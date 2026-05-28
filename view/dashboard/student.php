<?php

// ==========================
// REGISTRATION STEPS
// ==========================
$step1 = !empty($profile['updateProfile']);
$step2 = !empty($status['receipt_uploaded']);
$step3 = !empty($status['payment_confirmed']);
$step4 = !empty($status['course_fee_paid']);
$step5 = !empty($status['courses_registered']);

$steps = [$step1, $step2, $step3, $step4, $step5];

$completedSteps = count(array_filter($steps));
$totalSteps = count($steps);

$registrationProgress = ($totalSteps > 0)
    ? round(($completedSteps / $totalSteps) * 100, 2)
    : 0;

if ($completedSteps === 0) {
    $registrationStatus = 'Not Started';
    $registrationStatusClass = 'danger';
} elseif ($completedSteps < $totalSteps) {
    $registrationStatus = 'In Progress';
    $registrationStatusClass = 'warning';
} else {
    $registrationStatus = 'Completed';
    $registrationStatusClass = 'success';
}

// current step
$currentStep = 1;
if ($step1) $currentStep = 2;
if ($step2) $currentStep = 3;
if ($step3) $currentStep = 4;
if ($step4) $currentStep = 5;
if ($step5) $currentStep = 5;

$stepLabels = [
    1 => 'Profile Update',
    2 => 'Receipt Upload',
    3 => 'Payment Confirmation',
    4 => 'Course Fee Payment',
    5 => 'Course Registration'
];

$currentStepName = $stepLabels[$completedSteps + 1] ?? 'Completed';


// ==========================
// SEMESTER PAYMENT
// ==========================
$semesterPaymentDue = $model->getRows(
    "school_fee_settings",
    [
        "where" => [
            "semester_id" => $activeSemester['id'],
            "level_id" => $studentData['level_id']
        ],
        "return_type" => "single"
    ]
);

$semesterPaymentMade = $model->sumQuery(
    "payments",
    "amount_paid",
    [
        "where" => [
            "semester_id" => $activeSemester['id'],
            "student_id" => $studentData['student_id'],
            "status" => "successful",
            "payment_type" => "school_fee"
        ]
    ]
);

$tuitionDue = $semesterPaymentDue['amount'] ?? 0;
$amountPaid = $semesterPaymentMade ?? 0;

$paymentProgress = 0;
$balance = 0;

if ($tuitionDue > 0) {
    $paymentProgress = ($amountPaid / $tuitionDue) * 100;
}

$paymentProgress = min(100, round($paymentProgress, 2));
$balance = $tuitionDue - $amountPaid;

if ($amountPaid <= 0) {
    $paymentStatus = 'Not Paid';
    $paymentStatusClass = 'danger';
} elseif ($amountPaid < $tuitionDue) {
    $paymentStatus = 'Partially Paid';
    $paymentStatusClass = 'warning';
} else {
    $paymentStatus = 'Fully Paid';
    $paymentStatusClass = 'success';
}


// ==========================
// COURSE REGISTRATION STATUS
// ==========================
$courseRegistrationStatus = $model->getRows(
    "course_registered",
    [
        "where" => [
            "semester" => $activeSemester['id'],
            "session" => $activeSemester['session_id'],
            "student_id" => $studentData['student_id'],
        ],
        "return_type" => "single"
    ]
);

$courseRegStatus = $courseRegistrationStatus["approval_status"] ?? null;

$courseStatus = 'Not Started';
$courseStatusClass = 'danger';
$courseProgress = 0;

if (empty($courseRegStatus)) {

    $courseStatus = 'Not Started';
    $courseStatusClass = 'danger';
    $courseProgress = 0;
} elseif ($courseRegStatus === 'submitted') {

    $courseStatus = 'Saved (Not Submitted)';
    $courseStatusClass = 'info';
    $courseProgress = 25;
} elseif ($courseRegStatus === 'pending') {

    $courseStatus = 'Pending Approval';
    $courseStatusClass = 'warning';
    $courseProgress = 60;
} elseif ($courseRegStatus === 'rejected') {

    $courseStatus = 'Rejected';
    $courseStatusClass = 'danger';
    $courseProgress = 40;
} elseif ($courseRegStatus === 'approved') {

    $courseStatus = 'Approved';
    $courseStatusClass = 'success';
    $courseProgress = 100;
}

$nextStep = match ($courseRegStatus) {
    null => 'Start Course Registration',
    'submitted' => 'Submit for Approval',
    'pending' => 'Awaiting Review',
    'rejected' => 'Fix and Resubmit',
    'approved' => 'Completed',
    default => 'Unknown'
};

