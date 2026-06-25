<?php
include './partials/header.php';
$applicantId = (int) ($_SESSION['admission_applicant_id'] ?? 0);
if ($applicantId) {
    header("Location: dashboard.php");
    exit;
}
$signupEmail = $_SESSION['signup_email'] ?? '';
$signupVerified = !empty($_SESSION['signup_verified']);

$currentStep = 1;
if ($signupEmail) {
    $currentStep = 2;
}
if ($signupVerified) {
    $currentStep = 3;
}
$activeSession = $admission->activeSession();
require_once './helpers/admission_helper.php';
include './partials/topbar.php'; ?>

<main class="auth-panel">
    <div class="row g-0 surface overflow-hidden">
        <div class="col-lg-5 auth-aside p-4 p-lg-5">
            <div class="hero-badge mb-3">
                <?= $activeSession ? h($activeSession['academic_session_name']) : 'Unavailable' ?> ADMISSION NOW OPEN
            </div>

            <h1 class="hero-title">
                Start Your Academic Journey
            </h1>

            <p class="hero-text">
                Apply online into our University, Polytechnic and
                College of Education through a secure and seamless
                admission platform.
            </p>

            <div class="admission-features mt-4">
                <div><i class="ti ti-check"></i> Secure Online Application</div>
                <div><i class="ti ti-check"></i> Real-Time Application Tracking</div>
                <div><i class="ti ti-check"></i> Instant Email Verification</div>
                <div><i class="ti ti-check"></i> Online Acceptance Fee Payment</div>
            </div>
            <div class="session-card mt-4">
                <span class="session-badge">
                    ACTIVE SESSION
                </span>

                <div class="mt-3">
                    <small>Academic Session</small>
                    <h4><?= $activeSession ? h($activeSession['academic_session_name']) : 'Unavailable' ?></h4>
                </div>

                <?php if ($activeSession): ?>
                    <div class="session-meta">
                        <div>
                            <span>Application Fee</span>
                            <strong>₦<?= number_format((float)$activeSession['application_fee'], 2) ?></strong>
                        </div>

                        <div>
                            <span>Closing Date</span>
                            <strong><?= h($activeSession['end_date']) ?></strong>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-7 auth-card p-4 p-lg-5">
            <div class="nav nav-pills auth-switch mb-4" role="tablist">

                <button
                    class="nav-link active"
                    data-bs-toggle="pill"
                    data-bs-target="#registerTab"
                    type="button"
                    role="tab">
                    Create Account
                </button>

                <button
                    class="nav-link"
                    data-bs-toggle="pill"
                    data-bs-target="#loginTab"
                    type="button"
                    role="tab">
                    Applicant Login
                </button>

            </div>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="registerTab">

                    <div class="text-center mb-4">
                        <h3 class="fw-bold mb-2">Create Admission Account</h3>
                        <p class="text-muted mb-4">
                            Begin your admission application in three simple steps.
                        </p>

                        <div class="d-flex justify-content-center align-items-center gap-3 mb-3">
                            <div class="step-indicator <?= $currentStep >= 1 ? 'active' : '' ?> <?= $currentStep > 1 ? 'completed' : '' ?>" id="step1Indicator">
                                <span>1</span>
                                <small>Email</small>
                            </div>

                            <div class="step-line"></div>

                            <div class="step-indicator <?= $currentStep >= 2 ? 'active' : '' ?> <?= $currentStep > 2 ? 'completed' : '' ?>" id="step2Indicator">
                                <small>Verify OTP</small>
                            </div>

                            <div class="step-line"></div>

                            <div class="step-indicator <?= $currentStep >= 3 ? 'active' : '' ?>" id="step3Indicator">
                                <span>3</span>
                                <small>Password</small>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 1 -->

                    <div class="auth-step <?= $currentStep !== 1 ? 'd-none' : '' ?>" id="step1">

                        <form id="requestOtpForm" class="ajax-form" data-endpoint="../api/admission/request-otp.php">

                            <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">

                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input
                                    type="email"
                                    id="signupEmail"
                                    name="email"
                                    class="form-control form-control-lg"
                                    placeholder="Enter your email address"
                                    required>
                            </div>

                            <button class="btn btn-primary btn-lg w-100">
                                Send Verification Code
                            </button>

                        </form>
                    </div>

                    <!-- STEP 2 -->
                    <div class="auth-step <?= $currentStep !== 2 ? 'd-none' : '' ?>" id="step2">
                        <form id="verifyOtpForm" class="ajax-form"
                            data-endpoint="../api/admission/verify-otp.php">

                            <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">

                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input
                                    type="email"
                                    id="verifiedEmailDisplay"
                                    name="email"
                                    value="<?= h($signupEmail) ?>"
                                    class="form-control bg-light"
                                    readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Verification Code</label>
                                <input
                                    type="text"
                                    name="otp"
                                    maxlength="6"
                                    inputmode="numeric"
                                    autocomplete="one-time-code"
                                    class="form-control form-control-lg text-center fw-bold"
                                    style="letter-spacing:8px;">
                            </div>

                            <button class="btn btn-outline-primary btn-lg w-100">
                                Verify OTP
                            </button>

                        </form>

                    </div>

                    <!-- STEP 3 -->
                    <div class="auth-step <?= $currentStep !== 3 ? 'd-none' : '' ?>" id="step3">
                        <form id="createAccountForm"
                            class="ajax-form"
                            data-endpoint="../api/admission/create-account.php">

                            <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">

                            <div class="mb-3">
                                <label class="form-label">Verified Email</label>

                                <input
                                    type="email"
                                    id="finalVerifiedEmail"
                                    name="email"
                                    value="<?= h($signupEmail) ?>"
                                    class="form-control bg-light"
                                    readonly>

                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>

                                <input
                                    type="text"
                                    name="phone"
                                    class="form-control"
                                    placeholder="Enter phone number"
                                    required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password</label>

                                    <input
                                        type="password"
                                        name="password"
                                        class="form-control"
                                        minlength="8"
                                        required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirm Password</label>

                                    <input
                                        type="password"
                                        name="confirm_password"
                                        class="form-control"
                                        minlength="8"
                                        required>
                                </div>
                            </div>

                            <button class="btn btn-success btn-lg w-100">
                                Create Applicant Account
                            </button>

                        </form>
                    </div>

                </div>

                <div class="tab-pane fade" id="loginTab">
                    <form class="ajax-form" data-endpoint="../api/admission/login.php">
                        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary btn-lg w-100" type="submit">Login</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
</div>
<?php include './partials/footer.php'; ?>

</body>

</html>