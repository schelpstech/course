let auditTable;

document.addEventListener("DOMContentLoaded", function () {
  auditTable = $("#auditTable").DataTable({
    processing: true,
    ajax: {
      url: "../api/admin/ajax/endpoint/getAuditLogs.php",
      dataSrc: "data",
    },
    order: [[0, "desc"]],
  });

  // 🔁 Auto refresh every 10 seconds
  setInterval(function () {
    auditTable.ajax.reload(null, false); // false = keep pagination
  }, 10000);
});
