let table;

$(document).ready(function () {
  table = $("#levelTable").DataTable({
    ajax: {
      url: "../api/admin/ajax/level/fetchLevels.php",
      dataSrc: "data",
    },
    
    dom: "Bfrtip", // enables buttons

    buttons: [
      {
        extend: "excelHtml5",
        title: "Levels List",
        exportOptions: {
          columns: ":not(:last-child)"
        }
      },
      {
        extend: "pdfHtml5",
        title: "Levels List",
        exportOptions: {
          columns: ":not(:last-child)"
        }
      }
    ],
    columns: [
      { data: null },
      { data: "department_name" },
      { data: "name" },
      { data: "code" },
      { data: "status" },
      { data: "actions" },
    ],
    columnDefs: [
      {
        targets: 0,
        render: (data, type, row, meta) => meta.row + 1,
      },
    ],
  });
});

// ===================
// CASCADING LOGIC
// ===================

// Institution → Programme
$(document).on("change", "#institution_id", function () {
  $.get(
    "../api/admin/ajax/endpoint/getProgrammesByInstitution.php",
    { institution_id: $(this).val() },
    function (res) {
      let opt = "<option>Select Programme</option>";
      res.data.forEach(
        (p) => (opt += `<option value="${p.id}">${p.name}</option>`),
      );
      $("#programme_id").html(opt);
    },
  );
});

// Programme → Department
$(document).on("change", "#programme_id", function () {
  $.get(
    "../api/admin/ajax/endpoint/getDepartmentsByProgramme.php",
    { programme_id: $(this).val() },
    function (res) {
      let opt = "<option>Select Department</option>";
      res.data.forEach(
        (d) => (opt += `<option value="${d.id}">${d.name}</option>`),
      );
      $("#department_id").html(opt);
    },
  );
});

function loadInstitutions(callback = null) {
  $.get("../api/admin/ajax/endpoint/getinstitution.php", function (res) {
    let options = '<option value="">Select Institution</option>';

    if (res.data && res.data.length > 0) {
      res.data.forEach((inst) => {
        options += `<option value="${inst.id}">${inst.name}</option>`;
      });
    }

    $("#institution_id").html(options);

    if (callback) callback();
  }).fail(function () {
    console.error("Failed to load institutions");
  });
}

// ===================
// ADD
// ===================
$("#addLevelBtn").click(function () {
  $("#levelForm")[0].reset();
  $("#level_id").val("");

  loadInstitutions(() => $("#levelModal").modal("show"));
});

// ===================
// SAVE
// ===================
$("#levelForm").submit(function (e) {
  e.preventDefault();

  $.post(
    "../api/admin/ajax/level/saveLevel.php",
    {
      level_id: $("#level_id").val(),
      department_id: $("#department_id").val(),
      name: $("#name").val(),
      code: $("#code").val(),
    },
    function (res) {
      if (res.status) {
        Swal.fire("Success", res.message, "success");
        $("#levelModal").modal("hide");
        table.ajax.reload();
      }
    },
    "json",
  );
});

$(document).on("click", ".editBtn", function () {
  let btn = $(this);

  let id = btn.data("id");
  let institutionId = btn.data("institution");
  let programmeId = btn.data("programme");
  let departmentId = btn.data("department");

  let name = btn.data("name");
  let code = btn.data("code");

  $("#levelModalTitle").text("Edit Level");

  $("#level_id").val(id);
  $("#name").val(name);
  $("#code").val(code);

  // STEP 1: Load Institutions
  loadInstitutions(function () {
    $("#institution_id").val(institutionId);

    // STEP 2: Load Programmes
    $.get(
      "../api/admin/ajax/endpoint/getProgrammesByInstitution.php",
      { institution_id: institutionId },
      function (res) {
        let opt = "<option>Select Programme</option>";
        (res.data || []).forEach((p) => {
          opt += `<option value="${p.id}">${p.name}</option>`;
        });

        $("#programme_id").html(opt);
        $("#programme_id").val(programmeId);

        // STEP 3: Load Departments
        $.get(
          "../api/admin/ajax/endpoint/getDepartmentsByProgramme.php",
          { programme_id: programmeId },
          function (res2) {
            let opt2 = "<option>Select Department</option>";
            (res2.data || []).forEach((d) => {
              opt2 += `<option value="${d.id}">${d.name}</option>`;
            });

            $("#department_id").html(opt2);
            $("#department_id").val(departmentId);
          },
        );
      },
    );
  });

  $("#levelModal").modal("show");
});

$(document).on("click", ".toggleBtn", function () {
  let id = $(this).data("id");

  $.post(
    "../api/admin/ajax/level/toggleLevel.php",
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
