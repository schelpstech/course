
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header">
                <h5>Change Password</h5>
                <p>You must update your default password before continuing</p>
            </div>
            <div class="card-body">
                <form id="paymentForm" action="../api/change-password.php" method="POST">

                    <div class="row justify-content-center">
                        <div class="col-lg-7 col-md-9">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required autocomplete="new-password">
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required autocomplete="new-password">
                            </div>

                            <input type="hidden" name="csrf_token" value="<?= $utility->generateCsrf('change-password'); ?>">
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-success px-4">
                                    <i class="ti ti-lock-check"></i>
                                    Update Password
                                </button>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