?>
<div class="row">

    <!-- ===================== -->
    <!-- WELCOME -->
    <!-- ===================== -->
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-2">
                    Welcome back,
                    <?= ucwords($studentData['first_name'] . ' ' . $studentData['other_name'] . ' ' . $studentData['last_name'] ?? 'Student'); ?> 👋
                </h4>
                <p class="mb-0 text-muted">
                    Here is an overview of your academic activities for this semester.
                </p>
            </div>
        </div>
    </div>


    <!-- ===================== -->
    <!-- ALERTS -->
    <!-- ===================== -->
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <?php if (empty($profile['updateProfile'])): ?>

                    <div class="alert alert-danger">
                        📌 Complete your profile to continue.
                        <a href="<?= route('updateStudentProfile', $utility); ?>" class="btn btn-sm btn-danger ms-2">Update</a>
                    </div>

                <?php elseif (empty($status['receipt_uploaded'])): ?>

                    <div class="alert alert-warning">
                        📌 Upload semester payment receipt.
                        <a href="<?= route('uploadReceipt', $utility); ?>" class="btn btn-sm btn-primary ms-2">Upload</a>
                    </div>

                <?php elseif (empty($status['payment_confirmed']) && $status['status'] == "pending"): ?>

                    <div class="alert alert-info">
                        ⏳ Payment under review.
                    </div>

                <?php elseif (empty($status['payment_confirmed']) && $status['status'] == "failed"): ?>

                    <div class="alert alert-warning">
                        📌 Re-upload payment receipt.
                        <a href="<?= route('uploadReceipt', $utility); ?>" class="btn btn-sm btn-primary ms-2">Re-Upload</a>
                    </div>

                <?php elseif (empty($status['course_fee_paid'])): ?>

                    <div class="alert alert-warning">
                        💳 Pay course registration fee.
                        <a href="<?= route('payCourseForm', $utility); ?>" class="btn btn-sm btn-success ms-2">Pay Now</a>
                    </div>

                <?php elseif (empty($status['courses_registered'])): ?>

                    <div class="alert alert-primary">
                        📚 Register your courses.
                        <a href="<?= route('courseRegistration', $utility); ?>" class="btn btn-sm btn-primary ms-2">Register</a>
                    </div>

                <?php else: ?>

                    <div class="alert alert-success">
                        ✅ All steps completed.
                    </div>

                <?php endif; ?>

            </div>
        </div>
    </div>


    <!-- ===================== -->
    <!-- STEP PROGRESS -->
    <!-- ===================== -->
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <h6 class="mb-4 fw-bold"><?= $CurrentSemester; ?> Registration Progress</h6>

                <div class="stepper-wrapper">

                    <div class="stepper-line">
                        <div class="stepper-progress" style="width: <?= $registrationProgress ?>%"></div>
                    </div>

                    <div class="stepper-item <?= $step1 ? 'completed' : ($currentStep == 1 ? 'active' : '') ?>">
                        <div class="step-counter"><?= $step1 ? '✔' : '1' ?></div>
                        <div class="step-name">Profile</div>
                    </div>

                    <div class="stepper-item <?= $step2 ? 'completed' : ($currentStep == 2 ? 'active' : '') ?>">
                        <div class="step-counter"><?= $step2 ? '✔' : '2' ?></div>
                        <div class="step-name">Receipt</div>
                    </div>

                    <div class="stepper-item <?= $step3 ? 'completed' : ($currentStep == 3 ? 'active' : '') ?>">
                        <div class="step-counter"><?= $step3 ? '✔' : '3' ?></div>
                        <div class="step-name">Confirmation</div>
                    </div>

                    <div class="stepper-item <?= $step4 ? 'completed' : ($currentStep == 4 ? 'active' : '') ?>">
                        <div class="step-counter"><?= $step4 ? '✔' : '4' ?></div>
                        <div class="step-name">Course Fee</div>
                    </div>

                    <div class="stepper-item <?= $step5 ? 'completed' : ($currentStep == 5 ? 'active' : '') ?>">
                        <div class="step-counter"><?= $step5 ? '✔' : '5' ?></div>
                        <div class="step-name">Registration</div>
                    </div>

                </div>

            </div>
        </div>
    </div>


    <!-- ===================== -->
    <!-- DASHBOARD STATS -->
    <!-- ===================== -->
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="row g-3 align-items-stretch">

                    <!-- TUITION DUE -->
                    <div class="col-lg-3 col-md-6">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">

                                <div class="d-flex align-items-center">
                                    <div class="avatar bg-light-danger">
                                        <i class="ti ti-currency-naira f-24"></i>
                                    </div>
                                    <div class="ms-3">
                                        <p class="mb-1">Payment Due</p>
                                        <h4>₦<?= number_format($tuitionDue ?? 0); ?></h4>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- AMOUNT PAID -->
                    <div class="col-lg-3 col-md-6">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">

                                <div class="d-flex align-items-center">
                                    <div class="avatar bg-light-success">
                                        <i class="ti ti-wallet f-24"></i>
                                    </div>
                                    <div class="ms-3">
                                        <p class="mb-1">Amount Paid</p>
                                        <h4>₦<?= number_format($amountPaid ?? 0); ?></h4>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- PAYMENT STATUS -->
                    <div class="col-lg-3 col-md-6">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">

                                <div class="d-flex align-items-center mb-2">
                                    <div class="avatar bg-light-info">
                                        <i class="ti ti-report-money f-24"></i>
                                    </div>
                                    <div class="ms-3">
                                        <p class="mb-1">Payment Status</p>
                                        <span class="badge bg-<?= $paymentStatusClass ?>">
                                            <?= $paymentStatus ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-auto">
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-<?= $paymentStatusClass ?>"
                                            style="width: <?= $paymentProgress ?>%">
                                        </div>
                                    </div>

                                    <small>
                                        <?= $paymentProgress ?>% Paid
                                    </small>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- REGISTRATION STATUS -->
                    <div class="col-lg-3 col-md-6">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">

                                <div class="d-flex align-items-center mb-2">
                                    <div class="avatar bg-light-primary">
                                        <i class="ti ti-checklist f-24"></i>
                                    </div>
                                    <div class="ms-3">
                                        <p class="mb-1">Registration</p>
                                        <span class="badge bg-<?= $registrationStatusClass ?>">
                                            <?= $registrationStatus ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-auto">
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-<?= $registrationStatusClass ?>"
                                            style="width: <?= $registrationProgress ?>%">
                                        </div>
                                    </div>

                                    <small>
                                        <?= $registrationProgress ?>% Completed
                                    </small>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- ===================== -->
    <!-- COURSE + ACTIONS -->
    <!-- ===================== -->
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="row g-3 align-items-stretch">

                    <!-- COURSE REG STATUS -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">

                                <div class="d-flex align-items-center mb-2">
                                    <div class="avatar bg-light-primary">
                                        <i class="ti ti-checklist f-24"></i>
                                    </div>

                                    <div class="ms-3">
                                        <p class="mb-1">Course Registration</p>
                                        <span class="badge bg-<?= $courseStatusClass ?>">
                                            <?= $courseStatus ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="progress mb-2">
                                    <div class="progress-bar bg-<?= $courseStatusClass ?>"
                                        style="width: <?= $courseProgress ?>%">
                                    </div>
                                </div>

                                <div class="mt-auto">
                                    <small class="text-muted">
                                        Next: <?= $nextStep ?>
                                    </small>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- QUICK ACTIONS -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5>Quick Actions</h5>
                            </div>
                            <div class="card-body">

                                <?php if (empty($status['course_fee_paid']) && empty($status['courses_registered']) && empty($courseRegistrationStatus)): ?>

                                    <button class="btn btn-secondary w-100 mb-2" disabled>
                                        Course Registration Locked
                                    </button>

                                <?php elseif ($status['course_fee_paid'] == 1 && $status['courses_registered'] == 0): ?>

                                    <a href="<?= route('courseRegistration', $utility); ?>" class="btn btn-primary w-100 mb-2">
                                        Register Courses
                                    </a>

                                <?php elseif ($status['courses_registered'] == 1 && in_array($courseRegistrationStatus['approval_status'] ?? '', ['submitted', 'rejected'])): ?>

                                    <a href="<?= route('editCourseRegistration', $utility); ?>" class="btn btn-warning w-100 mb-2">
                                        Edit Courses
                                    </a>

                                <?php elseif ($status['courses_registered'] == 1 && in_array($courseRegistrationStatus['approval_status'] ?? '', ['approved', 'pending'])): ?>

                                    <a href="<?= route('myCourses', $utility); ?>" class="btn btn-success w-100 mb-2">
                                        My Courses
                                    </a>

                                <?php endif; ?>

                                <a href="<?= route('updateStudentProfile', $utility); ?>" class="btn btn-outline-secondary w-100">
                                    View Profile
                                </a>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>