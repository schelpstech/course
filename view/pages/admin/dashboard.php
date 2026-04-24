<?php

// ==========================
// ADMIN DATA
// ==========================
$admin_id = $_SESSION['admin_id'];
$admin = $adminModel->getadminById($admin_id);
$students = $adminModel->getStudents();
$payments = $adminModel->getPayments();

// ==========================
// STATS
// ==========================
$totalStudents = $adminModel->countStudents();
$totalCourses  = $adminModel->countCourses();
$totalPayments = $adminModel->countPayments();

// ==========================
// SIMPLE ADMIN PROGRESS (SYSTEM HEALTH STYLE)
// ==========================
$step1 = $totalStudents > 0;
$step2 = $totalCourses > 0;
$step3 = $totalPayments > 0;

$currentStep = 1;
if ($step1) $currentStep = 2;
if ($step2) $currentStep = 3;
if ($step3) $currentStep = 4;

$progress = (($currentStep - 1) / 3) * 100;

?>

<div class="row">

    <!-- ========================== -->
    <!-- WELCOME (STUDENT STYLE) -->
    <!-- ========================== -->
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-2">
                    Welcome back, <?= htmlspecialchars($admin['fullname'] ?? 'Admin'); ?> 👋
                </h4>
                <p class="mb-0 text-muted">
                    Here is a quick overview of your system performance.
                </p>
            </div>
        </div>
    </div>

    <!-- ========================== -->
    <!-- SYSTEM STATUS ALERT (LIKE STUDENT ALERT FLOW) -->
    <!-- ========================== -->
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <?php if ($totalStudents == 0): ?>
                    <div class="alert alert-warning">
                        ⚠️ No students registered yet.
                    </div>

                <?php elseif ($totalCourses == 0): ?>
                    <div class="alert alert-warning">
                        ⚠️ No courses created yet.
                    </div>

                <?php elseif ($totalPayments == 0): ?>
                    <div class="alert alert-info">
                        ℹ️ No payments recorded yet.
                    </div>

                <?php else: ?>
                    <div class="alert alert-success">
                        ✅ System is fully active and running.
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- ========================== -->
    <!-- ADMIN SYSTEM PROGRESS (STUDENT STEPPER STYLE) -->
    <!-- ========================== -->
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <h6 class="mb-4 fw-bold">System Setup Progress</h6>

                <div class="stepper-wrapper">

                    <!-- LINE -->
                    <div class="stepper-line">
                        <div class="stepper-progress" style="width: <?= $progress ?>%"></div>
                    </div>

                    <!-- STEP 1 -->
                    <div class="stepper-item <?= $step1 ? 'completed' : ($currentStep == 1 ? 'active' : '') ?>">
                        <div class="step-counter">
                            <?= $step1 ? '<i class="feather icon-check"></i>' : '1' ?>
                        </div>
                        <div class="step-name">Students</div>
                    </div>

                    <!-- STEP 2 -->
                    <div class="stepper-item <?= $step2 ? 'completed' : ($currentStep == 2 ? 'active' : '') ?>">
                        <div class="step-counter">
                            <?= $step2 ? '<i class="feather icon-check"></i>' : '2' ?>
                        </div>
                        <div class="step-name">Courses</div>
                    </div>

                    <!-- STEP 3 -->
                    <div class="stepper-item <?= $step3 ? 'completed' : ($currentStep == 3 ? 'active' : '') ?>">
                        <div class="step-counter">
                            <?= $step3 ? '<i class="feather icon-check"></i>' : '3' ?>
                        </div>
                        <div class="step-name">Payments</div>
                    </div>

                </div>

                <div class="text-center mt-3">
                    <small class="text-muted">
                        <?= round($progress) ?>% system setup completion
                    </small>
                </div>

            </div>
        </div>
    </div>

    <!-- ========================== -->
    <!-- KPI CARDS (STUDENT STYLE CARDS) -->
    <!-- ========================== -->

    <div class="col-lg-4 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">

                    <div class="avatar bg-light-primary">
                        <i class="ti ti-users f-24"></i>
                    </div>

                    <div class="ms-3">
                        <p class="mb-1">Total Students</p>
                        <h4 class="mb-0"><?= $totalStudents ?></h4>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">

                    <div class="avatar bg-light-success">
                        <i class="ti ti-book f-24"></i>
                    </div>

                    <div class="ms-3">
                        <p class="mb-1">Total Courses</p>
                        <h4 class="mb-0"><?= $totalCourses ?></h4>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">

                    <div class="avatar bg-light-warning">
                        <i class="ti ti-credit-card f-24"></i>
                    </div>

                    <div class="ms-3">
                        <p class="mb-1">Total Payments</p>
                        <h4 class="mb-0"><?= $totalPayments ?></h4>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- ========================== -->
    <!-- RECENT ACTIVITY (STUDENT STYLE LIST CARDS) -->
    <!-- ========================== -->

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Recent Students</h5>
            </div>
            <div class="card-body">

                <?php foreach (array_slice($students, 0, 5) as $student): ?>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] ?? 'N/A'); ?></span>
                        <small class="text-muted">ID: <?= $student['matric_no']; ?></small>
                        <span><?= htmlspecialchars($student['created_at'] ?? ''); ?></span>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Recent Payments</h5>
            </div>
            <div class="card-body">

                <?php foreach (array_slice($payments, 0, 5) as $payment): ?>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span><?= htmlspecialchars($payment['paymentReference'] ?? 'TXN'); ?></span>
                        
                        <strong>₦<?= number_format($payment['amount_paid'] ?? 0); ?></strong>
                        <span><?= htmlspecialchars($payment['created_at'] ?? ''); ?></span>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    </div>

</div>