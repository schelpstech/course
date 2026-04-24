let semTable;

$(document).ready(function () {

  loadSessionsDropdown();

  semTable = $("#semesterTable").DataTable({
    processing: true,
    ajax: {
      url: "../api/admin/ajax/semester/fetchSemester.php",
      dataSrc: "data"
    },
    columns: [
      { data: null },
      { data: "name" },
      { data: "session_name" }, // ensure backend uses this
      { data: "status" },
      { data: "actions" }
    ],
    columnDefs: [{
      targets: 0,
      render: (d, t, r, m) => m.row + 1
    }]
  });

});


/* =========================
   LOAD SESSIONS DROPDOWN
========================= */
function loadSessionsDropdown(callback = null) {

  $.getJSON("../api/admin/ajax/endpoint/getSessions.php", function (res) {

    let opt = '<option value="">Select Session</option>';

    (res.data || []).forEach((s) => {
      opt += `<option value="${s.id}">${s.name}</option>`;
    });

    $("#session_id, #filter_session").html(opt);

    if (callback) callback();

  }).fail(function () {
    console.error("Failed to load sessions");
    Swal.fire("Error", "Unable to load sessions", "error");
  });

}


/* =========================
   ADD SEMESTER
========================= */
$("#addSemesterBtn").click(function () {

  $("#semesterForm")[0].reset();
  $("#semester_id").val("");

  loadSessionsDropdown(() => {
    $("#semesterModal").modal("show");
  });

});


/* =========================
   SAVE SEMESTER
========================= */
$(document).off("submit", "#semesterForm").on("submit", "#semesterForm", function (e) {

  e.preventDefault();

  let formData = {
    id: $("#semester_id").val(),
    session_id: $("#session_id").val(),
    name: $("#name").val(),
    start_date: $("#start_date").val(),
    end_date: $("#end_date").val()
  };

  // LOADING STATE
  Swal.fire({
    title: "Processing...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  $.post("../api/admin/ajax/semester/saveSemester.php", formData, function (res) {

    Swal.close();

    if (res.status) {

      Swal.fire("Success", res.message, "success");

      $("#semesterModal").modal("hide");

      semTable.ajax.reload(null, false);

    } else {

      Swal.fire("Error", res.message || "Something went wrong", "error");

    }

  }, "json")

  .fail(function () {
    Swal.close();
    Swal.fire("Error", "Server error occurred", "error");
  });

});


/* =========================
   ACTIVATE SEMESTER
========================= */
$(document).on("click", ".activateSemester", function () {

  let id = $(this).data("id");

  Swal.fire({
    title: "Activate this semester?",
    text: "This will deactivate any currently active semester",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, Activate"
  }).then((result) => {

    if (result.isConfirmed) {

      Swal.fire({
        title: "Updating...",
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
      });

      $.post(
        "../api/admin/ajax/semester/activateSemester.php",
        { id },
        function (res) {

          Swal.close();

          if (res.status) {

            Swal.fire("Success", res.message, "success");

            semTable.ajax.reload(null, false);

          } else {

            Swal.fire("Error", res.message, "error");

          }

        },
        "json"
      )

      .fail(function () {
        Swal.close();
        Swal.fire("Error", "Server error occurred", "error");
      });

    }

  });

});


/* =========================
   OPTIONAL: FILTER BY SESSION
========================= */
$(document).on("change", "#filter_session", function () {

  semTable.ajax.url(
    "../api/admin/ajax/semester/fetchSemester.php?session_id=" + $(this).val()
  ).load();

});