// ===============================
// STUDENT TABLE INITIALIZATION
// ===============================
let studentTable;

$(document).ready(function () {
  studentTable = $("#studentsTable").DataTable({
    processing: true,
    ajax: {
      url: "../api/admin/ajax/student/fetchStudents.php",
      dataSrc: "data",
      error: function (xhr) {
        console.error("DataTables AJAX Error:", xhr.responseText);
        Swal.fire("Error", "Failed to load students", "error");
      },
    },
    columns: [
      { data: null },
      { data: "name" },
      { data: "matric" },
      { data: "programme" },
      { data: "level" },
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

// ===============================
// LOAD INSTITUTIONS
// ===============================
function loadInstitutions(callback = null) {
  $.getJSON("../api/admin/ajax/endpoint/getinstitution.php")
    .done(function (res) {
      let opt = '<option value="">Select Institution</option>';

      (res.data || []).forEach((i) => {
        opt += `<option value="${i.id}">${i.name}</option>`;
      });

      $("#institution").html(opt);

      if (callback) callback();
    })
    .fail(function () {
      Swal.fire("Error", "Failed to load institutions", "error");
    });
}

// ===============================
// CASCADING DROPDOWNS
// ===============================

// Institution → Programme
$(document).on("change", "#institution", function () {
  let id = $(this).val();

  if (!id) {
    $("#programme").html('<option value="">Select Programme</option>');
    $("#department").html('<option value="">Select Department</option>');
    $("#level").html('<option value="">Select Level</option>');
    return;
  }

  loadProgrammes(id);
});

// Programme → Department
$(document).on("change", "#programme", function () {
  let id = $(this).val();

  if (!id) {
    $("#department").html('<option value="">Select Department</option>');
    $("#level").html('<option value="">Select Level</option>');
    return;
  }

  loadDepartments(id);
});

// Department → Level
$(document).on("change", "#department", function () {
  let id = $(this).val();

  if (!id) {
    $("#level").html('<option value="">Select Level</option>');
    return;
  }

  loadLevels(id);
});

// ===============================
// LOAD PROGRAMMES
// ===============================
function loadProgrammes(institution_id, callback = null) {
  $.get(
    "../api/admin/ajax/endpoint/getProgrammesByInstitution.php",
    { institution_id },
    function (res) {
      let opt = '<option value="">Select Programme</option>';

      (res.data || []).forEach((p) => {
        opt += `<option value="${p.id}">${p.name}</option>`;
      });

      $("#programme").html(opt);

      // reset downstream
      $("#department").html('<option value="">Select Department</option>');
      $("#level").html('<option value="">Select Level</option>');

      if (callback) callback();
    },
  ).fail(() => {
    Swal.fire("Error", "Failed to load programmes", "error");
  });
}

// ===============================
// LOAD DEPARTMENTS
// ===============================
function loadDepartments(programme_id, callback = null) {
  $.get(
    "../api/admin/ajax/endpoint/getDepartmentsByProgramme.php",
    { programme_id },
    function (res) {
      let opt = '<option value="">Select Department</option>';

      (res.data || []).forEach((d) => {
        opt += `<option value="${d.id}">${d.name}</option>`;
      });

      $("#department").html(opt);

      // reset downstream
      $("#level").html('<option value="">Select Level</option>');

      if (callback) callback();
    },
  ).fail(() => {
    Swal.fire("Error", "Failed to load departments", "error");
  });
}

// ===============================
// LOAD LEVELS
// ===============================
function loadLevels(department_id, callback = null) {
  $.get(
    "../api/admin/ajax/endpoint/getLevelsByDepartment.php",
    { department_id },
    function (res) {
      let opt = '<option value="">Select Level</option>';

      (res.data || []).forEach((l) => {
        opt += `<option value="${l.id}">${l.name}</option>`;
      });

      $("#level").html(opt);

      if (callback) callback();
    },
  ).fail(() => {
    Swal.fire("Error", "Failed to load levels", "error");
  });
}

// ===============================
// ADD STUDENT
// ===============================
$("#addStudentBtn").click(function () {
  $("#studentForm")[0].reset();
  $("#student_id").val("");

  $("#programme").html('<option value="">Select Programme</option>');
  $("#department").html('<option value="">Select Department</option>');
  $("#level").html('<option value="">Select Level</option>');
  loadInstitutions(() => $("#addStudentModal").modal("show"));
});

// ===============================
// EDIT STUDENT
// ===============================
$(document).on("click", ".editStudent", function () {
  let btn = $(this);

  $("#student_id").val(btn.data("id"));
  $("input[name='matric_no']").val(btn.data("matric"));
  $("input[name='email']").val(btn.data("email"));
  $("input[name='first_name']").val(btn.data("first"));
  $("input[name='other_name']").val(btn.data("other"));
  $("input[name='last_name']").val(btn.data("last"));
  $("input[name='dob']").val(btn.data("dob"));
  $("select[name='gender']").val(btn.data("gender"));

  loadInstitutions(() => {
    $("#institution").val(btn.data("institution"));

    loadProgrammes(btn.data("institution"), () => {
      $("#programme").val(btn.data("programme"));

      loadDepartments(btn.data("programme"), () => {
        $("#department").val(btn.data("department"));

        loadLevels(btn.data("department"), () => {
          $("#level").val(btn.data("level"));
        });
      });
    });
  });

  $("#addStudentModal").modal("show");
});

// ===============================
// SAVE STUDENT
// ===============================
$("#studentForm").submit(function (e) {
  e.preventDefault();

  // VALIDATION
  if (!$("#institution").val()) {
    Swal.fire("Error", "Select institution", "error");
    return;
  }

  if (!$("#programme").val()) {
    Swal.fire("Error", "Select programme", "error");
    return;
  }

  if (!$("#department").val()) {
    Swal.fire("Error", "Select department", "error");
    return;
  }

  if (!$("#level").val()) {
    Swal.fire("Error", "Select level", "error");
    return;
  }

  let data = {
    id: $("#student_id").val(),
    matric_no: $("input[name='matric_no']").val(),
    email: $("input[name='email']").val(),
    first_name: $("input[name='first_name']").val(),
    other_name: $("input[name='other_name']").val(),
    last_name: $("input[name='last_name']").val(),
    dob: $("input[name='dob']").val(),
    gender: $("select[name='gender']").val(),
    institution_id: $("#institution").val(),
    programme_id: $("#programme").val(),
    department_id: $("#department").val(),
    level_id: $("#level").val(),
  };

  Swal.fire({
    title: "Saving...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.post(
    "../api/admin/ajax/student/saveStudent.php",
    data,
    function (res) {
      Swal.close();

      if (res.status) {
        $("#addStudentModal").modal("hide");
        Swal.fire("Success", res.message, "success");

        studentTable.ajax.reload(null, false);
      } else {
        Swal.fire("Error", res.message, "error");
      }
    },
    "json",
  ).fail(() => {
    Swal.close();
    Swal.fire("Error", "Server error", "error");
  });
});

// ===============================
// TOGGLE STUDENT
// ===============================
$(document).on("click", ".toggleStudent", function () {
  let id = $(this).data("id");

  $.post(
    "../api/admin/ajax/student/toggleStudent.php",
    { id },
    function (res) {
      Swal.fire("Done", res.message, "success");
      studentTable.ajax.reload(null, false);
    },
    "json",
  );
});

// ===============================
// DELETE STUDENT
// ===============================
$(document).on("click", ".deleteStudent", function () {
  let id = $(this).data("id");

  Swal.fire({
    title: "Delete student?",
    text: "This action cannot be undone",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, delete",
  }).then((r) => {
    if (r.isConfirmed) {
      $.post(
        "../api/admin/ajax/student/deleteStudent.php",
        { id },
        function (res) {
          if (res.status) {
            Swal.fire("Deleted", res.message, "success");
            studentTable.ajax.reload(null, false);
          } else {
            Swal.fire("Error", res.message, "error");
          }
        },
        "json",
      );
    }
  });
});

function loadDepartmentStats() {
  $.get(
    "../api/admin/ajax/dashboard/fetchDepartmentStats.php",
    function (res) {
      let html = "";

      if (!res.data || res.data.length === 0) {
        html = `<div class="text-muted">No data available</div>`;
      } else {
        res.data.forEach((d) => {
          html += `
          <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
              ${d.department}
              <span class="badge bg-primary rounded-pill">${d.total}</span>
          </a>
        `;
        });
      }

      $("#deptStatsList").html(html);
    },
    "json",
  );
}

$(document).ready(function () {
  loadDepartmentStats();
});
