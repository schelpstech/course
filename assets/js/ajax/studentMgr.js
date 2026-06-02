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

    dom: "Bfrtip",

    buttons: [
      {
        extend: "excelHtml5",
        title: "Students Report",
        exportOptions: {
          columns: ":not(:last-child)",
        },
      },
      {
        extend: "pdfHtml5",
        title: "Students Report",
        exportOptions: {
          columns: ":not(:last-child)",
        },
      },
    ],

    columns: [
      { data: null },
      { data: "name" },
      { data: "matric" },
      { data: "programme" },
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

  loadDepartmentStats();
});

$(document).ready(function () {
  loadDepartmentStats();
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

$(document).on("change", "#programme", function () {
  let id = $(this).val();

  if (!id) {
    $("#department").html('<option value="">Select Department</option>');
    $("#level").html('<option value="">Select Level</option>');
    return;
  }

  loadDepartments(id);
});

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
      $("#department").html('<option value="">Select Department</option>');
      $("#level").html('<option value="">Select Level</option>');

      if (callback) callback();
    },
  );
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
      $("#level").html('<option value="">Select Level</option>');

      if (callback) callback();
    },
  );
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
  );
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
  );
});

// ===============================
// VIEW STUDENT PROFILE
// ===============================
$(document).on("click", ".viewStudent", function () {
  let id = $(this).data("id");

  $("#studentProfileContent").html(
    '<div class="text-center py-4">Loading...</div>',
  );
  $("#viewStudentModal").modal("show");

  $.get(
    "../api/admin/ajax/student/getStudentProfile.php",
    { id },
    function (res) {
      if (!res.status) {
        $("#studentProfileContent").html(
          '<div class="text-danger text-center">Failed to load profile</div>',
        );
        return;
      }

      let s = res.data;

      // ✅ FIXED: now inside correct scope
      let courseRows = "";

      if (s.courses && s.courses.length > 0) {
        s.courses.forEach((c) => {
          courseRows += `
            <tr>
              <td>${c.course_code}</td>
              <td>${c.course_title}</td>
              <td>${c.unit}</td>
            </tr>
          `;
        });
      } else {
        courseRows = `<tr><td colspan="3" class="text-center">No courses registered</td></tr>`;
      }
      let html = `
<div class="row g-3">

  <!-- LEFT: PASSPORT -->
  <div class="col-md-3 text-center">
    <img
  src="../${s.passport ? s.passport : "../uploads/passports/default-avatar.png"}"
  onerror="this.onerror=null; this.src='../uploads/passports/default-avatar.png';"
  class="img-thumbnail shadow-sm"
  style="width:140px;height:140px;object-fit:cover;border-radius:10px;"
>

    <h6 class="mt-2">${s.fullname}</h6>
    <small class="text-muted">${s.email}</small>
  </div>

  <!-- RIGHT: DETAILS -->
  <div class="col-md-9">

    <div class="row">

      <div class="col-md-4">
        <p><strong>Matric:</strong><br>${s.matric}</p>
        <p><strong>Gender:</strong><br>${s.gender}</p>
      </div>

      <div class="col-md-4">
        <p><strong>Programme:</strong><br>${s.programme}</p>
        <p><strong>Department:</strong><br>${s.department}</p>
      </div>

      <div class="col-md-4">
        <p><strong>Level:</strong><br>${s.level}</p>
        <p><strong>DOB:</strong><br>${s.dob}</p>
      </div>

    </div>

    <hr>

    <!-- SUMMARY CARDS -->
    <div class="row text-center">

      <div class="col-md-4">
        <div class="card border-0 bg-light">
          <div class="card-body">
            <h5>${s.total_courses}</h5>
            <small>Courses</small>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card border-0 bg-light">
          <div class="card-body">
            <h5>${s.total_units}</h5>
            <small>Total Units</small>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card border-0 bg-light">
          <div class="card-body">
            <h5>₦${Number(s.total_paid).toLocaleString()}</h5>
            <small>${s.payment_status}</small>
          </div>
        </div>
      </div>

    </div>

  </div>

  <!-- COURSE TABLE -->
  <div class="col-12 mt-3">

    <h6 class="mb-2">Registered Courses</h6>

    <div class="table-responsive">
      <table class="table table-striped table-bordered table-sm">
        <thead class="table-dark">
          <tr>
            <th>Code</th>
            <th>Title</th>
            <th>Unit</th>
          </tr>
        </thead>
        <tbody>
          ${courseRows}
        </tbody>
      </table>
    </div>

  </div>

</div>
`;

      $("#studentProfileContent").html(html);
    },
    "json",
  );
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
            <a href="#"
               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
              ${d.department}
              <span class="badge bg-primary rounded-pill">${d.total}</span>
              <span class="badge bg-warning rounded-pill">${d.registered}</span>
              <span class="badge bg-success rounded-pill">${d.percentage}%</span>
            </a>
          `;
        });
      }

      $("#deptStatsList").html(html);
    },
    "json",
  );
}

// =============================== //
// TOGGLE STUDENT
//  // ===============================
//

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

// =============================== //
// RESET STUDENT PASSWORD // ===============================
//
$(document).on("click", ".resetPassword", function () {
  let id = $(this).data("id");
  $.post(
    "../api/admin/ajax/student/resetpassword.php",
    { id },
    function (res) {
      Swal.fire("Done", res.message, "success");
      studentTable.ajax.reload(null, false);
    },
    "json",
  );
});

// =============================== //
// DELETE STUDENT
// // ===============================
//

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
