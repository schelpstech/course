let sessionTable;

$(document).ready(function () {
  sessionTable = $("#sessionTable").DataTable({
    ajax: {
      url: "../api/admin/ajax/academicSession/fetchSession.php",
      dataSrc: "data",
    },
    columns: [
      { data: null },
      { data: "name" },
      { data: "duration" },
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

// ADD
$("#addSessionBtn").click(function () {
  $("#sessionForm")[0].reset();
  $("#session_id").val("");
  $("#sessionModal").modal("show");
});

// EDIT
$(document).on("click", ".editSession", function () {
  let btn = $(this);

  $("#session_id").val(btn.data("id"));
  $("#name").val(btn.data("name"));
  $("#start_date").val(btn.data("start"));
  $("#end_date").val(btn.data("end"));

  $("#sessionModal").modal("show");
});

// SAVE
$("#sessionForm").submit(function (e) {
  e.preventDefault();

  $.post(
    "../api/admin/ajax/academicSession/saveSession.php",
    {
      id: $("#session_id").val(),
      name: $("#name").val(),
      start_date: $("#start_date").val(),
      end_date: $("#end_date").val(),
    },
    function (res) {
      if (res.status) {
        Swal.fire("Success", res.message, "success");
        $("#sessionModal").modal("hide");
        sessionTable.ajax.reload();
      }
    },
    "json",
  );
});

// ACTIVATE
$(document).on("click", ".activateSession", function () {
  $.post(
    "../api/admin/ajax/academicSession/activateSession.php",
    { id: $(this).data("id") },
    function (res) {
      Swal.fire("Done", res.message, "success");
      sessionTable.ajax.reload(null, false);
    },
    "json",
  );
});
