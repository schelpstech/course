let courseformTable;

// ✅ Load filters first
function loadFilters() {
  $.getJSON("../api/admin/ajax/semester/getFilters.php", function (res) {
    let sessionOpt = '<option value="">Select Session</option>';
    let semesterOpt = '<option value="">Select Semester</option>';

    (res.sessions || []).forEach((s) => {
      sessionOpt += `<option value="${s.id}">${s.name}</option>`;
    });

    (res.semesters || []).forEach((s) => {
      semesterOpt += `<option value="${s.id}">${s.name}</option>`;
    });

    $("#sessionFilter").html(sessionOpt);
    $("#semesterFilter").html(semesterOpt);
  }).fail(function () {
    console.error("Failed to load filters");
  });
}

// ✅ Initialize DataTable
function initTable() {
  courseformTable = $("#courseformTable").DataTable({
    processing: true,
    serverSide: true,
    searching: true,
    paging: true,
    lengthChange: true,

    // 🔥 ROWS PER PAGE CONTROL (ADDED)
    pageLength: 25,
    lengthMenu: [
      [10, 25, 50, 100, 200],
      [10, 25, 50, 100, 200],
    ],

    ajax: {
      url: "../api/admin/ajax/courseform/load_students.php",
      type: "GET",
      data: function (d) {
        d.session_id = $("#sessionFilter").val();
        d.semester_id = $("#semesterFilter").val();
        d.clearance_workflow = $("#clearanceWorkflowFilter").val();
      },
    },

    dom: "Bfrtip",

    buttons: [
      {
        extend: "excelHtml5",
        title: "Students Report",
      },
      {
        extend: "pdfHtml5",
        title: "Students Report",
      },
    ],

    columns: [
      {
        data: null,
        render: (d, t, r, m) => m.row + 1 + m.settings._iDisplayStart,
      },

      {
        data: null,
        render: (d) => `
      <div>
        <strong>${d.name}</strong><br>
        <small>${d.matric_no || "-"}</small>
      </div>
    `,
      },

      {
        data: null,
        render: (d) => `
      <div>
        <strong>${d.department || "-"}</strong><br>
        <small>${d.level || "-"}</small>
      </div>
    `,
      },

      {
        data: "courses_count",
        render: (d, t, r) => `
      <button class="btn btn-sm btn-info"
        onclick="viewCourses(${r.course_regID})">
        ${d} Courses
      </button>
    `,
      },

      {
        data: "status",
        render: (d) => {
          let color = "secondary";

          if (d === "Approved") color = "success";
          else if (d === "Pending") color = "warning";
          else if (d === "Submitted") color = "info";
          else if (d === "Rejected") color = "danger";

          return `<span class="badge bg-${color}">${d}</span>`;
        },
      },
      {
        data: "clearance_status",
        render: function (d) {
          if (d === "approved") {
            return `
                <span class="badge bg-success">
                    Cleared
                </span>
            `;
          }

          return `
            <span class="badge bg-warning">
                Pending
            </span>
        `;
        },
      },

      {
        data: null,
        render: function (d) {
          let html = `
            <div class="d-flex gap-1">

                <select
                    class="form-control form-control-sm"
                    onchange="updateStatus(
                        ${d.course_regID},
                        this.value
                    )">

                    <option value="">
                        Change Status
                    </option>

                    <option value="pending">
                        Pending
                    </option>

                    <option value="submitted">
                        Submitted
                    </option>

                    <option value="approved">
                        Approved
                    </option>

                    <option value="rejected">
                        Rejected
                    </option>

                </select>
        `;

          if (d.status === "Approved" && d.clearance_status !== "approved") {
            html += `
                <button
                    class="btn btn-success btn-sm"
                    onclick="
                        approveCourseClearance(
                            ${d.semester_registration_id}
                        )
                    ">
                    Issue Clearance
                </button>
            `;
          }

          if (d.clearance_status === "approved") {
            html += `
                <span
                    class="badge bg-success">
                    Cleared
                </span>
            `;
          }

          html += `</div>`;

          return html;
        },
      },
    ],
  });
}

// 🔥 FIXED: single reload function
function reloadTable() {
  if (!courseformTable) return;

  courseformTable.ajax.reload(null, false);
}

// 🔁 Auto refresh every 30 seconds
setInterval(function () {
  if (document.hidden) return;

  let session = $("#sessionFilter").val();
  let semester = $("#semesterFilter").val();

  if (!session || !semester) return;

  reloadTable();
}, 30000);

// 🔄 Reload when filters change
$("#sessionFilter, #semesterFilter, #clearanceWorkflowFilter").on("change", function () {
  reloadTable();
});

// 🚀 Init
$(document).ready(function () {
  loadFilters();
  initTable();
});

function viewCourses(course_regID) {
  $("#coursesModal").modal("show");
  $("#coursesModalBody").html("<div class='text-center'>Loading...</div>");

  $.get(
    "../api/admin/ajax/courseform/view_courses.php",
    { id: course_regID },
    function (res) {
      $("#coursesModalBody").html(res);
    },
  ).fail(function () {
    $("#coursesModalBody").html(
      "<div class='text-danger'>Failed to load courses</div>",
    );
  });
}
function approveCourseClearance(semester_registration_id) {
  Swal.fire({
    title: "Approve Course Clearance?",

    text: "Student's course registration will be cleared.",

    icon: "question",

    showCancelButton: true,
  }).then((result) => {
    if (!result.isConfirmed) {
      return;
    }

    $.post(
      "../api/admin/ajax/clearance/approveCourseClearance.php",

      {
        semester_registration_id: semester_registration_id,
      },

      function (res) {
        if (res.status === "success") {
          Swal.fire("Success", res.message, "success");

          courseformTable.ajax.reload(null, false);
        } else {
          Swal.fire("Error", res.message, "error");
        }
      },

      "json",
    );
  });
}

function updateStatus(id, status) {
  if (!status) return;

  Swal.fire({
    title: "Change Status?",
    text: "This will update the student's course registration status.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Yes, update it!",
  }).then((result) => {
    if (!result.isConfirmed) return;

    $.post(
      "../api/admin/ajax/courseform/update_status.php",
      { id: id, status: status },
      function (res) {
        if (res.status === "success") {
          // ✅ Toast notification
          showToast("Status updated successfully", "success");

          // ✅ Optional SweetAlert success popup (quick)
          Swal.fire({
            icon: "success",
            title: "Updated!",
            text: "Registration status has been updated.",
            timer: 1500,
            showConfirmButton: false,
          });

          // 🔄 Reload table (no page reset)
          courseformTable.ajax.reload(null, false);
        } else {
          showToast("Failed to update status", "error");

          Swal.fire({
            icon: "error",
            title: "Error",
            text: "Failed to update status",
          });
        }
      },
      "json",
    ).fail(function () {
      showToast("Server error occurred", "error");

      Swal.fire({
        icon: "error",
        title: "Server Error",
        text: "Please try again later",
      });
    });
  });
}

function showToast(message, type = "success") {
  const Toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 2500,
    timerProgressBar: true,
  });

  Toast.fire({
    icon: type,
    title: message,
  });
}
