<?php
include './partials/header.php';


if (!$applicantId) {
    header("Location: index.php");
    exit;
}
if (!$application) {
    header("Location: dashboard.php");
    exit;
}

$applicationInvoice =
    $admission->ensurePaymentInvoice(
        (int)$application['id'],
        'application_fee'
    );

/*
|--------------------------------------------------------------------------
| PROTECT FORM PAGE
|--------------------------------------------------------------------------
*/

if (($applicationInvoice['status'] ?? '') !== 'paid') {

    $_SESSION['flash_error'] =
        'Please pay your application fee before completing the admission form.';

    header("Location: dashboard.php");
    exit;
}

require_once './helpers/admission_helper.php';

$documents = $full ? document_map($full) : [];

$requiredDocuments =
    $completion['required_documents']
    ?? ['passport', 'birth_certificate', 'olevel_result'];

$isLocked =
    $application &&
    in_array(
        $application['form_status'],
        [
            'Submitted',
            'Under Review',
            'Recommended',
            'Offered Admission',
            'Rejected',
            'Accepted'
        ],
        true
    );
?>

<div class="dashboard-layout">

    <?php include 'partials/sidebar.php'; ?>

    <div class="dashboard-content">

        <?php include 'partials/topbar.php'; ?>

        <main class="container-fluid py-4">


            <?php if ($applicantId): ?>
                <section class="form-hero mb-4">

                    <div class="row align-items-center">

                        <div class="col-lg-8">

                            <span class="hero-tag">
                                ADMISSION APPLICATION
                            </span>

                            <h2 class="mt-2">
                                Complete Your Application
                            </h2>

                            <p class="mb-0">
                                Complete all sections of the admission form and submit your application for review.
                            </p>

                        </div>

                        <div class="col-lg-4 text-lg-end">

                            <div class="status-widget">

                                <small>Completion</small>

                                <h4>
                                    <?= (int)($completion['percentage'] ?? 0) ?>%
                                </h4>

                            </div>

                        </div>

                    </div>

                </section>

                <?php if (!empty($completion['application_fee_paid'])): ?>
                    <section class="surface p-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <h5 class="mb-0">Admission Form</h5>
                            <div class="d-flex gap-2">
                                <?php if (!empty($application['registration_no'])): ?>
                                    <a class="btn btn-outline-primary btn-sm" target="_blank" href="../api/admission/download-slip.php">Application Slip</a>
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
                                'preview' => 'Preview',
                                'final' => 'Final Submission'
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

                        <div class="tab-content admission-form-content">
                            <div class="tab-pane fade show active" id="bioPane" role="tabpanel">
                                <?php include './forms/biodata.php'; ?>
                            </div>

                            <div class="tab-pane fade" id="contactPane" role="tabpanel">
                                <?php include './forms/contact.php'; ?>
                            </div>

                            <div class="tab-pane fade" id="academicPane" role="tabpanel">
                                <?php include './forms/academic.php'; ?>
                            </div>

                            <div class="tab-pane fade" id="olevelPane" role="tabpanel">
                                <?php include './forms/olevel.php'; ?>
                            </div>

                            <div class="tab-pane fade" id="programmePane" role="tabpanel">
                                <?php include './forms/programme.php'; ?>
                            </div>

                            <div class="tab-pane fade" id="documentsPane" role="tabpanel">
                                <?php include './forms/documents.php'; ?>
                            </div>

                            <div class="tab-pane fade" id="previewPane" role="tabpanel">
                                <?php include './forms/preview.php'; ?>
                            </div>

                            <div class="tab-pane fade" id="finalPane" role="tabpanel">
                                <?php include './forms/final.php'; ?>
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