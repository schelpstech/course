
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
                        <div class="col-md-6 off-set-3">
                            <input type="password" name="current_password" class="form-control" required placeholder=" " autocomplete="new-password">
                            <label>Current Password</label>
                        </div>
                    </div>
                    <div class="row justify-content-center">
                        <div class="col-md-6 off-set-3">
                            <input type="password" name="new_password" class="form-control" required placeholder=" " autocomplete="new-password">
                            <label>New Password</label>
                        </div>
                    </div>

                    <div class="row justify-content-center">
                        <div class="col-md-6 off-set-3">
                            <input type="password" name="confirm_password" class="form-control" required placeholder=" " autocomplete="new-password">
                            <label>Confirm Password</label>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="row">
                            <input type="hidden" name="csrf_token" value="<?= $utility->generateCsrf('change-password'); ?>">
                            <div class="col-md-6 offset-md-3 mb-3 text-center">
                                <button type="submit" class="btn btn-success px-4">
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