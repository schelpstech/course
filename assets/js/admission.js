const toast = (icon, title) =>
  Swal.fire({
    toast: true,
    position: "top-end",
    icon,
    title,
    showConfirmButton: false,
    timer: 3500,
  });

$(document).on("submit", ".ajax-form", function (event) {
  event.preventDefault();
  const form = this;
  const button = $(form)
    .find('button[type="submit"], button:not([type])')
    .first();
  button.prop("disabled", true);

  $.ajax({
    url: $(form).data("endpoint"),
    method: "POST",
    data: new FormData(form),
    processData: false,
    contentType: false,
    success(response) {
      toast("success", response.message || "Saved");
      if (response.redirect) {
        window.location.href = response.redirect;
        return;
      }
      if (
        $(form).data("endpoint").includes("save-step") ||
        $(form).data("endpoint").includes("submit-application")
      ) {
        setTimeout(() => window.location.reload(), 700);
      }
    },
    error(xhr) {
      const response = xhr.responseJSON || {};
      toast("error", response.message || "Request failed");
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
    url: "api/admission/upload-document.php",
    method: "POST",
    data: new FormData(form),
    processData: false,
    contentType: false,
    success(response) {
      toast("success", response.message || "Uploaded");
      setTimeout(() => window.location.reload(), 700);
    },
    error(xhr) {
      const response = xhr.responseJSON || {};
      toast("error", response.message || "Upload failed");
    },
  });
});

$(document).on("submit", ".payment-form", function (event) {
  event.preventDefault();
  $.ajax({
    url: "api/admission/initialize-payment.php",
    method: "POST",
    data: new FormData(this),
    processData: false,
    contentType: false,
    success(response) {
      window.location.href = response.authorization_url;
    },
    error(xhr) {
      const response = xhr.responseJSON || {};
      toast("error", response.message || "Payment could not start");
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
    "api/admission/get-programmes.php",
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
    "api/admission/get-departments.php",
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
  loadProgrammes();
  toggleJambFields();
});

// STEP 1 SUCCESS
$(document).on('otp-request-success', function(e, email){

    $('#signupEmail').prop('readonly', true);

    $('#verifiedEmailDisplay').val(email);
    $('#finalVerifiedEmail').val(email);

    $('#step1').addClass('d-none');
    $('#step2').removeClass('d-none');

    $('#step2Indicator').addClass('active');
});

// STEP 2 SUCCESS
$(document).on('otp-verified-success', function(){

    $('#step2').addClass('d-none');
    $('#step3').removeClass('d-none');

    $('#step3Indicator').addClass('active');
});
