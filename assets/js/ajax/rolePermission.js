let rolesTable;
let roleModal;
let roleUsersModal;
let permissionCache = {};

function roleEscape(value) {
  return $("<div>").text(value || "").html();
}

function roleUpdateCsrf(action, token) {
  if (token && window.rolePermissionConfig && window.rolePermissionConfig.csrf) {
    window.rolePermissionConfig.csrf[action] = token;
  }
}

function renderPermissionGroups(selected = []) {
  const selectedSet = new Set((selected || []).map(String));
  let html = "";

  Object.keys(permissionCache).forEach((module) => {
    html += `
      <div class="col-md-6">
        <div class="border rounded p-3 h-100">
          <h6 class="mb-3">${roleEscape(module)}</h6>
    `;

    (permissionCache[module] || []).forEach((permission) => {
      const checked = selectedSet.has(String(permission.id)) ? "checked" : "";
      html += `
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" name="permission_ids[]" value="${permission.id}" id="perm_${permission.id}" ${checked}>
          <label class="form-check-label" for="perm_${permission.id}">
            ${roleEscape(permission.name)}
          </label>
        </div>
      `;
    });

    html += `
        </div>
      </div>
    `;
  });

  $("#permissionGroups").html(html);
}

function loadPermissionBootstrap(callback = null) {
  $.getJSON("../api/admin/ajax/roles/bootstrap.php", function (res) {
    if (!res.status) {
      Swal.fire("Error", res.message || "Unable to load permissions", "error");
      return;
    }

    permissionCache = res.permissions || {};
    if (callback) callback();
  });
}

function resetRoleForm() {
  $("#roleForm")[0].reset();
  $("#role_id").val("");
  $("#role_slug").prop("readonly", false);
  renderPermissionGroups([]);
}

$(document).ready(function () {
  roleModal = new bootstrap.Modal(document.getElementById("roleModal"));
  roleUsersModal = new bootstrap.Modal(document.getElementById("roleUsersModal"));

  rolesTable = $("#rolesTable").DataTable({
    ajax: {
      url: "../api/admin/ajax/roles/fetchRoles.php",
      dataSrc: "data",
    },
    dom: "Bfrtip",
    buttons: [
      {
        extend: "excelHtml5",
        title: "Roles",
        exportOptions: { columns: ":not(:last-child)" },
      },
      {
        extend: "pdfHtml5",
        title: "Roles",
        exportOptions: { columns: ":not(:last-child)" },
      },
    ],
    columns: [
      { data: "name" },
      { data: "slug" },
      { data: "permission_count" },
      { data: "user_count" },
      { data: "status" },
      { data: "actions", orderable: false, searchable: false },
    ],
  });

  $("#addRoleBtn").on("click", function () {
    $("#roleModalTitle").text("Add Role");
    loadPermissionBootstrap(function () {
      resetRoleForm();
      roleModal.show();
    });
  });
});

$(document).on("input", "#role_name", function () {
  if ($("#role_id").val() || $("#role_slug").val()) return;

  const slug = $(this)
    .val()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "_")
    .replace(/^_+|_+$/g, "");

  $("#role_slug").val(slug);
});

$(document).on("click", ".editRole", function () {
  const id = $(this).data("id");
  $("#roleModalTitle").text("Edit Role");

  $.getJSON("../api/admin/ajax/roles/getRole.php", { id }, function (res) {
    if (!res.status) {
      Swal.fire("Error", res.message || "Unable to load role", "error");
      return;
    }

    loadPermissionBootstrap(function () {
      resetRoleForm();

      const role = res.role || {};
      $("#role_id").val(role.id || "");
      $("#role_name").val(role.name || "");
      $("#role_slug").val(role.slug || "").prop("readonly", !!role.id);
      $("#role_status").val(String(role.status ?? 1));
      $("#role_description").val(role.description || "");
      renderPermissionGroups(res.permission_ids || []);

      roleModal.show();
    });
  });
});

$(document).on("submit", "#roleForm", function (e) {
  e.preventDefault();

  const formData = new FormData(this);
  formData.append("csrf_token", window.rolePermissionConfig.csrf.save);

  Swal.fire({
    title: "Saving...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: "../api/admin/ajax/roles/saveRole.php",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (res) {
      roleUpdateCsrf("save", res.csrf_token);
      Swal.close();

      if (res.status) {
        roleModal.hide();
        rolesTable.ajax.reload(null, false);
        Swal.fire("Success", res.message, "success");
      } else {
        Swal.fire("Error", res.message || "Unable to save role", "error");
      }
    },
    error: function () {
      Swal.close();
      Swal.fire("Error", "Server error occurred", "error");
    },
  });
});

$(document).on("click", ".toggleRole", function () {
  const id = $(this).data("id");

  Swal.fire({
    title: "Change role status?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes",
  }).then((result) => {
    if (!result.isConfirmed) return;

    $.post(
      "../api/admin/ajax/roles/toggleRole.php",
      {
        id,
        csrf_token: window.rolePermissionConfig.csrf.toggle,
      },
      function (res) {
        roleUpdateCsrf("toggle", res.csrf_token);

        if (res.status) {
          Swal.fire("Updated", res.message, "success");
          rolesTable.ajax.reload(null, false);
        } else {
          Swal.fire("Error", res.message || "Unable to update role", "error");
        }
      },
      "json"
    );
  });
});

$(document).on("click", ".viewRoleUsers", function () {
  const id = $(this).data("id");
  $("#roleUsersRows").html("<tr><td colspan='3' class='text-center'>Loading...</td></tr>");
  roleUsersModal.show();

  $.getJSON("../api/admin/ajax/roles/roleUsers.php", { id }, function (res) {
    if (!res.status || !(res.data || []).length) {
      $("#roleUsersRows").html("<tr><td colspan='3' class='text-center'>No users found</td></tr>");
      return;
    }

    let rows = "";
    (res.data || []).forEach((user) => {
      rows += `
        <tr>
          <td>${user.name}</td>
          <td>${user.email}</td>
          <td>${user.status}</td>
        </tr>
      `;
    });

    $("#roleUsersRows").html(rows);
  });
});
