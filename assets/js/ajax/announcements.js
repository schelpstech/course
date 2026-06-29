let announcementsTable;
let announcementModal;
let announcementBootstrapLoaded = false;

const announcementTargetRank = {
  all: 0,
  institution: 1,
  programme: 2,
  department: 3,
  level: 4,
};

function announcementEscape(value) {
  return $("<div>").text(value || "").html();
}

function announcementSetOptions(selector, rows, selected = "", placeholder = "Select") {
  let html = placeholder === null ? "" : `<option value="">${announcementEscape(placeholder)}</option>`;

  (rows || []).forEach((row) => {
    const label = row.name || row.code || row.title || "";
    const selectedAttr = String(row.id) === String(selected || "") ? "selected" : "";
    html += `<option value="${announcementEscape(row.id)}" ${selectedAttr}>${announcementEscape(label)}</option>`;
  });

  $(selector).html(html);
}

function announcementUpdateCsrf(action, token) {
  if (token && window.announcementConfig && window.announcementConfig.csrf) {
    window.announcementConfig.csrf[action] = token;
  }
}

function announcementInputDate(value) {
  if (!value) return "";
  return String(value).replace(" ", "T").substring(0, 16);
}

function announcementDefaultDate(offsetDays = 0) {
  const date = new Date();
  date.setDate(date.getDate() + offsetDays);
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
  return date.toISOString().slice(0, 16);
}

function announcementLoadBootstrap(callback = null) {
  if (announcementBootstrapLoaded) {
    if (callback) callback();
    return;
  }

  $.getJSON("../api/admin/ajax/announcements/bootstrap.php", function (res) {
    if (!res.status) {
      Swal.fire("Error", res.message || "Unable to load announcement setup", "error");
      return;
    }

    announcementSetOptions("#announcement_institution_id", res.institutions || [], "", "Select Institution");
    announcementBootstrapLoaded = true;

    if (callback) callback();
  });
}

function announcementLoadProgrammes(institutionId, selected = "", callback = null) {
  announcementSetOptions("#announcement_programme_id", [], "", "Select Programme");
  announcementSetOptions("#announcement_department_id", [], "", "Select Department");
  announcementSetOptions("#announcement_level_id", [], "", "Select Level");

  if (!institutionId) {
    if (callback) callback();
    return;
  }

  $.getJSON(
    "../api/admin/ajax/endpoint/getProgrammesByInstitution.php",
    { institution_id: institutionId },
    function (res) {
      announcementSetOptions("#announcement_programme_id", res.data || [], selected, "Select Programme");
      if (callback) callback();
    }
  );
}

function announcementLoadDepartments(programmeId, selected = "", callback = null) {
  announcementSetOptions("#announcement_department_id", [], "", "Select Department");
  announcementSetOptions("#announcement_level_id", [], "", "Select Level");

  if (!programmeId) {
    if (callback) callback();
    return;
  }

  $.getJSON(
    "../api/admin/ajax/endpoint/getDepartmentsByProgramme.php",
    { programme_id: programmeId },
    function (res) {
      announcementSetOptions("#announcement_department_id", res.data || [], selected, "Select Department");
      if (callback) callback();
    }
  );
}

function announcementLoadLevels(departmentId, selected = "", callback = null) {
  announcementSetOptions("#announcement_level_id", [], "", "Select Level");

  if (!departmentId) {
    if (callback) callback();
    return;
  }

  $.getJSON(
    "../api/admin/ajax/endpoint/getLevelsByDepartment.php",
    { department_id: departmentId },
    function (res) {
      announcementSetOptions("#announcement_level_id", res.data || [], selected, "Select Level");
      if (callback) callback();
    }
  );
}

function announcementSyncTargetFields() {
  const visibility = $("#announcement_visibility").val() || "all";
  const rank = announcementTargetRank[visibility] || 0;

  $(".announcement-target-field").each(function () {
    const level = $(this).data("target-level");
    const fieldRank = announcementTargetRank[level] || 0;
    const visible = fieldRank <= rank && fieldRank > 0;
    const select = $(this).find("select");

    $(this).toggleClass("d-none", !visible);
    select.prop("disabled", !visible).prop("required", visible);
  });
}

function resetAnnouncementForm() {
  $("#announcementForm")[0].reset();
  $("#announcement_id").val("");
  $("#announcement_visibility").val("all");
  $("#announcement_is_active").val("1");
  $("#announcement_must_read").prop("checked", true);
  $("#announcement_start_date").val(announcementDefaultDate(0));
  $("#announcement_end_date").val(announcementDefaultDate(7));
  announcementSetOptions("#announcement_programme_id", [], "", "Select Programme");
  announcementSetOptions("#announcement_department_id", [], "", "Select Department");
  announcementSetOptions("#announcement_level_id", [], "", "Select Level");
  announcementSyncTargetFields();
}

