let table;

$(document).ready(function () {
  table = $("#programmeTable").DataTable({
    processing: true,
    ajax: {
      url: "../api/admin/ajax/fetchProgrammes.php",
      type: "GET",
      dataSrc: "data",
    },
    
    dom: "Bfrtip", // enables buttons

    buttons: [
      {
        extend: "excelHtml5",
        title: "Programmes List",
        exportOptions: {
          columns: ":not(:last-child)"
        }
      },
      {
        extend: "pdfHtml5",
        title: "Programmes List",
        exportOptions: {
          columns: ":not(:last-child)"
        }
      }
    ],
    columns: [
      { data: null },
      { data: "institution" },
      { data: "name" },
      { data: "code" },
      { data: "status" },
      { data: "actions" },
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
});

// ==========================
// LOAD INSTITUTIONS
// ==========================
function loadInstitutions(callback = null) {
  $.get("../api/admin/ajax/endpoint/getinstitution.php", function (res) {
    let options = '<option value="">Select Institution</option>';

    if (res && res.data && Array.isArray(res.data)) {
      res.data.forEach((inst) => {
        options += `<option value="${inst.id}">${inst.name}</option>`;
      });
    } else {
      console.error("Invalid response:", res);
    }

    $("#institution_id").html(options);

    if (callback) callback();
  }).fail(function (err) {
    console.error("AJAX failed:", err);
  });
}

// ==========================
// ADD PROGRAMME
// ==========================
$("#addProgrammeBtn").click(function () {
  $("#programmeForm")[0].reset();
  $("#prog_id").val(""); // FIXED

  $("#programmeModalTitle").text("Add Programme");

  loadInstitutions(function () {
    $("#programmeModal").modal("show");
  });
});

// ==========================
// EDIT PROGRAMME
// ==========================
$(document).on("click", ".editBtn", function () {
  let btn = $(this);

  $("#programmeModalTitle").text("Edit Programme");

  loadInstitutions(function () {
    $("#prog_id").val(btn.data("id")); // FIXED CONSISTENT ID
    $("#institution_id").val(btn.data("institution"));
    $("#name").val(btn.data("name"));
    $("#code").val(btn.data("code"));

    $("#programmeModal").modal("show");
  });
});

// ==========================
// RELOAD TABLE
// ==========================
function reloadTable() {
  table.ajax.reload(null, false);
}

// ==========================
// SAVE AND EDIT PROGRAM
// ==========================
$("#programmeForm").submit(function (e) {
  e.preventDefault();

  $.ajax({
    url: "../api/admin/ajax/program/saveprogram.php",
    type: "POST",
    data: {
      prog_id: $("#prog_id").val(),
      institution_id: $("#institution_id").val(),
      name: $("#name").val(),
      code: $("#code").val()
    },
    dataType: "json",
    success: function (res) {
      if (res.status) {
        Swal.fire("Success", res.message, "success");
        $("#programmeModal").modal("hide");
        table.ajax.reload(null, false);
      } else {
        Swal.fire("Error", res.message, "error");
      }
    },
  });
});

// ==========================
// DELETE PROGRAM
// ==========================

$(document).on("click", ".deleteBtn", function () {
  let id = $(this).data("id");

  if (!confirm("Delete this programme?")) return;

  $.post(
    "../api/admin/ajax/program/deleteprogram.php",
    { id },
    function (res) {
      if (res.status) {
        Swal.fire("Success", res.message, "success");
        table.ajax.reload(null, false);
      } else {
        Swal.fire("Error", res.message, "error");
      }
    },
    "json",
  );
});
// ==========================
// TOGGLE PROGRAM
// ==========================

$(document).on("click", ".toggleBtn", function () {
  let id = $(this).data("id");

  $.post(
    "../api/admin/ajax/program/toggleprogram.php",
    { id },
    function (res) {
      if (res.status) {
        Swal.fire("Success", res.message, "success");
        table.ajax.reload(null, false);
      } else {
        Swal.fire("Error", res.message, "error");
      }
    },
    "json",
  );
});
