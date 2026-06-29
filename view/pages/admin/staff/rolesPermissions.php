<?php
$roleCount = $rbac->tableExists('roles') ? count($rbac->getRoles(false)) : 0;
$permissionGroups = $rbac->getPermissionsGrouped();
$permissionCount = array_sum(array_map('count', $permissionGroups));

$roleTokens = [
    'save' => $utility->generateCsrf('role_save'),
    'toggle' => $utility->generateCsrf('role_toggle')
];
?>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card mb-0">
            <div class="card-body">
                <span class="text-muted">Roles</span>
                <h3 class="mb-0"><?= (int)$roleCount; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-0">
            <div class="card-body">
                <span class="text-muted">Permissions</span>
                <h3 class="mb-0"><?= (int)$permissionCount; ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Roles & Permissions</h5>
                <button class="btn btn-primary" id="addRoleBtn">
                    <i class="ph ph-plus"></i> Add Role
                </button>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="rolesTable" class="table table-striped table-bordered dataTable w-100">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Permissions</th>
                                <th>Users</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="roleModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable role-permission-dialog">
        <div class="modal-content">
            <form id="roleForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="roleModalTitle">Add Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body role-permission-body">
                    <input type="hidden" id="role_id" name="id">

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Role Name</label>
                            <input type="text" class="form-control" id="role_name" name="name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Slug</label>
                            <input type="text" class="form-control" id="role_slug" name="slug">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-control" id="role_status" name="status">
                                <option value="1">Active</option>
                                <option value="0">Disabled</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="role_description" name="description" rows="2"></textarea>
                        </div>
                    </div>

                    <div id="permissionGroups" class="row g-3 role-permission-groups"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ph ph-floppy-disk"></i> Save Role
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="roleUsersModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Users Under Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="roleUsersRows"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.rolePermissionConfig = {
        csrf: <?= json_encode($roleTokens); ?>
    };
</script>
