const toast = (icon, title) =>
  Swal.fire({
    toast: true,
    position: "top-end",
    icon,
    title,
    showConfirmButton: false,
    timer: 3500,
  });

function getAdmissionApiBase() {
  const script = Array.from(document.scripts).find((item) =>
    item.src.includes("/assets/js/admission.js"),
  );

  if (script?.src) {
    return new URL("../../api/admission/", script.src).href;
  }

  return new URL("../api/admission/", window.location.href).href;
}

function getAjaxErrorMessage(xhr, fallback) {
  if (xhr.responseJSON?.message) {
    return xhr.responseJSON.message;
  }

  if (xhr.responseText) {
    try {
      const parsed = JSON.parse(xhr.responseText);
      if (parsed.message) return parsed.message;
    } catch (error) {
      if (xhr.status === 404) {
        return "Admission payment endpoint was not found. Check the local server URL.";
      }
    }
  }

  return fallback;
}

const admissionApiBase = getAdmissionApiBase();

function activateAdmissionPane(paneId) {
  if (!paneId) return;

  const trigger = $(`[data-bs-target="#${paneId}"]`);
  if (!trigger.length) return;

  trigger.removeClass("disabled").prop("disabled", false);
  bootstrap.Tab.getOrCreateInstance(trigger[0]).show();
}

function rememberPaneAfterReload(paneId) {
  if (paneId) {
    sessionStorage.setItem("admissionActivePane", paneId);
  }
}

function reindexAcademicEntries() {
  $("#academicHistoryBlocks .academic-entry").each(function (index) {
    $(this).attr("data-entry-index", index);
    $(this).find(".entry-number").text(index + 1);
    $(this)
      .find("[name]")
      .each(function () {
        this.name = this.name.replace(/history\[\d+\]/, `history[${index}]`);
      });
  });

  $(".remove-academic-entry").prop(
    "disabled",
    $("#academicHistoryBlocks .academic-entry").length <= 1,
  );
}

function syncOlevelSittings() {
  if ($("#olevelForm").data("locked") === 1) return;

  const count = Number($("#sittingCount").val() || 1);

  $(".olevel-sitting-card").each(function () {
    const index = Number($(this).data("sitting-index"));
    const active = index < count;
    $(this).toggleClass("d-none", !active);
    $(this).find(".olevel-field").prop("disabled", !active);
  });
}

$(document).on("submit", ".ajax-form", function (event) {
  event.preventDefault();
  const form = this;
  const submitter = event.originalEvent?.submitter;
  const saveMode = submitter?.dataset?.saveMode || "";
  const nextPane = submitter?.dataset?.nextPane || "";
  const button = submitter
    ? $(submitter)
    : $(form).find('button[type="submit"], button:not([type])').first();
  button.prop("disabled", true);

  $.ajax({
    url: $(form).data("endpoint"),
    method: "POST",
    data: new FormData(form),
    processData: false,
    contentType: false,
    success(response) {
      toast("success", response.message || "Saved");

      const endpoint = $(form).data("endpoint");

      // ======================================
      // STEP 1 -> STEP 2
      // ======================================
      if (endpoint.includes("request-otp.php")) {
        const email = $("#signupEmail").val().trim();

        $("#signupEmail").prop("readonly", true);

        $("#verifiedEmailDisplay").val(email);
        $("#finalVerifiedEmail").val(email);

        $("#step1").addClass("d-none");
        $("#step2").removeClass("d-none");

        $("#step1Indicator").addClass("completed");
        $("#step2Indicator").addClass("active");

        return;
      }

      // ======================================
      // STEP 2 -> STEP 3
      // ======================================
      if (endpoint.includes("verify-otp.php")) {
        $("#step2").addClass("d-none");
        $("#step3").removeClass("d-none");

        $("#step2Indicator").addClass("completed");
        $("#step3Indicator").addClass("active");

        return;
      }

      // ======================================
      // CREATE ACCOUNT
      // ======================================
      if (response.redirect) {
        window.location.href = response.redirect;
        return;
      }

      // ======================================
      // FORM SAVE
      // ======================================
      if (endpoint.includes("save-step")) {
        rememberPaneAfterReload(saveMode === "continue" ? nextPane : $(".tab-pane.active").attr("id"));
        setTimeout(() => window.location.reload(), 650);
        return;
      }

      if (endpoint.includes("submit-application")) {
        rememberPaneAfterReload("finalPane");
        setTimeout(() => window.location.reload(), 700);
      }
    },

    error(xhr) {
      toast("error", getAjaxErrorMessage(xhr, "Request failed"));
    },
    complete() {
      button.prop("disabled", false);
    },
  });
});

$(document).on("submit", ".upload-form", function (event) {
  event.preventDefault();
  const form = this;
  $.ajax({
    url: admissionApiBase + "upload-document.php",
    method: "POST",
    data: new FormData(form),
    processData: false,
    contentType: false,
    success(response) {
      toast("success", response.message || "Uploaded");
      rememberPaneAfterReload("documentsPane");
      setTimeout(() => window.location.reload(), 700);
    },
    error(xhr) {
      toast("error", getAjaxErrorMessage(xhr, "Upload failed"));
    },
  });
});

