let regTable;

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
  regTable = $("#regTable").DataTable({
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
      url: "../api/admin/ajax/semester/getSemesterRegistrations.php",
      type: "GET",
      data: function (d) {
        d.session_id = $("#sessionFilter").val();
        d.semester_id = $("#semesterFilter").val();
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
            <strong>${d.level || "-"}</strong><br>
            <span>${d.program || "-"}</span><br>
            <small>${d.institution || "-"}</small>
          </div>
        `,
      },
      {
        data: "receipt_uploaded",
        render: (d) =>
          d == 1
            ? '<span class="text-success">✔ Uploaded</span>'
            : '<span class="text-danger">✖ Not Uploaded</span>',
      },

      {
        data: "payment_confirmed",
        render: (d) =>
          d == 1
            ? '<span class="text-success">✔ Confirmed</span>'
            : '<span class="text-danger">✖ Not Confirmed</span>',
      },

      {
        data: "course_fee_paid",
        render: (d) =>
          d == 1
            ? '<span class="text-success">✔ Paid</span>'
            : '<span class="text-danger">✖ Not Paid</span>',
      },

      {
        data: "courses_registered",
        render: (d) =>
          d == 1
            ? '<span class="text-success">✔ Registered</span>'
            : '<span class="text-danger">✖ Not Registered</span>',
      },

      {
        data: "status",
        render: (d) => {
          let color = "secondary";
          if (d === "Completed") color = "success";
          else if (d.includes("Awaiting")) color = "warning";
          else color = "danger";

          return `<span class="badge bg-${color}">${d}</span>`;
        },
      },
    ],
  });
}

// 🔥 FIXED: single reload function
function reloadTable() {
  let session = $("#sessionFilter").val();
  let semester = $("#semesterFilter").val();

  if (!session || !semester || !regTable) return;

  regTable.ajax.reload(null, false);
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
$("#sessionFilter, #semesterFilter").on("change", function () {
  reloadTable();
});

// 🚀 Init
$(document).ready(function () {
  loadFilters();
  initTable();
});
