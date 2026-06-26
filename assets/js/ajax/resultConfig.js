let resultConfigTable;
let resultConfigModal;
let resultConfigSemesters = [];

function resultConfigSetOptions(selector, rows, selected = "") {
  let html = "<option value=''>Select</option>";
  (rows || []).forEach((row) => {
    const selectedAttr = String(row.id) === String(selected) ? "selected" : "";
    html += `<option value="${row.id}" ${selectedAttr}>${$("<div>").text(row.name || "").html()}</option>`;
  });
  $(selector).html(html);
}

function resultConfigUpdateCsrf(token) {
  if (token && window.resultConfigManager) {
    window.resultConfigManager.csrf.save = token;
  }
}

function resultConfigLoadBootstrap(callback = null) {
  $.getJSON("../api/admin/ajax/results/bootstrap.php", function (res) {
    if (!res.status) {
      Swal.fire("Error", res.message || "Unable to load result setup", "error");
      return;
    }

    resultConfigSemesters = res.semesters || [];
    resultConfigSetOptions("#result_session_id", res.sessions || []);
    resultConfigSetOptions("#result_semester_id", []);
    if (callback) callback();
  });
}

function resultConfigFilterSemesters(sessionId, selected = "") {
  const rows = resultConfigSemesters.filter((semester) => String(semester.session_id) === String(sessionId));
  resultConfigSetOptions("#result_semester_id", rows, selected);
}

function resetResultConfigForm() {
  $("#resultConfigForm")[0].reset();
  $("#result_config_id").val("");
  resultConfigSetOptions("#result_semester_id", []);
  $("#ca_entry_enabled").val("1");
  $("#exam_entry_enabled").val("1");
  $("#result_publication_enabled").val("0");
  $("#gpa_enabled").val("1");
  $("#editing_enabled").val("1");
  $("#result_config_status").val("draft");
  $("#grace_period").val("0");
}

function updateTotalScore() {
  const ca = parseFloat($("#ca_max_score").val()) || 0;
  const exam = parseFloat($("#exam_max_score").val()) || 0;
  $("#total_max_score").val((ca + exam).toFixed(2));
}

$(document).ready(function () {
  resultConfigModal = new bootstrap.Modal(document.getElementById("resultConfigModal"));

  resultConfigTable = $("#resultConfigTable").DataTable({
    ajax: {
      url: "../api/admin/ajax/results/fetchResultConfigs.php",
      dataSrc: "data",
    },
    dom: "Bfrtip",
    buttons: [
      { extend: "excelHtml5", title: "Result Configuration", exportOptions: { columns: ":not(:last-child)" } },
      { extend: "pdfHtml5", title: "Result Configuration", exportOptions: { columns: ":not(:last-child)" } },
    ],
    columns: [
      { data: "session" },
      { data: "semester" },
      { data: "ca" },
      { data: "exam" },
      { data: "total" },
      { data: "entry" },
      { data: "publication" },
      { data: "deadline" },
      { data: "status" },
      { data: "actions", orderable: false, searchable: false },
    ],
  });

  $("#addResultConfigBtn").on("click", function () {
    $("#resultConfigModalTitle").text("Add Result Configuration");
    resultConfigLoadBootstrap(function () {
      resetResultConfigForm();
      resultConfigModal.show();
    });
  });
});

$(document).on("change", "#result_session_id", function () {
  resultConfigFilterSemesters($(this).val());
});

$(document).on("input", "#ca_max_score, #exam_max_score", updateTotalScore);

$(document).on("click", ".editResultConfig", function () {
  const id = $(this).data("id");
  $("#resultConfigModalTitle").text("Edit Result Configuration");

  $.getJSON("../api/admin/ajax/results/getResultConfig.php", { id }, function (res) {
    if (!res.status) {
      Swal.fire("Error", "Unable to load result configuration", "error");
      return;
    }

    resultConfigLoadBootstrap(function () {
      resetResultConfigForm();
      const config = res.config || {};

      $("#result_config_id").val(config.id || "");
      $("#result_session_id").val(config.academic_session_id || "");
      resultConfigFilterSemesters(config.academic_session_id, config.semester_id);
      $("#ca_max_score").val(config.ca_max_score || "");
      $("#exam_max_score").val(config.exam_max_score || "");
      $("#total_max_score").val(config.total_max_score || "");
      $("#ca_entry_enabled").val(String(config.ca_entry_enabled ?? 1));
      $("#exam_entry_enabled").val(String(config.exam_entry_enabled ?? 1));
      $("#result_publication_enabled").val(String(config.result_publication_enabled ?? 0));
      $("#gpa_enabled").val(String(config.gpa_enabled ?? 1));
      $("#editing_enabled").val(String(config.editing_enabled ?? 1));
      $("#result_config_status").val(config.status || "draft");
      $("#submission_deadline").val(config.submission_deadline_input || "");
      $("#grace_period").val(config.grace_period || 0);
      $("#result_config_remarks").val(config.remarks || "");

      resultConfigModal.show();
    });
  });
});

$(document).on("submit", "#resultConfigForm", function (e) {
  e.preventDefault();

  const formData = new FormData(this);
  formData.append("csrf_token", window.resultConfigManager.csrf.save);

  Swal.fire({
    title: "Saving...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: "../api/admin/ajax/results/saveResultConfig.php",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (res) {
      resultConfigUpdateCsrf(res.csrf_token);
      Swal.close();

      if (res.status) {
        resultConfigModal.hide();
        resultConfigTable.ajax.reload(null, false);
        Swal.fire("Success", res.message, "success");
      } else {
        Swal.fire("Error", res.message || "Unable to save result configuration", "error");
      }
    },
    error: function () {
      Swal.close();
      Swal.fire("Error", "Server error occurred", "error");
    },
  });
});