$(document).on("submit", ".payment-form", function (event) {
  event.preventDefault();
  $.ajax({
    url: admissionApiBase + "initialize-payment.php",
    method: "POST",
    data: new FormData(this),
    processData: false,
    contentType: false,
    success(response) {
      window.location.href = response.authorization_url;
    },
    error(xhr) {
      toast("error", getAjaxErrorMessage(xhr, "Payment could not start"));
    },
  });
});

function setOptions(selector, rows, selected, placeholder) {
  const field = $(selector);
  field.html(`<option value="">${placeholder}</option>`);
  rows.forEach((row) => {
    const option = $("<option>").val(row.id).text(row.name);
    if (String(row.id) === String(selected)) option.prop("selected", true);
    field.append(option);
  });
}

function loadProgrammes() {
  const institutionId = $("#institutionSelect").val();
  const selectedProgramme = $("#programmeSelect").data("selected");
  if (!institutionId) return;
  $.getJSON(
    admissionApiBase + "get-programmes.php",
    { institution_id: institutionId },
    (response) => {
      setOptions(
        "#programmeSelect",
        response.data || [],
        selectedProgramme,
        "Select Programme",
      );
      loadDepartments();
    },
  );
}

function loadDepartments() {
  const programmeId = $("#programmeSelect").val();
  const selectedDepartment = $("#departmentSelect").data("selected");
  if (!programmeId) return;
  $.getJSON(
    admissionApiBase + "get-departments.php",
    { programme_id: programmeId },
    (response) => {
      setOptions(
        "#departmentSelect",
        response.data || [],
        selectedDepartment,
        "Select Department",
      );
    },
  );
}

function toggleJambFields() {
  const mode = $("#modeOfEntry").val();
  $(".jamb-field").toggle(mode === "JAMB UTME" || mode === "Direct Entry");
}

$("#institutionSelect").on("change", function () {
  $("#programmeSelect").data("selected", "");
  $("#departmentSelect").data("selected", "");
  loadProgrammes();
});
$("#programmeSelect").on("change", function () {
  $("#departmentSelect").data("selected", "");
  loadDepartments();
});
$("#modeOfEntry").on("change", toggleJambFields);

$(function () {
  const rememberedPane = sessionStorage.getItem("admissionActivePane");
  if (rememberedPane) {
    sessionStorage.removeItem("admissionActivePane");
    activateAdmissionPane(rememberedPane);
  }

  loadProgrammes();
  toggleJambFields();
  reindexAcademicEntries();
  syncOlevelSittings();
});

$(document).on("click", "#addAcademicEntry", function () {
  const template = $("#academicEntryTemplate").html();
  const nextIndex = $("#academicHistoryBlocks .academic-entry").length;

  $("#academicHistoryBlocks").append(
    template
      .replaceAll("__INDEX__", nextIndex)
      .replaceAll("__NUMBER__", nextIndex + 1),
  );

  reindexAcademicEntries();
});

$(document).on("click", ".remove-academic-entry", function () {
  if ($("#academicHistoryBlocks .academic-entry").length <= 1) return;
  $(this).closest(".academic-entry").remove();
  reindexAcademicEntries();
});

$(document).on("change", "#sittingCount", syncOlevelSittings);

$(document).on("click", ".add-subject-row", function () {
  const card = $(this).closest(".olevel-sitting-card");
  const sittingIndex = card.data("sitting-index");
  const template = $("#subjectRowTemplate").html();

  card.find(".subjectRows").append(
    template
      .replaceAll("__SUBJECT_NAME__", `sittings[${sittingIndex}][subjects][]`)
      .replaceAll("__GRADE_NAME__", `sittings[${sittingIndex}][grades][]`),
  );
});

$(document).on("click", ".remove-subject-row", function () {
  const rows = $(this).closest("tbody").find(".subject-row");
  if (rows.length <= 5) {
    toast("info", "At least five subjects are required.");
    return;
  }

  $(this).closest(".subject-row").remove();
});

$(document).on("click", ".advance-only", function () {
  activateAdmissionPane($(this).data("next-pane"));
});

// STEP 1 SUCCESS
$(document).on("otp-request-success", function (e, email) {
  $("#signupEmail").prop("readonly", true);

  $("#verifiedEmailDisplay").val(email);
  $("#finalVerifiedEmail").val(email);

  $("#step1").addClass("d-none");
  $("#step2").removeClass("d-none");

  $("#step2Indicator").addClass("active");
});

// STEP 2 SUCCESS
$(document).on("otp-verified-success", function () {
  $("#step2").addClass("d-none");
  $("#step3").removeClass("d-none");

  $("#step3Indicator").addClass("active");
});
