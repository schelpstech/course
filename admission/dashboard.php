<?php
include './partials/header.php';

if (!$applicantId) {
    header("Location: index.php");
    exit;
}

if ($applicantId) {
    $applicationInvoice = $application ? $admission->ensurePaymentInvoice((int) $application['id'], 'application_fee') : null;
    $acceptanceInvoice = ($application && in_array($application['form_status'], ['Offered Admission', 'Accepted'], true))
        ? $admission->ensurePaymentInvoice((int) $application['id'], 'acceptance_fee')
        : null;
    $activeSession = $admission->activeSession();
    $institutions = $admission->institutions();
    $statusesLocked = ['Submitted', 'Pending Review', 'Under Review', 'Recommended', 'Offered Admission', 'Rejected', 'Accepted'];
}
require_once './helpers/admission_helper.php';
if ($applicantId) {
    $documents = $full ? document_map($full) : [];
    $requiredDocuments = $completion['required_documents'] ?? ['passport', 'birth_certificate', 'olevel_result'];
    $isLocked = $application && in_array($application['form_status'], $statusesLocked, true);
    $status = $application['form_status'] ?? '';
    $acceptancePaid = (($acceptanceInvoice['status'] ?? '') === 'paid');
    $submittedComplete = in_array($status, ['Submitted', 'Pending Review', 'Under Review', 'Recommended', 'Offered Admission', 'Rejected', 'Accepted'], true);
    $offerResponse = $full['offer_response']['response'] ?? '';
    $screeningRemarks = [];
    $latestReopenActionId = 0;

    foreach (($full['screening'] ?? []) as $screening) {
        if (($screening['action'] ?? '') === 'allow_edit') {
            $latestReopenActionId = max($latestReopenActionId, (int) ($screening['id'] ?? 0));
        }
    }

    foreach (($full['screening'] ?? []) as $screening) {
        if ((int) ($screening['id'] ?? 0) < $latestReopenActionId) {
            continue;
        }

        if (trim((string) ($screening['remarks'] ?? '')) !== '' || trim((string) ($screening['rejection_reason'] ?? '')) !== '') {
            $screeningRemarks[] = $screening;
        }
    }
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

                                <?php if (($applicationInvoice['status'] ?? '') !== 'paid' || ($status === 'Offered Admission' && !$acceptancePaid)): ?>
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
                                <div class="timeline-step <?= $submittedComplete ? 'complete' : '' ?>">

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

                        <?php if (!empty($screeningRemarks)): ?>
                            <section class="overview-card mt-4">
                                <div class="overview-header">
                                    <div>
                                        <h5 class="mb-1">Admission Office Remarks</h5>
                                        <small class="text-muted">
                                            Notes and decisions shared by the admission office.
                                        </small>
                                    </div>
                                    <div class="overview-badge">
                                        <?= count($screeningRemarks) ?> Update<?= count($screeningRemarks) === 1 ? '' : 's' ?>
                                    </div>
                                </div>

                                <div class="d-flex flex-column gap-3">
                                    <?php foreach ($screeningRemarks as $remark): ?>
                                        <div class="overview-item align-items-start">
                                            <div class="overview-icon bg-primary-subtle">
                                                <i class="bi bi-chat-square-text"></i>
                                            </div>
                                            <div class="w-100">
                                                <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                                    <div>
                                                        <small>Application Update</small>
                                                        <h6 class="mb-0">
                                                            <?= h($remark['to_status'] ?: $remark['action']) ?>
                                                        </h6>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?= !empty($remark['created_at']) ? date('d M Y, h:i A', strtotime($remark['created_at'])) : '' ?>
                                                    </small>
                                                </div>

                                                <?php if (!empty($remark['remarks'])): ?>
                                                    <p class="mb-2"><?= nl2br(h($remark['remarks'])) ?></p>
                                                <?php endif; ?>

                                                <?php if (!empty($remark['rejection_reason'])): ?>
                                                    <div class="alert alert-danger py-2 px-3 mb-0">
                                                        <strong>Reason:</strong>
                                                        <?= nl2br(h($remark['rejection_reason'])) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endif; ?>

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

                        <?php if ($status === 'Offered Admission'): ?>

                            <section class="action-card premium-action-card mb-4">

                                <div class="action-icon">
                                    <i class="bi bi-award"></i>
                                </div>

                                <div class="action-label">
                                    ADMISSION OFFER
                                </div>

                                <h4 class="mt-2">
                                    Offer of Admission
                                </h4>

                                <p>
                                    Congratulations. Accept this offer by paying your acceptance fee, or reject it if you do not wish to proceed.
                                </p>

                                <div class="amount">
                                    NGN <?= number_format((float)($acceptanceInvoice['amount'] ?? 0), 2) ?>
                                </div>

                                <?php if (!$acceptancePaid): ?>
                                    <form class="payment-form mt-4">
                                        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                                        <input type="hidden" name="payment_type" value="acceptance_fee">

                                        <button class="btn btn-light btn-lg w-100">
                                            <i class="bi bi-check-circle-fill me-2"></i>
                                            Accept & Pay Fee
                                        </button>
                                    </form>

                                    <form
                                        class="ajax-form mt-3"
                                        data-endpoint="../api/admission/respond-offer.php"
                                        data-confirm="Rejecting this offer will update your admission status to Rejected.">
                                        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                                        <input type="hidden" name="action" value="reject">

                                        <button class="btn btn-outline-light btn-lg w-100" type="submit">
                                            <i class="bi bi-x-circle me-2"></i>
                                            Reject Offer
                                        </button>
                                    </form>
                                <?php endif; ?>

                            </section>

                        <?php elseif ($status === 'Accepted'): ?>

                            <section class="action-card mb-4">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="overview-icon bg-success-subtle">
                                        <i class="bi bi-check2-circle"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">Admission Accepted</small>
                                        <h5 class="mb-0">Acceptance Fee Confirmed</h5>
                                    </div>
                                </div>

                                <p class="text-muted">
                                    Your admission has been accepted successfully. Download your admission letter and check back for matriculation details after student migration.
                                </p>

                                <?php if (!empty($full['matric_no'])): ?>
                                    <div class="alert alert-success">
                                        <strong>Matric Number:</strong> <?= h($full['matric_no']) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        Your matric number will appear here after the Admission Office migrates your record to the student portal.
                                    </div>
                                <?php endif; ?>

                                <a class="btn btn-primary btn-lg w-100" target="_blank" href="../api/admission/download-letter.php">
                                    <i class="bi bi-file-earmark-pdf me-2"></i>
                                    Download Admission Letter
                                </a>
                            </section>

                        <?php elseif ($status === 'Rejected'): ?>

                            <section class="action-card mb-4">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="overview-icon bg-danger-subtle">
                                        <i class="bi bi-x-octagon"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">Application Status</small>
                                        <h5 class="mb-0">Rejected</h5>
                                    </div>
                                </div>

                                <p class="text-muted mb-0">
                                    <?= $offerResponse === 'rejected'
                                        ? 'You rejected this admission offer. Contact the admission office if this was done in error.'
                                        : 'Check the admission office remarks for details or contact support if you need clarification.' ?>
                                </p>
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

                            <div class="check-item <?= $submittedComplete ? 'done' : '' ?>">
                                <i class="bi bi-check-circle-fill"></i>
                                Final Submission
                            </div>

                            <div class="check-item <?= in_array($status, ['Offered Admission', 'Accepted'], true) ? 'done' : '' ?>">
                                <i class="bi bi-check-circle-fill"></i>
                                Admission Decision
                            </div>

                            <div class="check-item <?= $acceptancePaid ? 'done' : '' ?>">
                                <i class="bi bi-check-circle-fill"></i>
                                Acceptance Fee Payment
                            </div>

                            <div class="check-item <?= !empty($full['matric_no']) ? 'done' : '' ?>">
                                <i class="bi bi-check-circle-fill"></i>
                                Student Portal Migration
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

                            <?php if (in_array($status, ['Offered Admission', 'Accepted'], true)): ?>

                                <div class="activity-item">
                                    <div class="activity-icon success">
                                        <i class="bi bi-award"></i>
                                    </div>

                                    <div>
                                        <strong>Admission Offer Issued</strong>
                                        <small class="d-block text-muted">
                                            Your application has received an admission offer
                                        </small>
                                    </div>
                                </div>

                            <?php endif; ?>

                            <?php if ($acceptancePaid): ?>

                                <div class="activity-item">
                                    <div class="activity-icon success">
                                        <i class="bi bi-check2-circle"></i>
                                    </div>

                                    <div>
                                        <strong>Admission Accepted</strong>
                                        <small class="d-block text-muted">
                                            Acceptance fee payment confirmed
                                        </small>
                                    </div>
                                </div>

                            <?php endif; ?>

                        </section>
                    </div>
                </div>
        </main>

    </div>

</div>

<?php endif; ?>
<?php include './partials/footer.php'; ?>

</body>

</html>
