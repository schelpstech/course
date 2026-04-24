let table;

$(document).ready(function () {
  table = $("#institutionTable").DataTable({
    processing: true,
    ajax: {
      url: "../api/admin/ajax/fetchInstitutions.php",
      type: "GET",
      dataSrc: "data",
    },
    columns: [
      { data: "logo" },
      { data: "name" },
      { data: "email" },
      { data: "address" },
      { data: "status" }, // ✅ NEW
      { data: "actions" },
    ],
  });
});

let modal = new bootstrap.Modal(document.getElementById("institutionModal"));

// OPEN CREATE
$("#addNewBtn").click(() => {
  $("#institutionForm")[0].reset();
  $("#modalTitle").text("Add Institution");
  $("#action").val("create");
  $("#inst_id").val("");
  modal.show();
});

// EDIT
$(document).on("click", ".editBtn", function () {
  $("#modalTitle").text("Edit Institution");

  $("#inst_id").val($(this).data("id"));
  $("#name").val($(this).data("name"));
  $("#email").val($(this).data("email"));
  $("#address").val($(this).data("address"));
  $("#slogan").val($(this).data("slogan"));

  $("#action").val("update");

  modal.show();
});

// CREATE / UPDATE

$("#institutionForm").submit(function (e) {
  e.preventDefault();

  let formData = new FormData(this);
  formData.append("action", $("#action").val());
  formData.append("id", $("#inst_id").val());
  formData.append("name", $("#name").val());
  formData.append("email", $("#email").val());
  formData.append("address", $("#address").val());
  formData.append("slogan", $("#slogan").val());
  formData.append("logo", $("#logo")[0].files[0]);
  $.ajax({
    url: "../api/admin/manageInstitution.php",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function (res) {
      // If response already JSON, DO NOT JSON.parse again
      console.log(res);

      if (res.status === "success") {
        $("#institutionModal").modal("hide");
        Swal.fire("Success", res.message, "success");

        $("#institutionTable").DataTable().ajax.reload(null, false);
      } else {
        Swal.fire("Error", res.message, "error");
      }
    },
    error: function () {
      Swal.fire("Error", "Something went wrong", "error");
    },
  });
});

// DELETE
$(document).on("click", ".deleteBtn", function () {
  let id = $(this).data("id");

  Swal.fire({
    title: "Delete this institution?",
    text: "This action cannot be undone",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    confirmButtonText: "Yes, delete it",
  }).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: "../api/admin/manageInstitution.php",
        type: "POST",
        data: {
          action: "delete",
          id: id,
        },
        dataType: "json", // 🔥 THIS FIXES JSON PARSING
        success: function (data) {
          if (data.status === "success") {
            Swal.fire("Deleted", data.message, "success");
            reloadTable();
          } else {
            Swal.fire("Error", data.message, "error");
          }
        },
        error: function (xhr) {
          console.log(xhr.responseText); // 🔍 DEBUG
          Swal.fire("Error", "Server error occurred", "error");
        },
      });
    }
  });
});

// TOGGLE
$(document).on("click", ".toggleBtn", function () {
  let id = $(this).data("id");

  $.ajax({
    url: "../api/admin/manageInstitution.php",
    type: "POST",
    data: {
      action: "toggle",
      id: id,
    },
    dataType: "json", // 🔥 auto parse JSON safely

    success: function (data) {
      if (data.status === "success") {
        Swal.fire({
          icon: "success",
          title: "Updated",
          text: data.message,
          timer: 1500,
          showConfirmButton: false,
        });

        reloadTable();
      } else {
        Swal.fire("Error", data.message, "error");
      }
    },

    error: function (xhr) {
      console.log(xhr.responseText); // 🔍 debug
      Swal.fire("Error", "Server error occurred", "error");
    },
  });
});

function reloadTable() {
  table.ajax.reload(null, false); // no page reset
}


