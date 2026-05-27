<?php

$current_page = $_SESSION['pageid'] ?? 'dashboard';

// =========================
// HELPERS
// =========================
function isActive($page, $current_page)
{
    return $page === $current_page ? 'active' : '';
}

function hasAdminRole($role, $allowedRoles = [])
{
    return in_array($role, $allowedRoles);
}


function isMenuOpen(string $current_page, array $pages = []): string
{
    return in_array($current_page, $pages) ? 'active pc-trigger' : '';
}
// =========================
// USER CONTEXT
// =========================
$isAdmin = isset($_SESSION['admin_id']);
$isStudent = isset($_SESSION['user_id']);

$adminData = $isAdmin ? $adminModel->getadminById($_SESSION['admin_id']) : null;
$role = $adminData['role'] ?? '';

// =========================
// ADMIN MENU CONFIG
// =========================
$adminMenu = [

    [
        'title' => 'Academic Setup',
        'icon' => 'ph ph-graduation-cap',
        'roles' => ['super'],
        'children' => [
            ['page' => 'institutions', 'label' => 'Institutions'],
            ['page' => 'programs', 'label' => 'Programmes'],
            ['page' => 'departments', 'label' => 'Departments'],
            ['page' => 'manageLevels', 'label' => 'Levels'],
            ['page' => 'academicSessions', 'label' => 'Sessions'],
            ['page' => 'manageSemesters', 'label' => 'Semesters'],
            ['page' => 'courses', 'label' => 'Courses'],
        ]
    ],

    [
        'title' => 'Payments',
        'icon' => 'ph ph-currency-ngn',
        'roles' => ['bursary', 'super'],
        'children' => [
            ['page' => 'payment_assign', 'label' => 'Assign Payment', 'roles' => ['super']],
            ['page' => 'payment_remark', 'label' => 'Review Payment'],
            ['page' => 'internetPaymentReview', 'label' => 'Internet Payments', 'roles' => ['super']],
            ['page' => 'payment_config', 'label' => 'Payment Config', 'roles' => ['super']]
        ]
    ],

    [
        'title' => 'Students',
        'icon' => 'ph ph-student',
        'roles' => ['registry', 'super'],
        'children' => [
            ['page' => 'students', 'label' => 'Manage Students'],
            ['page' => 'courseformMgr', 'label' => 'Course Forms', 'roles' => ['super']],
        ]
    ],

    [
        'title' => 'Logs & Monitoring',
        'icon' => 'ph ph-clipboard-text',
        'roles' => ['registry', 'bursary', 'log', 'super'],
        'children' => [
            ['page' => 'student-trail', 'label' => 'Student Log'],
            ['page' => 'audit-trail', 'label' => 'Audit Trail', 'roles' => ['super']]
        ]
    ]
];

// =========================
// MENU RENDERER
// =========================
function renderMenu($menu, $role, $current_page, $utility)
{
    foreach ($menu as $group) {

        if (isset($group['roles']) && !in_array($role, $group['roles'])) {
            continue;
        }

        $pages = array_column($group['children'], 'page');
        $isOpen = isMenuOpen($current_page, $pages);

        echo '<li class="pc-item pc-hasmenu ' . $isOpen . '">';
        echo '<a href="#" class="pc-link">';
        echo '<span class="pc-micon"><i class="' . $group['icon'] . '"></i></span>';
        echo '<span class="pc-mtext">' . $group['title'] . '</span>';
        echo '<span class="pc-arrow"><i class="ph ph-caret-down"></i></span>';
        echo '</a>';

        echo '<ul class="pc-submenu">';

        foreach ($group['children'] as $item) {

            if (isset($item['roles']) && !in_array($role, $item['roles'])) {
                continue;
            }

            $active = isActive($item['page'], $current_page);

            echo '<li class="pc-item ' . $active . '">';
            echo '<a href="' . route($item['page'], $utility) . '" class="pc-link">';
            echo $item['label'];
            echo '</a>';
            echo '</li>';
        }

        echo '</ul>';
        echo '</li>';
    }
}
?>

<nav class="pc-sidebar">
    <div class="navbar-wrapper">

        <!-- LOGO -->
        <div class="m-header">
            <a href="<?= $isAdmin ? route('adminDashboard', $utility) : route('studentDashboard', $utility); ?>" class="b-brand">
                <img src="/assets/images/logo.png" class="sidebar-logo" style="width:50px;height:50px;" alt="logo" />
            </a>
        </div>

        <div class="navbar-content">
            <ul class="pc-navbar">

                <!-- ========================= -->
                <!-- DASHBOARD -->
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

                    <li class="pc-item <?= isActive('transactionHistory', $current_page); ?>">
                        <a href="<?= route('transactionHistory', $utility); ?>" class="pc-link">
                            <span class="pc-micon"><i class="ph ph-receipt"></i></span>
                            <span class="pc-mtext">Transactions</span>
                        </a>
                    </li>

                    <?php if (empty($profile['updateProfile'])): ?>

                        <li class="pc-item <?= isActive('updateStudentProfile', $current_page); ?>">
                            <a href="<?= route('updateStudentProfile', $utility); ?>" class="pc-link">
                                <span class="pc-micon"><i class="ph ph-user"></i></span>
                                <span class="pc-mtext">Update Profile</span>
                            </a>
                        </li>

                    <?php elseif (empty($status['receipt_uploaded'])): ?>

                        <li class="pc-item <?= isActive('uploadReceipt', $current_page); ?>">
                            <a href="<?= route('uploadReceipt', $utility); ?>" class="pc-link">
                                <span class="pc-micon"><i class="ph ph-upload"></i></span>
                                <span class="pc-mtext">Upload Receipt</span>
                            </a>
                        </li>

                    <?php elseif (empty($status['payment_confirmed'])): ?>

                        <li class="pc-item">
                            <a href="#" class="pc-link">
                                <span class="pc-micon"><i class="ph ph-hourglass"></i></span>
                                <span class="pc-mtext">Payment Under Review</span>
                            </a>
                        </li>

                    <?php elseif (empty($status['course_fee_paid'])): ?>

                        <li class="pc-item <?= isActive('payCourseForm', $current_page); ?>">
                            <a href="<?= route('payCourseForm', $utility); ?>" class="pc-link">
                                <span class="pc-micon"><i class="ph ph-credit-card"></i></span>
                                <span class="pc-mtext">Pay Course Fee</span>
                            </a>
                        </li>

                    <?php elseif (empty($status['courses_registered'])): ?>

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

                    <?php endif; ?>

                <?php endif; ?>

                <!-- ========================= -->
                <!-- ADMIN MENU -->
                <!-- ========================= -->
                <?php if ($isAdmin): ?>

                    <li class="pc-item pc-caption">
                        <label>Administration</label>
                    </li>

                    <?php renderMenu($adminMenu, $role, $current_page, $utility); ?>


                <?php endif; ?>

                <!-- ========================= -->
                <!-- LOGOUT -->
                <!-- ========================= -->

                <li class="pc-item <?= isActive('change-password', $current_page); ?>">
                    <a href="<?= route('change-password', $utility); ?>" class="pc-link">
                        <span class="pc-micon"><i class="ph ph-key"></i></span>
                        <span class="pc-mtext">Change Password</span>
                    </a>
                </li>
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