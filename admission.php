<?php
require_once './start.inc.php';

$admission = new Admission($db, $model, $utility, $qrcode, $mailservice);
$csrf = $admission->csrfToken();
$applicantId = (int) ($_SESSION['admission_applicant_id'] ?? 0);
$application = $applicantId ? $admission->getApplicationForApplicant($applicantId) : null;
$full = $application ? $admission->getFullApplication((int) $application['id']) : null;
$completion = $application ? $admission->completion((int) $application['id']) : [];
$applicationInvoice = $application ? $admission->ensurePaymentInvoice((int) $application['id'], 'application_fee') : null;
$acceptanceInvoice = ($application && in_array($application['form_status'], ['Offered Admission', 'Accepted'], true))
    ? $admission->ensurePaymentInvoice((int) $application['id'], 'acceptance_fee')
    : null;
$activeSession = $admission->activeSession();
$institutions = $admission->institutions();
$statusesLocked = ['Submitted', 'Under Review', 'Recommended', 'Offered Admission', 'Rejected', 'Accepted'];

require_once 'admission/helpers/admission_helper.php';

$documents = $full ? document_map($full) : [];
$requiredDocuments = $completion['required_documents'] ?? ['passport', 'birth_certificate', 'olevel_result'];
$isLocked = $application && in_array($application['form_status'], $statusesLocked, true);
?>
<!doctype html>
<html lang="en">
<?php include 'admission/partials/header.php'; ?>

