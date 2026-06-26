let staffTable;
let staffModal;
let staffActivityModal;

function staffEscape(value) {
  return $("<div>").text(value || "").html();
}

function staffSetOptions(selector, rows, labelKey = "name", selected = "") {
  let html = "<option value=''>Select</option>";

  (rows || []).forEach((row) => {
    const isSelected = String(row.id) === String(selected) ? "selected" : "";
    html += `<option value="${row.id}" ${isSelected}>${staffEscape(row[labelKey])}</option>`;
  });

  $(selector).html(html);
}

function staffUpdateCsrf(action, token) {
  if (token && window.staffAdminConfig && window.staffAdminConfig.csrf) {
    window.staffAdminConfig.csrf[action] = token;
  }
}

function staffLoadBootstrap(callback = null) {
  $.getJSON("../api/admin/ajax/staff/bootstrap.php", function (res) {
    if (!res.status) {
      Swal.fire("Error", res.message || "Unable to load staff setup", "error");
      return;
    }

    let roles = "";
    (res.roles || []).forEach((role) => {
      roles += `<option value="${role.id}">${staffEscape(role.name)}</option>`;
    });

    $("#staff_roles").html(roles);
    staffSetOptions("#scope_institution_id", res.institutions || []);

    if (callback) callback(res);
  });
}

function staffLoadProgrammes(institutionId, selected = "", callback = null) {
  staffSetOptions("#scope_programme_id", []);
  staffSetOptions("#scope_department_id", []);
  staffSetOptions("#scope_level_id", []);

  if (!institutionId) {
    if (callback) callback();
    return;
  }

  $.getJSON(
    "../api/admin/ajax/endpoint/getProgrammesByInstitution.php",
    { institution_id: institutionId },
    function (res) {
      staffSetOptions("#scope_programme_id", res.data || [], "name", selected);
      if (callback) callback();
    }
  );
}

function staffLoadDepartments(programmeId, selected = "", callback = null) {
  staffSetOptions("#scope_department_id", []);
  staffSetOptions("#scope_level_id", []);

  if (!programmeId) {
    if (callback) callback();
    return;
  }

  $.getJSON(
    "../api/admin/ajax/endpoint/getDepartmentsByProgramme.php",
    { programme_id: programmeId },
    function (res) {
      staffSetOptions("#scope_department_id", res.data || [], "name", selected);
      if (callback) callback();
    }
  );
}

function staffLoadLevels(departmentId, selected = "", callback = null) {
  staffSetOptions("#scope_level_id", []);

  if (!departmentId) {
    if (callback) callback();
    return;
  }

  $.getJSON(
    "../api/admin/ajax/endpoint/getLevelsByDepartment.php",
    { department_id: departmentId },
    function (res) {
      staffSetOptions("#scope_level_id", res.data || [], "name", selected);
      if (callback) callback();
    }
  );
}

function staffApplyScopeState() {
  const type = $("#scope_type").val();

  $("#scope_institution_id").prop("disabled", type === "global");
  $("#scope_programme_id").prop("disabled", !["programme", "department", "level"].includes(type));
  $("#scope_department_id").prop("disabled", !["department", "level", "lecturer"].includes(type));
  $("#scope_level_id").prop("disabled", type !== "level");
}

function staffResetForm() {
  $("#staffForm")[0].reset();
  $("#staff_id").val("");
  $("#staff_roles").val([]);
  $("#scope_type").val("global");
  staffSetOptions("#scope_programme_id", []);
  staffSetOptions("#scope_department_id", []);
  staffSetOptions("#scope_level_id", []);
  staffApplyScopeState();
}

$(document).ready(function () {
  staffModal = new bootstrap.Modal(document.getElementById("staffModal"));
  staffActivityModal = new bootstrap.Modal(document.getElementById("staffActivityModal"));

  staffTable = $("#staffTable").DataTable({
    ajax: {
      url: "../api/admin/ajax/staff/fetchStaff.php",
      dataSrc: "data",
    },
    dom: "Bfrtip",
    buttons: [
      {
        extend: "excelHtml5",
        title: "Staff Users",
        exportOptions: { columns: ":not(:last-child)" },
      },
      {
        extend: "pdfHtml5",
        title: "Staff Users",
        exportOptions: { columns: ":not(:last-child)" },
      },
    ],
    columns: [
      { data: "name" },
      { data: "email" },
      { data: "phone" },
      { data: "roles" },
      { data: "institution" },
      { data: "department" },
      { data: "status" },
      { data: "last_login" },
      { data: "actions", orderable: false, searchable: false },
    ],
  });

  $("#addStaffBtn").on("click", function () {
    $("#staffModalTitle").text("Add Staff");
    staffLoadBootstrap(function () {
      staffResetForm();
      staffModal.show();
    });
  });
});

