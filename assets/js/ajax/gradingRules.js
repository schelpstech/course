let gradingRulesTable;
let gradingRuleModal;

function gradingSetOptions(selector, rows, selected = "") {
  let html = "<option value=''>Select</option>";
  (rows || []).forEach((row) => {
    const selectedAttr = String(row.id) === String(selected) ? "selected" : "";
    html += `<option value="${row.id}" ${selectedAttr}>${$("<div>").text(row.name || "").html()}</option>`;
  });
  $(selector).html(html);
}

function gradingUpdateCsrf(token) {
  if (token && window.gradingRuleManager) {
    window.gradingRuleManager.csrf.save = token;
  }
}

function gradingLoadBootstrap(callback = null) {
  $.getJSON("../api/admin/ajax/results/bootstrap.php", function (res) {
    if (!res.status) {
      Swal.fire("Error", res.message || "Unable to load grading setup", "error");
      return;
    }

    gradingSetOptions("#grading_institution_id", res.institutions || []);
    if (callback) callback();
  });
}

function resetGradingForm() {
  $("#gradingRuleForm")[0].reset();
  $("#grading_rule_id").val("");
  $("#grading_status").val("1");
}

$(document).ready(function () {
  gradingRuleModal = new bootstrap.Modal(document.getElementById("gradingRuleModal"));

  gradingRulesTable = $("#gradingRulesTable").DataTable({
    ajax: {
      url: "../api/admin/ajax/results/fetchGradingRules.php",
      dataSrc: "data",
    },
    dom: "Bfrtip",
    buttons: [
      { extend: "excelHtml5", title: "Grading Rules", exportOptions: { columns: ":not(:last-child)" } },
      { extend: "pdfHtml5", title: "Grading Rules", exportOptions: { columns: ":not(:last-child)" } },
    ],
    columns: [
      { data: "institution" },
      { data: "range" },
      { data: "grade" },
      { data: "point" },
      { data: "remark" },
      { data: "status" },
      { data: "actions", orderable: false, searchable: false },
    ],
  });

  $("#addGradingRuleBtn").on("click", function () {
    $("#gradingRuleModalTitle").text("Add Grading Rule");
    gradingLoadBootstrap(function () {
      resetGradingForm();
      gradingRuleModal.show();
    });
  });
});

$(document).on("click", ".editGradingRule", function () {
  const id = $(this).data("id");
  $("#gradingRuleModalTitle").text("Edit Grading Rule");

  $.getJSON("../api/admin/ajax/results/getGradingRule.php", { id }, function (res) {
    if (!res.status) {
      Swal.fire("Error", "Unable to load grading rule", "error");
      return;
    }

    gradingLoadBootstrap(function () {
      resetGradingForm();
      const rule = res.rule || {};

      $("#grading_rule_id").val(rule.id || "");
      $("#grading_institution_id").val(rule.institution_id || "");
      $("#min_score").val(rule.min_score || "");
      $("#max_score").val(rule.max_score || "");
      $("#letter_grade").val(rule.letter_grade || "");
      $("#grade_point").val(rule.grade_point || "");
      $("#grading_status").val(String(rule.status ?? 1));
      $("#grading_remark").val(rule.remark || "");

      gradingRuleModal.show();
    });
  });
});

$(document).on("submit", "#gradingRuleForm", function (e) {
  e.preventDefault();

  const formData = new FormData(this);
  formData.append("csrf_token", window.gradingRuleManager.csrf.save);

  Swal.fire({
    title: "Saving...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: "../api/admin/ajax/results/saveGradingRule.php",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (res) {
      gradingUpdateCsrf(res.csrf_token);
      Swal.close();

      if (res.status) {
        gradingRuleModal.hide();
        gradingRulesTable.ajax.reload(null, false);
        Swal.fire("Success", res.message, "success");
      } else {
        Swal.fire("Error", res.message || "Unable to save grading rule", "error");
      }
    },
    error: function () {
      Swal.close();
      Swal.fire("Error", "Server error occurred", "error");
    },
  });
});
