<?php

$step1 = $profile['updateProfile']      ?? null;
$step2 = $status['receipt_uploaded']    ?? null;
$step3 = $status['payment_confirmed']   ?? null;
$step4 = $status['course_fee_paid']     ?? null;
$step5 = $status['courses_registered']  ?? null;


// current step index
$currentStep = 1;

if ($step1) $currentStep = 2;
if ($step2) $currentStep = 3;
if ($step3) $currentStep = 4;
if ($step4) $currentStep = 5;
if ($step5) $currentStep = 5;

// progress %
$progress = (($currentStep - 1) / 5) * 100;
?>

<div class="row">
    <!-- Welcome Section -->
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-2">Welcome back, <?= $_SESSION['fullname'] ?? 'Student'; ?> 👋</h4>
                <p class="mb-0 text-muted">
                    Here is an overview of your academic activities for this semester.
                </p>
            </div>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <?php if (empty($profile['updateProfile'])): ?>
                    <div class="alert alert-danger">
                        📌 You need to complete your profile information to continue.
                        <a href="<?= route('updateStudentProfile', $utility); ?>" class="btn btn-sm btn-danger ms-2">Update Student Profile</a>
                    </div>

                <?php elseif (empty($status['receipt_uploaded'])): ?>
                    <div class="alert alert-warning">
                        📌 You need to upload your semester payment receipt to continue.
                        <a href="<?= route('uploadReceipt', $utility); ?>" class="btn btn-sm btn-primary ms-2">Upload Now</a>
                    </div>

                <?php elseif (empty($status['payment_confirmed']) && $status['status'] == "pending"): ?>
                    <div class="alert alert-info">
                        ⏳ Your payment is under review by the bursary. Please check back later.
                    </div>

                <?php elseif (empty($status['payment_confirmed']) && $status['status'] == "failed"): ?>
                    <div class="alert alert-warning">
                        📌 You need to reupload your semester payment receipt to continue.
                        <a href="<?= route('uploadReceipt', $utility); ?>" class="btn btn-sm btn-primary ms-2">Re-Upload Now</a>
                    </div>

                <?php elseif (empty($status['course_fee_paid'])): ?>
                    <div class="alert alert-warning">
                        💳 Pay for this Semester Internet Subscription / Course Registration Fee to proceed.
                        <a href="<?= route('payCourseForm', $utility); ?>" class="btn btn-sm btn-success ms-2">Pay Now</a>
                    </div>

                <?php elseif (empty($status['courses_registered'])): ?>
                    <div class="alert alert-primary">
                        📚 You can now register your courses.
                        <a href="<?= route('courseRegistration', $utility); ?>" class="btn btn-sm btn-primary ms-2">Register Courses</a>
                    </div>

                <?php else: ?>
                    <div class="alert alert-success">
                        ✅ You have completed all steps for this semester.
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <h6 class="mb-4 fw-bold"> <?= $CurrentSemester; ?> Registration Progress</h6>

                <div class="stepper-wrapper">

                    <!-- PROGRESS LINE -->
                    <div class="stepper-line">
                        <div class="stepper-progress" style="width: <?= $progress ?>%"></div>
                    </div>

                    <!-- STEP 1 -->
                    <div class="stepper-item <?= $step1 ? 'completed' : ($currentStep == 1 ? 'active' : '') ?>">
                        <div class="step-counter">
                            <?= $step1 ? '<i class="feather icon-check"></i>' : '1' ?>
                        </div>
                        <div class="step-name">Profile</div>
                    </div>

                    <!-- STEP 2 -->
                    <div class="stepper-item <?= $step2 ? 'completed' : ($currentStep == 2 ? 'active' : '') ?>">
                        <div class="step-counter">
                            <?= $step2 ? '<i class="feather icon-check"></i>' : '2' ?>
                        </div>
                        <div class="step-name">Receipt</div>
                    </div>

                    <!-- STEP 3 -->
                    <div class="stepper-item <?= $step3 ? 'completed' : ($currentStep == 3 ? 'active' : '') ?>">
                        <div class="step-counter">
                            <?= $step3 ? '<i class="feather icon-check"></i>' : '3' ?>
                        </div>
                        <div class="step-name">Confirmation</div>
                    </div>

                    <!-- STEP 4 -->
                    <div class="stepper-item <?= $step4 ? 'completed' : ($currentStep == 4 ? 'active' : '') ?>">
                        <div class="step-counter">
                            <?= $step4 ? '<i class="feather icon-check"></i>' : '4' ?>
                        </div>
                        <div class="step-name">Course Fee</div>
                    </div>

                    <!-- STEP 5 -->
                    <div class="stepper-item <?= $step5 ? 'completed' : ($currentStep == 5 ? 'active' : '') ?>">
                        <div class="step-counter">
                            <?= $step5 ? '<i class="feather icon-check"></i>' : '5' ?>
                        </div>
                        <div class="step-name">Registration</div>
                    </div>

                </div>

                <!-- STEP DESCRIPTION -->
                <div class="step-description mt-4 text-center">

                    <?php if ($currentStep == 1): ?>
                        <small>Fill in your personal and academic details.</small>

                    <?php elseif ($currentStep == 2): ?>
                        <small>Upload your payment receipt for verification.</small>

                    <?php elseif ($currentStep == 3): ?>
                        <small>Wait for payment confirmation by the school.</small>

                    <?php elseif ($currentStep == 4): ?>
                        <small>Pay your Semester Internet Subscription / Course Registration Fee.</small>

                    <?php elseif ($currentStep == 5): ?>
                        <small>Select and submit your courses.</small>
                    <?php endif; ?>

                </div>

            </div>
        </div>
    </div>

    <!-- Dashboard Stats -->
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar bg-light-primary">
                            <i class="ti ti-book f-24"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="mb-1">Registered Courses</p>
                        <h4 class="mb-0"><?= $data['registered_courses'] ?? 0; ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar bg-light-success">
                            <i class="ti ti-check f-24"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="mb-1">Completed Courses</p>
                        <h4 class="mb-0"><?= $data['completed_courses'] ?? 0; ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar bg-light-warning">
                            <i class="ti ti-clock f-24"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="mb-1">Pending Courses</p>
                        <h4 class="mb-0"><?= $data['pending_courses'] ?? 0; ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avatar bg-light-danger">
                            <i class="ti ti-report f-24"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="mb-1">Current GPA</p>
                        <h4 class="mb-0"><?= $data['gpa'] ?? '0.00'; ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Semester Registration Progress -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Course Registration Progress</h5>
            </div>
            <div class="card-body">
                <?php
                $progress = $data['registration_progress'] ?? 0;
                ?>
                <div class="progress mb-2" style="height: 20px">
                    <div class="progress-bar bg-success" role="progressbar"
                        style="width: <?= $progress; ?>%"
                        aria-valuenow="<?= $progress; ?>"
                        aria-valuemin="0"
                        aria-valuemax="100">
                        <?= $progress; ?>%
                    </div>
                </div>
                <small class="text-muted">
                    <?= $progress; ?>% of your courses registered for this semester
                </small>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">

            <?php if (empty($status['course_fee_paid']) && empty($status['courses_registered']) && empty($courseRegistrationStatus)): ?>

                    <button class="btn btn-secondary w-100 mb-2" disabled>
                        <i class="fa fa-lock"></i> Course Registration Disabled
                    </button>

                <?php elseif ($status['course_fee_paid'] == 1 && $status['courses_registered'] == 0 && empty($courseRegistrationStatus)): ?>

                    <a href="<?= route('courseRegistration', $utility); ?>" class="btn btn-primary w-100 mb-2">
                        Register Courses
                    </a>

                <?php elseif ($status['courses_registered'] == 1 && !empty($courseRegistrationStatus) && in_array($courseRegistrationStatus['approval_status'], ['submitted', 'rejected'])): ?>

                    <a href="<?= route('editCourseRegistration', $utility); ?>" class="btn btn-warning w-100 mb-2">
                        Edit Course Form
                    </a>

                <?php elseif ($status['courses_registered'] == 1 && !empty($courseRegistrationStatus) && in_array($courseRegistrationStatus['approval_status'], ['approved', 'pending'])): ?>

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