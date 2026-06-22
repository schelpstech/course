<?php
include './partials/header.php';
$applicantId = (int) ($_SESSION['admission_applicant_id'] ?? 0);
if (!$applicantId) {
    header("Location: index.php");
    exit;
}
if ($applicantId) {
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
}
require_once './helpers/admission_helper.php';
if ($applicantId) {
    $documents = $full ? document_map($full) : [];
    $requiredDocuments = $completion['required_documents'] ?? ['passport', 'birth_certificate', 'olevel_result'];
    $isLocked = $application && in_array($application['form_status'], $statusesLocked, true);
}

?>

<div class="dashboard-layout">

    <?php include 'partials/sidebar.php'; ?>

    <div class="dashboard-content">

        <?php include 'partials/topbar.php'; ?>

        <main class="container-fluid py-4">


            <?php if ($applicantId): ?>

                <!-- HERO SECTION -->
                <section class="dashboard-hero mb-4">
                    <!-- Decorative Element -->
                    <div class="hero-wave"></div>
                    <div class="hero-wave hero-wave-2"></div>
                    <!-- Actual Content -->
                    <div class="row align-items-center g-4">

                        <div class="col-lg-8">

                            <span class="hero-tag">
                                <?= h($activeSession['academic_session_name']) ?> ADMISSION PORTAL
                            </span>

                            <h2 class="mt-3 mb-2">
                                Welcome Back,
                                <?= h($full['first_name'] ?? 'Applicant') ?>
                            </h2>

                            <p class="mb-0">
                                Complete your admission application and monitor your admission progress.
                            </p>

                        </div>

                        <div class="col-lg-4">

                            <div class="status-widget ms-lg-auto">

                                <small>Current Status</small>

                                <h4 class="mb-2">
                                    <?= h($application['form_status']) ?>
                                </h4>

                                <?php if (($applicationInvoice['status'] ?? '') !== 'paid'): ?>
                                    <span class="badge bg-warning text-dark">
                                        Action Required
                                    </span>
                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </section>


                <!-- METRIC SECTION -->
                <div class="row g-4 mb-4">

                    <!-- Application Number -->
                    <div class="col-xl-3 col-md-6">
                        <div class="metric-card h-100">

                            <div class="metric-icon metric-blue">
                                📄
                            </div>

                            <div class="metric-body">
                                <small>Application Number</small>

                                <h4><?= h($application['application_no']) ?></h4>

                                <span class="metric-footer text-primary">
                                    <?= h($activeSession['academic_session_name']) ?>
                                </span>
                            </div>

                        </div>
                    </div>

                    <!-- Application Status -->
                    <div class="col-xl-3 col-md-6">
                        <div class="metric-card h-100">

                            <div class="metric-icon metric-green">
                                ✅
                            </div>

                            <div class="metric-body">
                                <small>Application Status</small>

                                <h4><?= h($application['form_status']) ?></h4>

                                <span class="metric-footer">
                                    Current Application State
                                </span>
                            </div>

                        </div>
                    </div>

                    <!-- Payment Status -->
                    <div class="col-xl-3 col-md-6">
                        <div class="metric-card h-100">

                            <div class="metric-icon metric-orange">
                                💳
                            </div>

                            <div class="metric-body">
                                <small>Payment Status</small>

                                <h4>
                                    <?= ucfirst($applicationInvoice['status'] ?? 'Unpaid') ?>
                                </h4>

                                <span class="metric-footer">
                                    <?php if (($applicationInvoice['status'] ?? '') !== 'paid'): ?>
                                        <span class="badge rounded-pill bg-warning text-dark">
                                            Outstanding
                                        </span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill bg-success">
                                            Paid
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </div>

                        </div>
                    </div>

                    <!-- Profile Completion -->
                    <div class="col-xl-3 col-md-6">
                        <div class="metric-card h-100">

                            <div class="metric-icon metric-purple">
                                📊
                            </div>

                            <div class="metric-body">
                                <small>Profile Completion</small>

                                <h4>
                                    <?= (int)($completion['percentage'] ?? 0) ?>%
                                </h4>

                                <div class="metric-footer">

                                    <div class="progress">
                                        <div
                                            class="progress-bar"
                                            style="width:<?= (int)($completion['percentage'] ?? 0) ?>%">
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                <!-- MAIN DASHBOARD CONTENT -->
                <div class="row g-4">

                    <!-- LEFT SIDE -->
                    <div class="col-lg-8">

                        <!-- APPLICATION PROGRESS -->
                        <section class="progress-card mb-4">

                            <div class="d-flex justify-content-between align-items-center mb-4">

                                <div>
                                    <h5 class="mb-1">
                                        Application Progress
                                    </h5>

                                    <small class="text-muted">
                                        Track your admission journey from application to admission.
                                    </small>
                                </div>

                                <div class="progress-score">

                                    <span class="progress-percent">
                                        <?= (int)($completion['percentage'] ?? 0) ?>%
                                    </span>

                                    <small>Completed</small>

                                </div>

                            </div>

                            <div class="overall-progress mb-5">

                                <div class="progress">
                                    <div
                                        class="progress-bar bg-success"
                                        style="width:<?= (int)($completion['percentage'] ?? 0) ?>%">
                                    </div>
                                </div>

                            </div>

                            <div class="timeline">

                                <!-- ACCOUNT CREATED -->
                                <div class="timeline-step complete">

                                    <div class="timeline-circle">
                                        <i class="bi bi-person-check"></i>
                                    </div>

                                    <div class="timeline-label">
                                        <strong>Account</strong>
                                        <small>Created</small>
                                    </div>

                                </div>

                                <!-- PAYMENT -->
                                <div class="timeline-step <?= !empty($completion['application_fee_paid']) ? 'complete' : '' ?>">

                                    <div class="timeline-circle">
                                        <i class="bi bi-credit-card"></i>
                                    </div>

                                    <div class="timeline-label">
                                        <strong>Payment</strong>
                                        <small>Application Fee</small>
                                    </div>

                                </div>

                                <!-- BIODATA -->
                                <div class="timeline-step <?= !empty($completion['bio']) ? 'complete' : '' ?>">

                                    <div class="timeline-circle">
                                        <i class="bi bi-person-vcard"></i>
                                    </div>

                                    <div class="timeline-label">
                                        <strong>Biodata</strong>
                                        <small>Completed</small>
                                    </div>

                                </div>

                                <!-- DOCUMENTS -->
                                <div class="timeline-step <?= !empty($completion['documents']) ? 'complete' : '' ?>">

                                    <div class="timeline-circle">
                                        <i class="bi bi-folder-check"></i>
                                    </div>

                                    <div class="timeline-label">
                                        <strong>Documents</strong>
                                        <small>Uploaded</small>
                                    </div>

                                </div>

                                <!-- SUBMITTED -->
                                <div class="timeline-step <?= ($application['form_status'] ?? '') === 'Submitted' ? 'complete' : '' ?>">

                                    <div class="timeline-circle">
                                        <i class="bi bi-send-check"></i>
                                    </div>

                                    <div class="timeline-label">
                                        <strong>Submission</strong>
                                        <small>Completed</small>
                                    </div>

                                </div>

                            </div>

                        </section>

                        <!-- APPLICATION OVERVIEW -->
                        <section class="overview-card">

                            <div class="overview-header">

                                <div>
                                    <h5 class="mb-1">
                                        Application Overview
                                    </h5>

                                    <small class="text-muted">
                                        Summary of your admission application
                                    </small>
                                </div>

                                <div class="overview-badge">
                                    <?= h($application['form_status']) ?>
                                </div>

                            </div>

                            <div class="row g-4 mt-2">

                                <div class="col-md-6">
                                    <div class="overview-item">

                                        <div class="overview-icon bg-primary-subtle">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </div>

                                        <div>
                                            <small>Application Number</small>
                                            <h6><?= h($application['application_no']) ?></h6>
                                        </div>

                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="overview-item">

                                        <div class="overview-icon bg-success-subtle">
                                            <i class="bi bi-calendar-event"></i>
                                        </div>

                                        <div>
                                            <small>Academic Session</small>
                                            <h6><?= h($activeSession['academic_session_name']) ?></h6>
                                        </div>

                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="overview-item">

                                        <div class="overview-icon bg-warning-subtle">
                                            <i class="bi bi-clock-history"></i>
                                        </div>

                                        <div>
                                            <small>Date Created</small>
                                            <h6><?= date('d M Y', strtotime($application['created_at'])) ?></h6>
                                        </div>

                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="overview-item">

                                        <div class="overview-icon bg-info-subtle">
                                            <i class="bi bi-mortarboard"></i>
                                        </div>

                                        <div>
                                            <small>Application Type</small>
                                            <h6>Regular Admission</h6>
                                        </div>

                                    </div>
                                </div>

                                <?php if (!empty($full['registration_no'])): ?>
                                    <div class="col-md-6">
                                        <div class="overview-item">

                                            <div class="overview-icon bg-secondary-subtle">
                                                <i class="bi bi-person-badge"></i>
                                            </div>

                                            <div>
                                                <small>Registration Number</small>
                                                <h6><?= h($full['registration_no']) ?></h6>
                                            </div>

                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($full['matric_no'])): ?>
                                    <div class="col-md-6">
                                        <div class="overview-item">

                                            <div class="overview-icon bg-dark-subtle">
                                                <i class="bi bi-award"></i>
                                            </div>

                                            <div>
                                                <small>Matric Number</small>
                                                <h6><?= h($full['matric_no']) ?></h6>
                                            </div>

                                        </div>
                                    </div>
                                <?php endif; ?>

                            </div>

                        </section>

                    </div>

                    <div class="col-lg-4">

                        <!-- NEXT ACTION -->

                        <?php if (empty($completion['application_fee_paid'])): ?>

                            <section class="action-card premium-action-card mb-4">

                                <div class="action-icon">
                                    <i class="bi bi-credit-card"></i>
                                </div>

                                <div class="action-label">
                                    NEXT ACTION
                                </div>

                                <h4 class="mt-2">
                                    Pay Application Fee
                                </h4>

                                <p>
                                    Your admission application cannot proceed until the application fee has been paid.
                                </p>

                                <div class="amount">
                                    NGN <?= number_format((float)($applicationInvoice['amount'] ?? 0), 2) ?>
                                </div>

                                <form class="payment-form mt-4">
                                    <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                                    <input type="hidden" name="payment_type" value="application_fee">

                                    <button class="btn btn-light btn-lg w-100">
                                        <i class="bi bi-lock-fill me-2"></i>
                                        Pay Now
                                    </button>
                                </form>

                            </section>

                        <?php endif; ?>



                        <!-- CHECKLIST -->

                        <section class="checklist-card mb-4">

                            <h5 class="mb-4">
                                Application Checklist
                            </h5>

                            <div class="check-item <?= !empty($completion['application_fee_paid']) ? 'done' : '' ?>">
                                <i class="bi bi-check-circle-fill"></i>
                                Application Fee Payment
                            </div>

                            <div class="check-item <?= !empty($completion['bio']) ? 'done' : '' ?>">
                                <i class="bi bi-check-circle-fill"></i>
                                Biodata Information
                            </div>

                            <div class="check-item <?= !empty($completion['documents']) ? 'done' : '' ?>">
                                <i class="bi bi-check-circle-fill"></i>
                                Documents Upload
                            </div>

                            <div class="check-item <?= ($application['form_status'] ?? '') === 'Submitted' ? 'done' : '' ?>">
                                <i class="bi bi-check-circle-fill"></i>
                                Final Submission
                            </div>

                        </section>



                        <!-- RECENT ACTIVITIES -->

                        <section class="activity-card mb-4">

                            <h5 class="mb-4">
                                Recent Activity
                            </h5>

                            <div class="activity-item">
                                <div class="activity-icon success">
                                    <i class="bi bi-person-check"></i>
                                </div>

                                <div>
                                    <strong>Account Created</strong>
                                    <small class="d-block text-muted">
                                        Applicant account successfully created
                                    </small>
                                </div>
                            </div>

                            <div class="activity-item">
                                <div class="activity-icon primary">
                                    <i class="bi bi-envelope-check"></i>
                                </div>

                                <div>
                                    <strong>Email Verified</strong>
                                    <small class="d-block text-muted">
                                        OTP verification completed
                                    </small>
                                </div>
                            </div>

                            <?php if (!empty($completion['application_fee_paid'])): ?>

                                <div class="activity-item">
                                    <div class="activity-icon success">
                                        <i class="bi bi-credit-card"></i>
                                    </div>

                                    <div>
                                        <strong>Application Fee Paid</strong>
                                        <small class="d-block text-muted">
                                            Payment successfully confirmed
                                        </small>
                                    </div>
                                </div>

                            <?php endif; ?>

                        </section>
                    </div>

                </div>
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

    </div>

</div>

<?php endif; ?>
<?php include './partials/footer.php'; ?>

</body>

</html>