$(document).on("change", "#scope_type", staffApplyScopeState);

$(document).on("change", "#scope_institution_id", function () {
  staffLoadProgrammes($(this).val());
});

$(document).on("change", "#scope_programme_id", function () {
  staffLoadDepartments($(this).val());
});

$(document).on("change", "#scope_department_id", function () {
  staffLoadLevels($(this).val());
});

$(document).on("click", ".editStaff", function () {
  const id = $(this).data("id");
  $("#staffModalTitle").text("Edit Staff");

  $.getJSON("../api/admin/ajax/staff/getStaff.php", { id }, function (res) {
    if (!res.status) {
      Swal.fire("Error", res.message || "Unable to load staff user", "error");
      return;
    }

    staffLoadBootstrap(function () {
      staffResetForm();

      const staff = res.staff || {};
      const scope = res.scope || {};

      $("#staff_id").val(staff.id || "");
      $("#staff_title").val(staff.title || "");
      $("#staff_fullname").val(staff.fullname || "");
      $("#staff_email").val(staff.email || "");
      $("#staff_phone").val(staff.phone || "");
      $("#staff_no").val(staff.staff_no || "");
      $("#staff_password").val("");
      $("#staff_roles").val((res.role_ids || []).map(String));
      $("#scope_type").val(scope.scope_type || "global");
      $("#scope_institution_id").val(scope.institution_id || "");

      staffLoadProgrammes(scope.institution_id, scope.programme_id, function () {
        staffLoadDepartments(scope.programme_id, scope.department_id, function () {
          staffLoadLevels(scope.department_id, scope.level_id, function () {
            staffApplyScopeState();
            staffModal.show();
          });
        });
      });
    });
  });
});

$(document).on("submit", "#staffForm", function (e) {
  e.preventDefault();

  const formData = new FormData(this);
  formData.append("csrf_token", window.staffAdminConfig.csrf.save);

  Swal.fire({
    title: "Saving...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: "../api/admin/ajax/staff/saveStaff.php",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (res) {
      staffUpdateCsrf("save", res.csrf_token);
      Swal.close();

      if (res.status) {
        staffModal.hide();
        staffTable.ajax.reload(null, false);

        const message = res.generated_password
          ? `${res.message} Temporary password: ${res.generated_password}`
          : res.message;

        Swal.fire("Success", message, "success");
      } else {
        Swal.fire("Error", res.message || "Unable to save staff user", "error");
      }
    },
    error: function () {
      Swal.close();
      Swal.fire("Error", "Server error occurred", "error");
    },
  });
});

$(document).on("click", ".toggleStaff", function () {
  const id = $(this).data("id");

  Swal.fire({
    title: "Change staff status?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes",
  }).then((result) => {
    if (!result.isConfirmed) return;

    $.post(
      "../api/admin/ajax/staff/toggleStaff.php",
      {
        id,
        csrf_token: window.staffAdminConfig.csrf.toggle,
      },
      function (res) {
        staffUpdateCsrf("toggle", res.csrf_token);

        if (res.status) {
          Swal.fire("Updated", res.message, "success");
          staffTable.ajax.reload(null, false);
        } else {
          Swal.fire("Error", res.message || "Unable to update staff status", "error");
        }
      },
      "json"
    );
  });
});

$(document).on("click", ".resetStaffPassword", function () {
  const id = $(this).data("id");

  Swal.fire({
    title: "Reset password?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Reset",
  }).then((result) => {
    if (!result.isConfirmed) return;

    $.post(
      "../api/admin/ajax/staff/resetPassword.php",
      {
        id,
        csrf_token: window.staffAdminConfig.csrf.reset,
      },
      function (res) {
        staffUpdateCsrf("reset", res.csrf_token);

        if (res.status) {
          Swal.fire("Password Reset", `Temporary password: ${res.temporary_password}`, "success");
        } else {
          Swal.fire("Error", res.message || "Unable to reset password", "error");
        }
      },
      "json"
    );
  });
});

$(document).on("click", ".viewStaffActivity", function () {
  const id = $(this).data("id");

  $("#staffActivityRows").html("<tr><td colspan='3' class='text-center'>Loading...</td></tr>");
  staffActivityModal.show();

  $.getJSON("../api/admin/ajax/staff/fetchActivity.php", { id }, function (res) {
    if (!res.status || !(res.data || []).length) {
      $("#staffActivityRows").html("<tr><td colspan='3' class='text-center'>No activity found</td></tr>");
      return;
    }

    let rows = "";
    (res.data || []).forEach((log) => {
      rows += `
        <tr>
          <td>${log.action}</td>
          <td>${log.ip_address}</td>
          <td>${log.date}</td>
        </tr>
      `;
    });

    $("#staffActivityRows").html(rows);
  });
});