<body>
    <div class="admission-shell">
        <?php include 'admission/partials/topbar.php'; ?>

        <?php if (!$applicantId): ?>
            <main class="auth-panel">
                <div class="row g-0 surface overflow-hidden">
                    <div class="col-lg-5 auth-aside p-4 p-lg-5">
                        <h1 class="h3 mb-3">Online Admission</h1>
                        <p class="text-white-50 mb-4">Create an applicant account, pay the application fee, complete the form, and track your admission decision.</p>
                        <div class="border border-secondary rounded p-3">
                            <small class="text-white-50 d-block">Current Session</small>
                            <strong><?= $activeSession ? h($activeSession['academic_session_name']) : 'Unavailable' ?></strong>
                            <?php if ($activeSession): ?>
                                <div class="mt-2 small text-white-50">
                                    Application Fee: NGN <?= number_format((float) $activeSession['application_fee'], 2) ?><br>
                                    Closes: <?= h($activeSession['end_date']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-7 auth-card p-4 p-lg-5">
                        <ul class="nav nav-pills mb-4" role="tablist">
                            <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#registerTab" type="button">Create Account</button></li>
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#loginTab" type="button">Applicant Login</button></li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="registerTab">

                                <div class="text-center mb-4">
                                    <h3 class="fw-bold mb-2">Create Admission Account</h3>
                                    <p class="text-muted mb-4">
                                        Begin your admission application in three simple steps.
                                    </p>

                                    <div class="d-flex justify-content-center align-items-center gap-3 mb-3">
                                        <div class="step-indicator active" id="step1Indicator">
                                            <span>1</span>
                                            <small>Email</small>
                                        </div>

                                        <div class="step-line"></div>

                                        <div class="step-indicator" id="step2Indicator">
                                            <span>2</span>
                                            <small>Verify OTP</small>
                                        </div>

                                        <div class="step-line"></div>

                                        <div class="step-indicator" id="step3Indicator">
                                            <span>3</span>
                                            <small>Password</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- STEP 1 -->
                                <div class="auth-step" id="step1">
                                    <form id="requestOtpForm" class="ajax-form" data-endpoint="api/admission/request-otp.php">

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
                                <div class="auth-step d-none" id="step2">

                                    <form id="verifyOtpForm" class="ajax-form"
                                        data-endpoint="api/admission/verify-otp.php">

                                        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">

                                        <div class="mb-3">
                                            <label class="form-label">Email Address</label>

                                            <input
                                                type="email"
                                                id="verifiedEmailDisplay"
                                                name="email"
                                                class="form-control bg-light"
                                                readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Verification Code</label>

                                            <input
                                                type="text"
                                                name="otp"
                                                class="form-control form-control-lg text-center"
                                                maxlength="6"
                                                placeholder="Enter 6-digit OTP"
                                                required>
                                        </div>

                                        <button class="btn btn-outline-primary btn-lg w-100">
                                            Verify OTP
                                        </button>

                                    </form>

                                </div>

                                <!-- STEP 3 -->
                                <div class="auth-step d-none" id="step3">

                                    <form id="createAccountForm"
                                        class="ajax-form"
                                        data-endpoint="api/admission/create-account.php">

                                        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">

                                        <div class="mb-3">
                                            <label class="form-label">Verified Email</label>

                                            <input
                                                type="email"
                                                id="finalVerifiedEmail"
                                                name="email"
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
                                <form class="ajax-form" data-endpoint="api/admission/login.php">
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
                                            <button class="btn btn-primary" type="submit">Login</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>




        <?php else: ?>
            <main class="container-fluid px-4 py-4">
                <div class="row g-3 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="surface metric p-3 h-100">
                            <small class="text-muted">Application Number</small>
                            <h5 class="mb-0"><?= h($application['application_no']) ?></h5>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="surface metric p-3 h-100">
                            <small class="text-muted">Registration Number</small>
                            <h5 class="mb-0"><?= h($application['registration_no'] ?: 'Pending Submission') ?></h5>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="surface metric p-3 h-100">
                            <small class="text-muted">Application Status</small>
                            <h5 class="mb-0"><?= h($application['form_status']) ?></h5>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="surface metric p-3 h-100">
                            <small class="text-muted">Application Fee</small>
                            <h5 class="mb-0">
                                <span class="status-badge <?= ($applicationInvoice['status'] ?? '') === 'paid' ? 'status-paid' : 'status-pending' ?>">
                                    <?= h(ucfirst($applicationInvoice['status'] ?? 'unpaid')) ?>
                                </span>
                            </h5>
                        </div>
                    </div>
                </div>

                <?php if (empty($completion['application_fee_paid'])): ?>
                    <section class="surface p-4 mb-4">
                        <div class="row align-items-center g-3">
                            <div class="col-lg-8">
                                <h5 class="mb-1">Application Fee Invoice</h5>
                                <div class="text-muted">
                                    Invoice <?= h($applicationInvoice['invoice_no'] ?? '') ?> |
                                    Amount NGN <?= number_format((float) ($applicationInvoice['amount'] ?? 0), 2) ?> |
                                    Session <?= h($application['academic_session_name'] ?? '') ?>
                                </div>
                            </div>
                            <div class="col-lg-4 text-lg-end">
                                <form class="payment-form d-inline-block">
                                    <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                                    <input type="hidden" name="payment_type" value="application_fee">
                                    <button class="btn btn-success" type="submit">Pay Application Fee</button>
                                </form>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($acceptanceInvoice): ?>
                    <section class="surface p-4 mb-4">
                        <div class="row align-items-center g-3">
                            <div class="col-lg-8">
                                <h5 class="mb-1">Admission Offer</h5>
                                <div class="text-muted">
                                    Acceptance Fee: NGN <?= number_format((float) ($acceptanceInvoice['amount'] ?? 0), 2) ?> |
                                    Status: <?= h(ucfirst($acceptanceInvoice['status'] ?? 'unpaid')) ?>
                                    <?php if (!empty($full['matric_no'])): ?>
                                        | Matric Number: <?= h($full['matric_no']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-lg-4 text-lg-end">
                                <a class="btn btn-outline-primary" target="_blank" href="api/admission/download-letter.php">Admission Letter</a>
                                <?php if (($acceptanceInvoice['status'] ?? '') !== 'paid' && $application['form_status'] === 'Offered Admission'): ?>
                                    <form class="payment-form d-inline-block ms-2">
                                        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                                        <input type="hidden" name="payment_type" value="acceptance_fee">
                                        <button class="btn btn-success" type="submit">Pay Acceptance Fee</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if (!empty($completion['application_fee_paid'])): ?>
                    <section class="surface p-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <h5 class="mb-0">Admission Form</h5>
                            <div class="d-flex gap-2">
                                <?php if (!empty($application['registration_no'])): ?>
                                    <a class="btn btn-outline-primary btn-sm" target="_blank" href="api/admission/download-slip.php">Application Slip</a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <ul class="nav wizard-nav mb-4" id="wizardTabs" role="tablist">
                            <?php
                            $steps = [
                                'bio' => 'Bio Data',
                                'contact' => 'Contact',
                                'academic' => 'Academic History',
                                'olevel' => 'O-Level',
                                'programme' => 'Programme',
                                'documents' => 'Documents',
                                'preview' => 'Preview'
                            ];
                            $firstActive = true;
                            foreach ($steps as $key => $label):
                                $enabled = !$isLocked ? can_open_step($completion, $key) : true;
                                $active = $firstActive && $enabled;
                                if ($active) {
                                    $firstActive = false;
                                }
                            ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $active ? 'active' : '' ?> <?= !$enabled ? 'disabled' : '' ?>"
                                        data-bs-toggle="tab"
                                        data-bs-target="#<?= h($key) ?>Pane"
                                        type="button"
                                        role="tab"
                                        <?= !$enabled ? 'disabled' : '' ?>>
                                        <?= h($label) ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="bioPane" role="tabpanel">
                                <form class="ajax-form" data-endpoint="api/admission/save-step.php">
                                    <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                                    <input type="hidden" name="step" value="bio">
                                    <div class="row g-3">
                                        <div class="col-md-4"><label class="form-label">Surname</label><input class="form-control" name="surname" value="<?= h($full['surname'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>></div>
                                        <div class="col-md-4"><label class="form-label">First Name</label><input class="form-control" name="first_name" value="<?= h($full['first_name'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>></div>
                                        <div class="col-md-4"><label class="form-label">Other Name</label><input class="form-control" name="other_name" value="<?= h($full['other_name'] ?? '') ?>" <?= $isLocked ? 'disabled' : '' ?>></div>
                                        <div class="col-md-3">
                                            <label class="form-label">Gender</label>
                                            <select class="form-select" name="gender" required <?= $isLocked ? 'disabled' : '' ?>>
                                                <option value="">Select</option>
                                                <option <?= selected($full['gender'] ?? '', 'Male') ?>>Male</option>
                                                <option <?= selected($full['gender'] ?? '', 'Female') ?>>Female</option>
                                                <option <?= selected($full['gender'] ?? '', 'Other') ?>>Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3"><label class="form-label">Date of Birth</label><input type="date" class="form-control" name="date_of_birth" value="<?= h($full['date_of_birth'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>></div>
                                        <div class="col-md-3"><label class="form-label">Nationality</label><input class="form-control" name="nationality" value="<?= h($full['nationality'] ?? 'Nigeria') ?>" required <?= $isLocked ? 'disabled' : '' ?>></div>
                                        <div class="col-md-3"><label class="form-label">Religion</label><input class="form-control" name="religion" value="<?= h($full['religion'] ?? '') ?>" <?= $isLocked ? 'disabled' : '' ?>></div>
                                        <div class="col-md-6"><label class="form-label">State of Origin</label><input class="form-control" name="state_of_origin" value="<?= h($full['state_of_origin'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>></div>
                                        <div class="col-md-6"><label class="form-label">Local Government</label><input class="form-control" name="local_government" value="<?= h($full['local_government'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>></div>
                                        <?php if (!$isLocked): ?><div class="col-12"><button class="btn btn-primary">Save Bio Data</button></div><?php endif; ?>
                                    </div>
                                </form>
                            </div>

                            <div class="tab-pane fade" id="contactPane" role="tabpanel">
                                <form class="ajax-form" data-endpoint="api/admission/save-step.php">
                                    <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                                    <input type="hidden" name="step" value="contact">
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?= h($full['contact_email'] ?? $full['applicant_email'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>></div>
                                        <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?= h($full['contact_phone'] ?? $full['applicant_phone'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>></div>
                                        <div class="col-12"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="4" required <?= $isLocked ? 'disabled' : '' ?>><?= h($full['address'] ?? '') ?></textarea></div>
                                        <?php if (!$isLocked): ?><div class="col-12"><button class="btn btn-primary">Save Contact</button></div><?php endif; ?>
                                    </div>
                                </form>
                            </div>

                            <div class="tab-pane fade" id="academicPane" role="tabpanel">
                                <form class="ajax-form" data-endpoint="api/admission/save-step.php">
                                    <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                                    <input type="hidden" name="step" value="academic">
                                    <?php $history = $full['history'] ?? [[]];
                                    for ($i = 0; $i < max(2, count($history)); $i++): $row = $history[$i] ?? []; ?>
                                        <div class="row g-3 border-bottom pb-3 mb-3">
                                            <div class="col-md-4"><label class="form-label">Institution Name</label><input class="form-control" name="history[<?= $i ?>][institution_name]" value="<?= h($row['institution_name'] ?? '') ?>" <?= $isLocked ? 'disabled' : '' ?>></div>
                                            <div class="col-md-3"><label class="form-label">Certificate</label><input class="form-control" name="history[<?= $i ?>][certificate_obtained]" value="<?= h($row['certificate_obtained'] ?? '') ?>" <?= $isLocked ? 'disabled' : '' ?>></div>
                                            <div class="col-md-3"><label class="form-label">Location</label><input class="form-control" name="history[<?= $i ?>][location]" value="<?= h($row['location'] ?? '') ?>" <?= $isLocked ? 'disabled' : '' ?>></div>
                                            <div class="col-md-1"><label class="form-label">Start</label><input class="form-control" name="history[<?= $i ?>][start_year]" value="<?= h($row['start_year'] ?? '') ?>" <?= $isLocked ? 'disabled' : '' ?>></div>
                                            <div class="col-md-1"><label class="form-label">End</label><input class="form-control" name="history[<?= $i ?>][end_year]" value="<?= h($row['end_year'] ?? '') ?>" <?= $isLocked ? 'disabled' : '' ?>></div>
                                        </div>
                                    <?php endfor; ?>
                                    <?php if (!$isLocked): ?><button class="btn btn-primary">Save Academic History</button><?php endif; ?>
                                </form>
                            </div>

                            <div class="tab-pane fade" id="olevelPane" role="tabpanel">
                                <form class="ajax-form" data-endpoint="api/admission/save-step.php">
                                    <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                                    <input type="hidden" name="step" value="olevel">
                                    <?php
                                    $defaultSubjects = ['English Language', 'Mathematics', '', '', '', '', '', '', ''];
                                    $sittings = $full['sittings'] ?? [];
                                    for ($s = 0; $s < 2; $s++):
                                        $sitting = $sittings[$s] ?? [];
                                        $results = $sitting['results'] ?? [];
                                    ?>
                                        <div class="border rounded p-3 mb-3">
                                            <h6 class="mb-3"><?= $s === 0 ? 'First Sitting' : 'Second Sitting' ?></h6>
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-4"><label class="form-label">Exam Type</label><input class="form-control" name="sittings[<?= $s ?>][exam_type]" value="<?= h($sitting['exam_type'] ?? '') ?>" <?= $isLocked ? 'disabled' : '' ?>></div>
                                                <div class="col-md-4"><label class="form-label">Exam Year</label><input class="form-control" name="sittings[<?= $s ?>][exam_year]" value="<?= h($sitting['exam_year'] ?? '') ?>" <?= $isLocked ? 'disabled' : '' ?>></div>
                                                <div class="col-md-4"><label class="form-label">Exam Number</label><input class="form-control" name="sittings[<?= $s ?>][exam_number]" value="<?= h($sitting['exam_number'] ?? '') ?>" <?= $isLocked ? 'disabled' : '' ?>></div>
                                            </div>
                                            <div class="row g-2">
                                                <?php for ($r = 0; $r < 9; $r++): $result = $results[$r] ?? []; ?>
                                                    <div class="col-md-8"><input class="form-control" name="sittings[<?= $s ?>][subjects][]" value="<?= h($result['subject'] ?? $defaultSubjects[$r]) ?>" placeholder="Subject" <?= $isLocked ? 'disabled' : '' ?>></div>
                                                    <div class="col-md-4"><select class="form-select" name="sittings[<?= $s ?>][grades][]" <?= $isLocked ? 'disabled' : '' ?>><?= grade_options($result['grade'] ?? '') ?></select></div>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                    <?php if (!$isLocked): ?><button class="btn btn-primary">Save O-Level Results</button><?php endif; ?>
                                </form>
                            </div>

                            <div class="tab-pane fade" id="programmePane" role="tabpanel">
                                <form class="ajax-form" data-endpoint="api/admission/save-step.php">
                                    <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                                    <input type="hidden" name="step" value="programme">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Mode of Entry</label>
                                            <select class="form-select" name="mode_of_entry" id="modeOfEntry" required <?= $isLocked ? 'disabled' : '' ?>>
                                                <option value="">Select</option>
                                                <option <?= selected($full['mode_of_entry'] ?? '', 'JAMB UTME') ?>>JAMB UTME</option>
                                                <option <?= selected($full['mode_of_entry'] ?? '', 'Direct Entry') ?>>Direct Entry</option>
                                                <option <?= selected($full['mode_of_entry'] ?? '', 'Remedial') ?>>Remedial</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 jamb-field"><label class="form-label">JAMB Registration Number</label><input class="form-control" name="jamb_registration_number" value="<?= h($full['jamb_registration_number'] ?? '') ?>" <?= $isLocked ? 'disabled' : '' ?>></div>
                                        <div class="col-md-4 jamb-field"><label class="form-label">JAMB Score</label><input class="form-control" name="jamb_score" value="<?= h($full['jamb_score'] ?? '') ?>" <?= $isLocked ? 'disabled' : '' ?>></div>
                                        <div class="col-md-4">
                                            <label class="form-label">Institution</label>
                                            <select class="form-select linked-select" name="institution_id" id="institutionSelect" data-selected="<?= h($full['institution_id'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>>
                                                <option value="">Select</option>
                                                <?php foreach ($institutions as $institution): ?>
                                                    <option value="<?= h($institution['id']) ?>" <?= selected($full['institution_id'] ?? '', $institution['id']) ?>><?= h($institution['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Programme</label>
                                            <select class="form-select linked-select" name="programme_id" id="programmeSelect" data-selected="<?= h($full['programme_id'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>>
                                                <option value="">Select Institution First</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Department</label>
                                            <select class="form-select" name="department_id" id="departmentSelect" data-selected="<?= h($full['department_id'] ?? '') ?>" required <?= $isLocked ? 'disabled' : '' ?>>
                                                <option value="">Select Programme First</option>
                                            </select>
                                        </div>
                                        <?php if (!$isLocked): ?><div class="col-12"><button class="btn btn-primary">Save Programme Selection</button></div><?php endif; ?>
                                    </div>
                                </form>
                            </div>

                            <div class="tab-pane fade" id="documentsPane" role="tabpanel">
                                <div class="row g-3">
                                    <?php foreach ($requiredDocuments as $type): $doc = $documents[$type] ?? null; ?>
                                        <div class="col-lg-6">
                                            <form class="upload-form border rounded p-3 h-100" enctype="multipart/form-data">
                                                <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                                                <input type="hidden" name="document_type" value="<?= h($type) ?>">
                                                <label class="form-label"><?= h(doc_label($type)) ?></label>
                                                <input type="file" class="form-control" name="document" <?= $isLocked ? 'disabled' : '' ?> required>
                                                <div class="small text-muted mt-2">
                                                    <?= $doc ? 'Uploaded: ' . h($doc['original_name']) : 'Not uploaded' ?>
                                                    <?= $type === 'passport' ? ' | JPG/PNG, max 15KB' : ' | PDF/JPG/PNG' ?>
                                                </div>
                                                <?php if (!$isLocked): ?><button class="btn btn-outline-primary btn-sm mt-3">Upload</button><?php endif; ?>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="previewPane" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table preview-table">
                                        <tr>
                                            <th>Name</th>
                                            <td><?= h(trim(($full['surname'] ?? '') . ' ' . ($full['first_name'] ?? '') . ' ' . ($full['other_name'] ?? ''))) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td><?= h($full['contact_email'] ?? $full['applicant_email'] ?? '') ?></td>
                                        </tr>
                                        <tr>
                                            <th>Phone</th>
                                            <td><?= h($full['contact_phone'] ?? $full['applicant_phone'] ?? '') ?></td>
                                        </tr>
                                        <tr>
                                            <th>Institution</th>
                                            <td><?= h($full['institution_name'] ?? '') ?></td>
                                        </tr>
                                        <tr>
                                            <th>Programme</th>
                                            <td><?= h($full['programme_name'] ?? '') ?></td>
                                        </tr>
                                        <tr>
                                            <th>Department</th>
                                            <td><?= h($full['department_name'] ?? '') ?></td>
                                        </tr>
                                        <tr>
                                            <th>Mode of Entry</th>
                                            <td><?= h($full['mode_of_entry'] ?? '') ?></td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td><?= h($application['form_status']) ?></td>
                                        </tr>
                                    </table>
                                </div>

                                <?php if (!$isLocked && can_open_step($completion, 'preview')): ?>
                                    <form class="ajax-form" data-endpoint="api/admission/submit-application.php">
                                        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                                        <button class="btn btn-success">Submit Application</button>
                                    </form>
                                <?php elseif (!$isLocked): ?>
                                    <div class="alert alert-warning">Complete all sections and required uploads before final submission.</div>
                                <?php else: ?>
                                    <div class="alert alert-info mb-0">This application has been submitted and is now locked.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
            </main>
        <?php endif; ?>
    </div>
    <?php include 'admission/partials/footer.php'; ?>

</body>

</html>