$(document).ready(function () {
  announcementModal = new bootstrap.Modal(document.getElementById("announcementModal"));

  announcementLoadBootstrap(function () {
    resetAnnouncementForm();
  });

  announcementsTable = $("#announcementsTable").DataTable({
    ajax: {
      url: "../api/admin/ajax/announcements/fetchAnnouncements.php",
      dataSrc: "data",
    },
    dom: "Bfrtip",
    buttons: [
      { extend: "excelHtml5", title: "Announcements", exportOptions: { columns: ":not(:last-child)" } },
      { extend: "pdfHtml5", title: "Announcements", exportOptions: { columns: ":not(:last-child)" } },
    ],
    columns: [
      { data: "title" },
      { data: "window" },
      { data: "visibility" },
      { data: "target" },
      { data: "read_count" },
      { data: "status" },
      { data: "must_read" },
      { data: "actions", orderable: false, searchable: false },
    ],
  });
});

$(document).on("click", "#addAnnouncementBtn", function () {
  $("#announcementModalTitle").text("New Notice");
  announcementLoadBootstrap(function () {
    resetAnnouncementForm();
    announcementModal.show();
  });
});

$(document).on("change", "#announcement_visibility", announcementSyncTargetFields);

$(document).on("change", "#announcement_institution_id", function () {
  announcementLoadProgrammes($(this).val());
});

$(document).on("change", "#announcement_programme_id", function () {
  announcementLoadDepartments($(this).val());
});

$(document).on("change", "#announcement_department_id", function () {
  announcementLoadLevels($(this).val());
});

$(document).on("click", ".editAnnouncement", function () {
  const id = $(this).data("id");

  $.getJSON("../api/admin/ajax/announcements/getAnnouncement.php", { id }, function (res) {
    if (!res.status) {
      Swal.fire("Error", res.message || "Unable to load announcement", "error");
      return;
    }

    announcementLoadBootstrap(function () {
      resetAnnouncementForm();

      const item = res.announcement || {};
      $("#announcementModalTitle").text("Edit Notice");
      $("#announcement_id").val(item.id || "");
      $("#announcement_title").val(item.title || "");
      $("#announcement_body").val(item.body || "");
      $("#announcement_visibility").val(item.visibility || "all");
      $("#announcement_start_date").val(announcementInputDate(item.start_date));
      $("#announcement_end_date").val(announcementInputDate(item.end_date));
      $("#announcement_is_active").val(String(item.is_active ?? 1));
      $("#announcement_must_read").prop("checked", String(item.must_read ?? 1) === "1");
      announcementSyncTargetFields();

      $("#announcement_institution_id").val(item.institution_id || "");
      announcementLoadProgrammes(item.institution_id, item.programme_id, function () {
        announcementLoadDepartments(item.programme_id, item.department_id, function () {
          announcementLoadLevels(item.department_id, item.level_id, function () {
            announcementModal.show();
          });
        });
      });
    });
  });
});

$(document).on("submit", "#announcementForm", function (e) {
  e.preventDefault();

  const formData = new FormData(this);
  formData.append("csrf_token", window.announcementConfig.csrf.save);

  Swal.fire({
    title: "Saving...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: "../api/admin/ajax/announcements/saveAnnouncement.php",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (res) {
      announcementUpdateCsrf("save", res.csrf_token);
      Swal.close();

      if (res.status) {
        announcementModal.hide();
        announcementsTable.ajax.reload(null, false);
        Swal.fire("Success", res.message, "success");
      } else {
        Swal.fire("Error", res.message || "Unable to save announcement", "error");
      }
    },
    error: function () {
      Swal.close();
      Swal.fire("Error", "Server error occurred", "error");
    },
  });
});

$(document).on("click", ".toggleAnnouncement", function () {
  const id = $(this).data("id");

  Swal.fire({
    title: "Update notice status?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes",
  }).then((result) => {
    if (!result.isConfirmed) return;

    $.post(
      "../api/admin/ajax/announcements/toggleAnnouncement.php",
      {
        id,
        csrf_token: window.announcementConfig.csrf.toggle,
      },
      function (res) {
        announcementUpdateCsrf("toggle", res.csrf_token);

        if (res.status) {
          announcementsTable.ajax.reload(null, false);
          Swal.fire("Updated", res.message, "success");
        } else {
          Swal.fire("Error", res.message || "Unable to update announcement", "error");
        }
      },
      "json"
    );
  });
});
