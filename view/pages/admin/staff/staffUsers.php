<?php
$staffCounts = [
    'total' => (int)$model->countRows('admins'),
    'active' => (int)$model->countRows('admins', ['where' => ['ix_active' => 1]]),
    'inactive' => (int)$model->countRows('admins', ['where' => ['ix_active' => 0]])
];

$staffTokens = [
    'save' => $utility->generateCsrf('staff_save'),
    'toggle' => $utility->generateCsrf('staff_toggle'),
    'reset' => $utility->generateCsrf('staff_reset')
];
?>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card mb-0">
            <div class="card-body">
                <span class="text-muted">Staff Users</span>
                <h3 class="mb-0"><?= $staffCounts['total']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-0">
            <div class="card-body">
                <span class="text-muted">Active</span>
                <h3 class="mb-0 text-success"><?= $staffCounts['active']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-0">
            <div class="card-body">
                <span class="text-muted">Disabled</span>
                <h3 class="mb-0 text-danger"><?= $staffCounts['inactive']; ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Staff/Admin Users</h5>
                <button class="btn btn-primary" id="addStaffBtn">
                    <i class="ph ph-user-plus"></i> Add Staff
                </button>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="staffTable" class="table table-striped table-bordered dataTable w-100">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Institution Scope</th>
                                <th>Department Scope</th>
                                <th>Status</th>
                                <th>Last Login</th>
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

<div class="modal fade" id="staffModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form id="staffForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="staffModalTitle">Add Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="staff_id" name="id">

                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#staffProfileTab" type="button">Profile</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#staffAccessTab" type="button">Roles & Scope</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="staffProfileTab">
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">Title</label>
                                    <input type="text" class="form-control" id="staff_title" name="title" placeholder="Dr">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="staff_fullname" name="fullname" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" id="staff_email" name="email" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Phone</label>
                                    <input type="text" class="form-control" id="staff_phone" name="phone">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Staff Number</label>
                                    <input type="text" class="form-control" id="staff_no" name="staff_no">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Password</label>
                                    <input type="text" class="form-control" id="staff_password" name="password" autocomplete="new-password">
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="staffAccessTab">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Roles</label>
                                    <select class="form-control" id="staff_roles" name="role_ids[]" multiple size="8" required></select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Scope Type</label>
                                    <select class="form-control mb-3" id="scope_type" name="scope_type">
                                        <option value="global">Global</option>
                                        <option value="institution">Institution</option>
                                        <option value="programme">Programme</option>
                                        <option value="department">Department</option>
                                        <option value="level">Level</option>
                                        <option value="lecturer">Lecturer</option>
                                    </select>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Institution</label>
                                            <select class="form-control" id="scope_institution_id" name="institution_id"></select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Programme</label>
                                            <select class="form-control" id="scope_programme_id" name="programme_id"></select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Department</label>
                                            <select class="form-control" id="scope_department_id" name="department_id"></select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Level</label>
                                            <select class="form-control" id="scope_level_id" name="level_id"></select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ph ph-floppy-disk"></i> Save Staff
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="staffActivityModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Staff Activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>IP Address</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody id="staffActivityRows"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.staffAdminConfig = {
        csrf: <?= json_encode($staffTokens); ?>
    };
</script>
