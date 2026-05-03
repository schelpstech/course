let courseTable;

$(document).ready(function () {
  courseTable = $("#courseTable").DataTable({
    ajax: {
      url: "../api/admin/ajax/course/fetchCourses.php",
      dataSrc: "data",
    },

    dom: "Bfrtip", // enables buttons

    buttons: [
      {
        extend: "excelHtml5",
        title: "Courses List",
        exportOptions: {
          columns: ":not(:last-child)"
        }
      },
      {
        extend: "pdfHtml5",
        title: "Courses List",
        exportOptions: {
          columns: ":not(:last-child)"
        }
      }
    ],

    columns: [
      { data: null },
      { data: "code" },
      { data: "title" },
      { data: "unit" },
      { data: "level" },
      { data: "semester" },
      { data: "status" },
      { data: "actions" },
    ],

    columnDefs: [
      {
        targets: 0,
        render: (d, t, r, m) => m.row + 1,
      },
    ],
  });
});

/* ======================
   LOAD DROPDOWNS
====================== */

// Institution
function loadInstitutions(callback = null) {
  $.getJSON("../api/admin/ajax/endpoint/getinstitution.php", function (res) {
    let opt = "<option>Select Institution</option>";

    (res.data || []).forEach((i) => {
      opt += `<option value="${i.id}">${i.name}</option>`;
    });

    $("#institution_id").html(opt);
    if (callback) callback();
  });
}

// Programme
$(document).on("change", "#institution_id", function () {
  $.getJSON(
    "../api/admin/ajax/endpoint/getProgrammesByInstitution.php",
    { institution_id: $(this).val() },
    function (res) {
      let opt = "<option>Select Programme</option>";

      (res.data || []).forEach((p) => {
        opt += `<option value="${p.id}">${p.name}</option>`;
      });

      $("#programme_id").html(opt);
    },
  );
});

// Department
$(document).on("change", "#programme_id", function () {
  $.getJSON(
    "../api/admin/ajax/endpoint/getDepartmentsByProgramme.php",
    { programme_id: $(this).val() },
    function (res) {
      let opt = "<option>Select Department</option>";

      (res.data || []).forEach((d) => {
        opt += `<option value="${d.id}">${d.name}</option>`;
      });

      $("#department_id").html(opt);
    },
  );
});

// Level
$(document).on("change", "#department_id", function () {
  $.getJSON(
    "../api/admin/ajax/endpoint/getLevelsByDepartment.php",
    { department_id: $(this).val() },
    function (res) {
      let opt = "<option>Select Level</option>";

      (res.data || []).forEach((l) => {
        opt += `<option value="${l.id}">${l.name}</option>`;
      });

      $("#level_id").html(opt);
    },
  );
});

function loadSemesters() {
  $.getJSON("../api/admin/ajax/endpoint/getSemesters.php", function (res) {
    let opt = "<option value=''>Select Semester</option>";

    (res.data || []).forEach((s) => {
      opt += `<option value="${s.id}">
        ${s.semester_name} Semester ${s.academic_sessions_name}
      </option>`;
    });

    $("#semester_id").html(opt);
  });
}

/* ======================
   ADD COURSE
====================== */
$("#addCourseBtn").click(function () {
  $("#courseForm")[0].reset();
  $("#course_id").val("");

  loadInstitutions();
  loadSemesters();

  $("#courseModal").modal("show");
});

/* ======================
   EDIT COURSE
====================== */
$(document).on("click", ".editCourse", function () {
  let btn = $(this);

  $("#course_id").val(btn.data("id"));
  $("#course_code").val(btn.data("code"));
  $("#course_title").val(btn.data("title"));
  $("#unit").val(btn.data("unit"));
  $("#course_type").val(btn.data("type"));

  let institution = btn.data("institution");
  let programme = btn.data("programme");
  let department = btn.data("department");
  let level = btn.data("level");
  let semester = btn.data("semester");

  // STEP 1: load institutions
  loadInstitutions(function () {
    $("#institution_id").val(institution).trigger("change");

    // STEP 2: programme
    setTimeout(() => {
      $("#programme_id").val(programme).trigger("change");

      // STEP 3: department
      setTimeout(() => {
        $("#department_id").val(department).trigger("change");

        // STEP 4: level
        setTimeout(() => {
          $("#level_id").val(level);
        }, 300);
      }, 300);
    }, 300);
  });

  // STEP 5: semester (independent)
  loadSemesters();
  setTimeout(() => {
    $("#semester_id").val(semester);
  }, 300);

  $("#courseModal").modal("show");
});

$(document).on("click", ".toggleCourse", function () {
  let id = $(this).data("id");

  Swal.fire({
    title: "Change course status?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes",
  }).then((result) => {
    if (result.isConfirmed) {
      $.post(
        "../api/admin/ajax/course/toggleCourse.php",
        { id },
        function (res) {
          if (res.status) {
            Swal.fire("Success", res.message, "success");
            courseTable.ajax.reload(null, false);
          } else {
            Swal.fire("Error", res.message, "error");
          }
        },
        "json",
      );
    }
  });
});

$(document)
  .off("submit", "#courseForm")
  .on("submit", "#courseForm", function (e) {
    e.preventDefault();

    let formData = {
      id: $("#course_id").val(),
      course_code: $("#course_code").val(),
      course_title: $("#course_title").val(),
      course_type: $("#course_type").val(),
      unit: $("#unit").val(),
      level_id: $("#level_id").val(),
      semester: $("#semester_id").val(), // ✅ FIXED
    };
    $("#courseModal").modal("hide"); // close FIRST
    Swal.fire({
      title: "Saving...",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    $.post(
      "../api/admin/ajax/course/saveCourse.php",
      formData,
      function (res) {
        Swal.close();

        if (res.status) {
          Swal.fire({
            icon: "success",
            title: "Success",
            text: res.message,
            timer: 1500,
            showConfirmButton: false,
          });

          courseTable.ajax.reload(null, false);
        } else {
          Swal.fire("Error", res.message, "error");
        }
      },
      "json",
    )

      .fail(function () {
        Swal.close();
        Swal.fire("Error", "Server error", "error");
      });
  });
