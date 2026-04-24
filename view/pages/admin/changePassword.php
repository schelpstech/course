<div class="row justify-content-center">
    <div class="col-md-6">

        <div class="card shadow-sm border-0">

            <!-- HEADER -->
            <div class="card-header bg-white border-0 text-center py-4">
                <h4 class="mb-1">Change Password</h4>
                <p class="text-muted mb-0">
                    Update your default password to continue
                </p>
            </div>

            <!-- BODY -->
            <div class="card-body px-4">

                <form id="changePasswordForm" action="../api/admin/change-password.php" method="POST">

                    <!-- CURRENT PASSWORD -->
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password"
                               name="current_password"
                               class="form-control form-control-lg"
                               required
                               autocomplete="off">
                    </div>

                    <!-- NEW PASSWORD -->
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password"
                               name="new_password"
                               class="form-control form-control-lg"
                               required
                               autocomplete="off">
                    </div>

                    <!-- CONFIRM PASSWORD -->
                    <div class="mb-4">
                        <label class="form-label">Confirm Password</label>
                        <input type="password"
                               name="confirm_password"
                               class="form-control form-control-lg"
                               required
                               autocomplete="off">
                    </div>

                    <!-- CSRF -->
                    <input type="hidden"
                           name="csrf_token"
                           value="<?= $utility->generateCsrf('admin_change_password'); ?>">

                    <!-- BUTTON -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg">
                            Update Password
                        </button>
                    </div>

                </form>

            </div>

        </div>

    </div>
</div>