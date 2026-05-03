<?php

$current_page = $_SESSION['pageid'] ?? 'dashboard';

// helpers
function isActive($page, $current_page)
{
    return $page === $current_page ? 'active' : '';
}

$isAdmin = isset($_SESSION['admin_id']);
$isStudent = isset($_SESSION['user_id']);
?>

<nav class="pc-sidebar">
    <div class="navbar-wrapper">

        <!-- LOGO -->
        <div class="m-header">
            <a href="<?= $isAdmin ? route('adminDashboard', $utility) : route('studentDashboard', $utility); ?>" class="b-brand">
                <img src="../assets/images/logo.png" class="sidebar-logo" style="width: 50px; height: 50px;" alt="logo" />
            </a>
        </div>

        <div class="navbar-content">
            <ul class="pc-navbar">

                <!-- ========================= -->
                <!-- COMMON DASHBOARD -->
                <!-- ========================= -->
                <li class="pc-item <?= isActive($isAdmin ? 'adminDashboard' : 'students', $current_page); ?>">
                    <a href="<?= $isAdmin ? route('adminDashboard', $utility) : route('students', $utility); ?>" class="pc-link">
                        <span class="pc-micon"><i class="ph ph-house-line"></i></span>
                        <span class="pc-mtext">Dashboard</span>
                    </a>
                </li>

                <!-- ========================= -->
                <!-- STUDENT MENU -->
                <!-- ========================= -->
                <?php if ($isStudent): ?>

                    <?php if (empty($profile['updateProfile'])): ?>

                        <!-- ONLY PROFILE -->
                        <li class="pc-item <?= isActive('updateStudentProfile', $current_page); ?>">
                            <a href="<?= route('updateStudentProfile', $utility); ?>" class="pc-link">
                                <span class="pc-micon"><i class="ph ph-user"></i></span>
                                <span class="pc-mtext">Update Profile</span>
                            </a>
                        </li>

                    <?php elseif (empty($status['receipt_uploaded'])): ?>

                        <!-- ONLY RECEIPT -->
                        <li class="pc-item <?= isActive('uploadReceipt', $current_page); ?>">
                            <a href="<?= route('uploadReceipt', $utility); ?>" class="pc-link">
                                <span class="pc-micon"><i class="ph ph-upload"></i></span>
                                <span class="pc-mtext">Upload Receipt</span>
                            </a>
                        </li>

                    <?php elseif (empty($status['payment_confirmed'])): ?>

                        <!-- WAITING STATE -->
                        <li class="pc-item">
                            <a href="#" class="pc-link">
                                <span class="pc-micon"><i class="ph ph-hourglass"></i></span>
                                <span class="pc-mtext">Payment Under Review</span>
                            </a>
                        </li>

                    <?php elseif (empty($status['course_fee_paid'])): ?>

                        <!-- PAY COURSE -->
                        <li class="pc-item <?= isActive('payCourseForm', $current_page); ?>">
                            <a href="<?= route('payCourseForm', $utility); ?>" class="pc-link">
                                <span class="pc-micon"><i class="ph ph-credit-card"></i></span>
                                <span class="pc-mtext">Pay Course Fee</span>
                            </a>
                        </li>

                    <?php elseif (empty($status['courses_registered'])): ?>

                        <!-- COURSE REGISTRATION -->
                        <li class="pc-item <?= isActive('courseRegistration', $current_page); ?>">
                            <a href="<?= route('courseRegistration', $utility); ?>" class="pc-link">
                                <span class="pc-micon"><i class="ph ph-book-open"></i></span>
                                <span class="pc-mtext">Course Registration</span>
                            </a>
                        </li>

                    <?php else: ?>

                        <li class="pc-item <?= isActive('updateStudentProfile', $current_page); ?>">
                            <a href="<?= route('updateStudentProfile', $utility); ?>" class="pc-link">
                                <span class="pc-micon"><i class="ph ph-user"></i></span>
                                <span class="pc-mtext">Update Profile</span>
                            </a>
                        </li>

                        <!-- FULL ACCESS -->
                        <li class="pc-item <?= isActive('courseRegistration', $current_page); ?>">
                            <a href="<?= route('courseRegistration', $utility); ?>" class="pc-link">
                                <span class="pc-micon"><i class="ph ph-book-open"></i></span>
                                <span class="pc-mtext">Course Registration</span>
                            </a>
                        </li>

                        <li class="pc-item <?= isActive('myCourses', $current_page); ?>">
                            <a href="<?= route('myCourses', $utility); ?>" class="pc-link">
                                <span class="pc-micon"><i class="ph ph-list-checks"></i></span>
                                <span class="pc-mtext">My Courses</span>
                            </a>
                        </li>

                        <li class="pc-item <?= isActive('transactionHistory', $current_page); ?>">
                            <a href="<?= route('transactionHistory', $utility); ?>" class="pc-link">
                                <span class="pc-micon"><i class="ph ph-receipt"></i></span>
                                <span class="pc-mtext">Transactions</span>
                            </a>
                        </li>

                    <?php endif; ?>

                <?php endif; ?>

                <!-- ========================= -->
                <!-- ADMIN MENU -->
                <!-- ========================= -->
                <?php if ($isAdmin): ?>

                    <li class="pc-item pc-caption">
                        <label>Administration</label>
                    </li>

                    <li class="pc-item <?= isActive('institutions', $current_page); ?>">
                        <a href="<?= route('institutions', $utility); ?>" class="pc-link">
                            <span class="pc-micon"><i class="ph ph-bank"></i></span>
                            <span class="pc-mtext">Manage Institution</span>
                        </a>
                    </li>

                    <li class="pc-item <?= isActive('programs', $current_page); ?>">
                        <a href="<?= route('programs', $utility); ?>" class="pc-link">
                            <span class="pc-micon"><i class="ph ph-graduation-cap"></i></span>
                            <span class="pc-mtext">Manage Programmes</span>
                        </a>
                    </li>

                    <li class="pc-item <?= isActive('departments', $current_page); ?>">
                        <a href="<?= route('departments', $utility); ?>" class="pc-link">
                            <span class="pc-micon"><i class="ph ph-building-office"></i></span>
                            <span class="pc-mtext">Manage Departments</span>
                        </a>
                    </li>

                    <li class="pc-item <?= isActive('manageLevels', $current_page); ?>">
                        <a href="<?= route('manageLevels', $utility); ?>" class="pc-link">
                            <span class="pc-micon"><i class="ph ph-stack"></i></span>
                            <span class="pc-mtext">Manage Programme Level</span>
                        </a>
                    </li>

                    <li class="pc-item <?= isActive('students', $current_page); ?>">
                        <a href="<?= route('students', $utility); ?>" class="pc-link">
                            <span class="pc-micon"><i class="ph ph-student"></i></span>
                            <span class="pc-mtext">Manage Students</span>
                        </a>
                    </li>

                    <li class="pc-item <?= isActive('academicSessions', $current_page); ?>">
                        <a href="<?= route('academicSessions', $utility); ?>" class="pc-link">
                            <span class="pc-micon"><i class="ph ph-calendar"></i></span>
                            <span class="pc-mtext">Academic Session</span>
                        </a>
                    </li>

                    <li class="pc-item <?= isActive('manageSemesters', $current_page); ?>">
                        <a href="<?= route('manageSemesters', $utility); ?>" class="pc-link">
                            <span class="pc-micon"><i class="ph ph-calendar-check"></i></span>
                            <span class="pc-mtext">Manage Semester</span>
                        </a>
                    </li>

                    <li class="pc-item <?= isActive('courses', $current_page); ?>">
                        <a href="<?= route('courses', $utility); ?>" class="pc-link">
                            <span class="pc-micon"><i class="ph ph-book-open"></i></span>
                            <span class="pc-mtext">Manage Courses</span>
                        </a>
                    </li>

                    <li class="pc-item <?= isActive('payment_config', $current_page); ?>">
                        <a href="<?= route('payment_config', $utility); ?>" class="pc-link">
                            <span class="pc-micon"><i class="ph ph-currency-ngn"></i></span>
                            <span class="pc-mtext">Payment Config</span>
                        </a>
                    </li>

                    <li class="pc-item <?= isActive('payment_assign', $current_page); ?>">
                        <a href="<?= route('payment_assign', $utility); ?>" class="pc-link">
                            <span class="pc-micon"><i class="ph ph-currency-ngn"></i></span>
                            <span class="pc-mtext">Assign Payment</span>
                        </a>
                    </li>

                    <li class="pc-item <?= isActive('payment_remark', $current_page); ?>">
                        <a href="<?= route('payment_remark', $utility); ?>" class="pc-link">
                            <span class="pc-micon"><i class="ph ph-currency-ngn"></i></span>
                            <span class="pc-mtext">Review Payment</span>
                        </a>
                    </li>

                    <li class="pc-item <?= isActive('change-password', $current_page); ?>">
                        <a href="<?= route('change-password', $utility); ?>" class="pc-link">
                            <span class="pc-micon"><i class="ph ph-clipboard-text"></i></span>
                            <span class="pc-mtext">Change Password</span>
                        </a>
                    </li>
                    <li class="pc-item <?= isActive('audit-trail', $current_page); ?>">
                        <a href="<?= route('audit-trail', $utility); ?>" class="pc-link">
                            <span class="pc-micon"><i class="ph ph-clipboard-text"></i></span>
                            <span class="pc-mtext">Audit Trail</span>
                        </a>
                    </li>
                    <li class="pc-item <?= isActive('student-trail', $current_page); ?>">
                        <a href="<?= route('student-trail', $utility); ?>" class="pc-link">
                            <span class="pc-micon"><i class="ph ph-clipboard-text"></i></span>
                            <span class="pc-mtext">Student Log</span>
                        </a>
                    </li>

                <?php endif; ?>

                <!-- ========================= -->
                <!-- LOGOUT -->
                <!-- ========================= -->
                <li class="pc-item">
                    <form method="POST" action="../api/logout.php" class="px-3 py-2">
                        <input type="hidden" name="csrf_token" value="<?= $utility->secureEncode('logout'); ?>">
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="ph ph-sign-out"></i> Logout
                        </button>
                    </form>
                </li>

            </ul>
        </div>

    </div>
</nav>