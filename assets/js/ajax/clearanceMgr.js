$(document).ready(function () {
  loadClearanceTypes();
  loadInstitutions();
});

/**
 * ----------------------------------------
 * LOAD INSTITUTIONS
 * ----------------------------------------
 */
function loadInstitutions(callback = null) {
  $.getJSON("../api/admin/ajax/endpoint/getinstitution.php", function (res) {
    let opt = "<option value=''>Select Institution</option>";

    (res.data || []).forEach((inst) => {
      opt += `<option value="${inst.id}">${inst.name}</option>`;
    });

    $("#institution_id").html(opt);

    if (typeof callback === "function") {
      callback();
    }
  });
}

/**
 * ----------------------------------------
 * LOAD CLEARANCE TYPES TABLE
 * ----------------------------------------
 */
function loadClearanceTypes() {
  $.getJSON(
    "../api/admin/ajax/clearance/getClearanceTypes.php",
    function (res) {
      let rows = "";

      (res.data || []).forEach((item, index) => {
        rows += `
                    <tr>

                        <td>${index + 1}</td>

                        <td>${item.institution_name}</td>

                        <td>${item.name}</td>

                        <td>
                            <span class="badge bg-info">
                                ${item.code}
                            </span>
                        </td>

                        <td>
                            <span class="badge ${
                              item.is_mandatory == 1
                                ? "bg-success"
                                : "bg-secondary"
                            }">
                                ${item.is_mandatory == 1 ? "Yes" : "No"}
                            </span>
                        </td>

                        <td>
                            <span class="badge ${
                              item.status == 1 ? "bg-success" : "bg-danger"
                            }">
                                ${item.status == 1 ? "Active" : "Inactive"}
                            </span>
                        </td>

                        <td>
                            <button
                                class="btn btn-warning btn-sm editBtn"
                                data-id="${item.id}">
                                Edit
                            </button>
                        </td>

                    </tr>
                `;
      });

      if ($.fn.DataTable.isDataTable("#clearanceTable")) {
        $("#clearanceTable").DataTable().destroy();
      }

      $("#clearanceTable tbody").html(rows);

      $("#clearanceTable").DataTable({
        responsive: true,

        pageLength: 25,

        order: [[0, "asc"]],

        dom:
          "<'row mb-3'<'col-md-6'B><'col-md-6'f>>" +
          "<'row'<'col-12'tr>>" +
          "<'row mt-3'<'col-md-5'i><'col-md-7'p>>",

        buttons: [
          {
            extend: "excelHtml5",
            text: '<i class="ph ph-file-xls"></i> Excel',
            className: "btn btn-success",
          },

          {
            extend: "pdfHtml5",
            text: '<i class="ph ph-file-pdf"></i> PDF',
            className: "btn btn-danger",

            orientation: "landscape",

            pageSize: "A4",

            title: "Institution Clearance Types",
          },

          {
            extend: "print",
            text: '<i class="ph ph-printer"></i> Print',
            className: "btn btn-primary",
          },
        ],
      });
    },
  );
}

/**
 * ----------------------------------------
 * OPEN MODAL (ADD)
 * ----------------------------------------
 */
$("#addClearanceBtn").on("click", function () {
  $("#clearanceForm")[0].reset();

  $("#clearance_id").val("");

  $("#clearanceModalTitle").text("Add Clearance Type");

  loadInstitutions();

  $("#clearanceModal").modal("show");
});

/**
 * ----------------------------------------
 * EDIT CLEARANCE TYPE
 * ----------------------------------------
 */
$(document).on("click", ".editBtn", function () {
  let id = $(this).data("id");

  $.post(
    "../api/admin/ajax/clearance/getSingleClearanceType.php",
    { id: id },
    function (res) {
      if (res.status === "success") {
        let data = res.data;

        $("#clearanceForm")[0].reset();

        $("#clearance_id").val(data.id);

        $("#name").val(data.name);

        $("#code").val(data.code);

        $("#description").val(data.description);

        $("#is_mandatory").val(data.is_mandatory);

        $("#status").val(data.status);

        $("#clearanceModalTitle").text("Edit Clearance Type");

        loadInstitutions(function () {
          $("#institution_id").val(data.institution_id);
        });

        $("#clearanceModal").modal("show");
      } else {
        Swal.fire("Error", res.message, "error");
      }
    },
    "json",
  ).fail(function () {
    Swal.fire("Error", "Failed to fetch record", "error");
  });
});

/**
 * ----------------------------------------
 * SAVE CLEARANCE TYPE
 * ----------------------------------------
 */
$("#clearanceForm").submit(function (e) {
  e.preventDefault();

  let data = {
    id: $("#clearance_id").val(),

    institution_id: $("#institution_id").val(),

    name: $("#name").val(),

    code: $("#code").val(),

    description: $("#description").val(),

    is_mandatory: $("#is_mandatory").val(),

    status: $("#status").val(),
  };

  $.post(
    "../api/admin/ajax/clearance/saveClearanceType.php",
    data,
    function (res) {
      if (res.status === "success") {
        Swal.fire("Success", res.message, "success");

        $("#clearanceModal").modal("hide");

        $("#clearanceForm")[0].reset();

        loadClearanceTypes();
      } else {
        Swal.fire("Error", res.message, "error");
      }
    },
    "json",
  ).fail(function () {
    Swal.fire("Error", "Request failed. Please try again.", "error");
  });
});
