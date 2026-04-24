table = $("#departmentTable").DataTable({

  processing: true,
  ajax: {
    url: "../api/admin/ajax/department/fetchDepartments.php",
    type: "GET",
    dataSrc: "data",
  },

  columns: [
    { data: null }, // S/N
    { data: "programme" },
    { data: "name" },
    { data: "code" },
    { data: "status" },
    { data: "actions" }
  ],

  columnDefs: [
    {
      targets: 0,
      render: function (data, type, row, meta) {
        return meta.row + 1;
      }
    }
  ]
});

// =========================
// LOAD INSTITUTIONS
// =========================
function loadInstitutions(callback = null) {
  $.get("../api/admin/ajax/endpoint/getinstitution.php", function (res) {
    let options = '<option value="">Select Institution</option>';

    res.data.forEach((inst) => {
      options += `<option value="${inst.id}">${inst.name}</option>`;
    });

    $("#institution_id").html(options);

    if (callback) callback();
  });
}

// =========================
// LOAD PROGRAMMES
// =========================
$(document).on("change", "#institution_id", function () {
  let institution_id = $(this).val();

  if (!institution_id) {
    $("#programme_id").html('<option value="">Select Programme</option>');
    return;
  }

  $.get(
    "../api/admin/ajax/endpoint/getProgrammesByInstitution.php",
    {
      institution_id: institution_id,
    },
    function (res) {
      let options = '<option value="">Select Programme</option>';

      res.data.forEach((prog) => {
        options += `<option value="${prog.id}">${prog.name}</option>`;
      });

      $("#programme_id").html(options);
    },
  );
});

// =========================
// ADD DEPARTMENT
// =========================
$("#addDepartmentBtn").click(function () {
  $("#departmentForm")[0].reset();
  $("#dept_id").val("");
  $("#departmentModalTitle").text("Add Department");
  $("#programme_id").html('<option value="">Select Programme</option>');

  loadInstitutions(function () {
    $("#departmentModal").modal("show");
  });
});

// =========================
// EDIT DEPARTMENT
// =========================
$(document).on("click", ".editBtn", function () {
  let btn = $(this);

  let deptId = btn.data("id");
  let institutionId = btn.data("institution");
  let programmeId = btn.data("programme");

  $("#departmentModalTitle").text("Edit Department");

  $("#dept_id").val(deptId);
  $("#name").val(btn.data("name"));
  $("#code").val(btn.data("code"));

  loadInstitutions(function () {
    $("#institution_id").val(institutionId);

    $.get(
      "../api/admin/ajax/endpoint/getProgrammesByInstitution.php",
      {
        institution_id: institutionId,
      },
      function (res) {
        let options = '<option value="">Select Programme</option>';

        res.data.forEach((prog) => {
          options += `<option value="${prog.id}">${prog.name}</option>`;
        });

        $("#programme_id").html(options);

        $("#programme_id").val(programmeId);
      },
    );
  });

  $("#departmentModal").modal("show");
});

// =========================
// SAVE (CREATE / UPDATE)
// =========================
$("#departmentForm").submit(function (e) {
  e.preventDefault();

  $.post(
    "../api/admin/ajax/department/savedept.php",
    {
      dept_id: $("#dept_id").val(),
      programme_id: $("#programme_id").val(),
      name: $("#name").val(),
      code: $("#code").val(),
    },
    function (res) {
      if (res.status) {
         $("#departmentModal").modal("hide");
        Swal.fire({
          icon: "success",
          title: "Success",
          text: res.message,
          timer: 1500,
          showConfirmButton: false,
        });

       
        table.ajax.reload(null, false);
      } else {
         $("#departmentModal").modal("hide");
        Swal.fire({
          icon: "error",
          title: "Error",
          text: res.message,
        });
      }
    },
    "json",
  );
});

// =========================
// DELETE
// =========================
$(document).on("click", ".deleteBtn", function () {
  let id = $(this).data("id");

  Swal.fire({
    title: "Are you sure?",
    text: "This department will be deleted",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, delete it",
  }).then((result) => {
    if (result.isConfirmed) {
      $.post(
        "../api/admin/ajax/department/deletedept.php",
        { id },
        function (res) {
          if (res.status) {
            Swal.fire({
              icon: "success",
              title: "Deleted",
              text: res.message,
              timer: 1500,
              showConfirmButton: false,
            });

            table.ajax.reload(null, false);
          } else {
            Swal.fire("Error", res.message, "error");
          }
        },
        "json",
      );
    }
  });
});

// =========================
// TOGGLE (ENABLE / DISABLE)
// =========================
$(document).on("click", ".toggleBtn", function () {
  let id = $(this).data("id");

  $.post(
    "../api/admin/ajax/department/toggledept.php",
    { id },
    function (res) {
      if (res.status) {
        Swal.fire({
          icon: "success",
          title: "Updated",
          text: res.message,
          timer: 1200,
          showConfirmButton: false,
        });

        table.ajax.reload(null, false);
      } else {
        Swal.fire("Error", res.message, "error");
      }
    },
    "json",
  );
});
