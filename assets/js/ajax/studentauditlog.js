let auditTable;

document.addEventListener("DOMContentLoaded", function () {
  auditTable = $("#auditTable").DataTable({
    processing: true,
    ajax: {
      url: "../api/admin/ajax/endpoint/getAuditLogs.php",
      dataSrc: "data",
    },
    columns: [
      null,
      null,
      null,
      null,
      {
        data: 4,
        render: function (data, type) {
          if (type === "sort") {
            return data.sort; // raw timestamp
          }
          return data.display; // formatted date
        },
      },
    ],
    order: [[4, "desc"]],
  });

  // 🔁 Auto refresh every 10 seconds
  setInterval(function () {
    auditTable.ajax.reload(null, false);
  }, 30000);
});