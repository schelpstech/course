<?php

$current_page = $GLOBALS['pageId'] ?? $_SESSION['admin_pageid'] ?? $_SESSION['pageid'] ?? 'dashboard';

// =========================
// HELPERS
// =========================
function isActive($page, $current_page)
{
    return $page === $current_page ? 'active' : '';
}

function hasAdminRole($roles, $allowedRoles = [])
{
    $roles = is_array($roles) ? $roles : [$roles];
    return count(array_intersect($roles, $allowedRoles)) > 0;
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
$adminRoles = ($isAdmin && isset($rbac)) ? $rbac->roleSlugs((int)$_SESSION['admin_id']) : [];
$role = $adminRoles[0] ?? ($adminData['role'] ?? '');
$portalSuiteLabel = $isAdmin ? 'Admin Suite' : 'Student Suite';
$portalUserName = $isAdmin ? ($adminData['fullname'] ?? $adminData['name'] ?? 'Administrator') : 'Student';
$portalUserContext = $isAdmin ? ($adminData['email'] ?? ucfirst((string)$role)) : 'Student Portal';

if (!$isAdmin && isset($studentData) && is_array($studentData)) {
    $studentName = trim(($studentData['first_name'] ?? '') . ' ' . ($studentData['last_name'] ?? ''));
    $portalUserName = $studentName !== '' ? $studentName : $portalUserName;
    $portalUserContext = $studentData['matric_no'] ?? $portalUserContext;
} elseif (!$isAdmin && isset($profile) && is_array($profile)) {
    $studentName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
    $portalUserName = $studentName !== '' ? $studentName : $portalUserName;
    $portalUserContext = $profile['matric_no'] ?? $portalUserContext;
}

$portalInitialsSource = preg_replace('/[^A-Za-z]/', '', $portalUserName);
$portalInitials = strtoupper(substr($portalInitialsSource ?: 'CP', 0, 2));

function canAccessAdminMenu(array $item, array $roles, $rbac): bool
{
    if (!empty($item['requires_department_scope'])) {
        if (!isset($rbac) || !$rbac->departmentScopeId()) {
            return false;
        }
    }

    if (!empty($item['permissions']) && isset($rbac)) {
        if ($rbac->canAny($item['permissions'])) {
            return true;
        }
    }

    if (!empty($item['permission']) && isset($rbac)) {
        if ($rbac->can($item['permission'])) {
            return true;
        }
    }

    if (!empty($item['roles'])) {
        return hasAdminRole($roles, $item['roles']);
    }

    return empty($item['permissions']) && empty($item['permission']);
}

// =========================
// ADMIN MENU CONFIG
// =========================
$adminMenu = [

    [
        'title' => 'Staff & Access',
        'icon' => 'ph ph-shield-check',
        'permissions' => ['manage_admin_users', 'manage_roles'],
        'children' => [
            ['page' => 'staffUsers', 'label' => 'Staff Users', 'permission' => 'manage_admin_users'],
            ['page' => 'rolesPermissions', 'label' => 'Roles & Permissions', 'permission' => 'manage_roles'],
        ]
    ],

    [
        'title' => 'Communications',
        'icon' => 'ph ph-megaphone',
        'permission' => 'manage_announcements',
        'children' => [
            ['page' => 'announcements', 'label' => 'Announcements', 'permission' => 'manage_announcements'],
        ]
    ],

    [
        'title' => 'Academic Setup',
        'icon' => 'ph ph-graduation-cap',
        'roles' => ['super'],
        'permissions' => ['manage_institutions', 'manage_departments', 'manage_courses'],
        'children' => [
            ['page' => 'institutions', 'label' => 'Institutions', 'permission' => 'manage_institutions'],
            ['page' => 'programs', 'label' => 'Programmes', 'permission' => 'manage_institutions'],
            ['page' => 'departments', 'label' => 'Departments', 'permission' => 'manage_departments'],
            ['page' => 'manageLevels', 'label' => 'Levels', 'permission' => 'manage_departments'],
            ['page' => 'academicSessions', 'label' => 'Sessions', 'permission' => 'manage_institutions'],
            ['page' => 'manageSemesters', 'label' => 'Semesters', 'permission' => 'manage_institutions'],
            ['page' => 'courses', 'label' => 'Courses', 'permission' => 'manage_courses'],
        ]
    ],

    [
        'title' => 'Lecturer Portal',
        'icon' => 'ph ph-chalkboard-teacher',
        'permissions' => ['view_results', 'enter_ca_scores', 'enter_exam_scores', 'submit_scores'],
        'children' => [
            ['page' => 'lecturerDashboard', 'label' => 'Dashboard', 'permissions' => ['view_results', 'enter_ca_scores', 'enter_exam_scores', 'submit_scores']],
            ['page' => 'lecturerScoresheet', 'label' => 'Scoresheet', 'permissions' => ['enter_ca_scores', 'enter_exam_scores', 'submit_scores']],
        ]
    ],

    [
        'title' => 'Department Portal',
        'icon' => 'ph ph-buildings',
        'requires_department_scope' => true,
        'permissions' => ['view_department_students', 'view_course_forms', 'manage_dept_courses', 'allocate_dept_courses', 'moderate_results', 'approve_results'],
        'children' => [
            ['page' => 'departmentDashboard', 'label' => 'Dashboard', 'permissions' => ['view_department_students', 'view_course_forms', 'manage_dept_courses', 'allocate_dept_courses', 'moderate_results', 'approve_results']],
            ['page' => 'departmentStudents', 'label' => 'Students', 'permissions' => ['view_department_students', 'view_students']],
            ['page' => 'departmentCourseForms', 'label' => 'Course Forms', 'permission' => 'view_course_forms'],
            ['page' => 'departmentCourses', 'label' => 'Courses', 'permission' => 'manage_dept_courses'],
            ['page' => 'departmentCourseAllocations', 'label' => 'Course Allocation', 'permission' => 'allocate_dept_courses'],
            ['page' => 'departmentModeration', 'label' => 'Result Moderation', 'permissions' => ['moderate_results', 'approve_results']],
        ]
    ],

    [
        'title' => 'Results',
        'icon' => 'ph ph-exam',
        'permissions' => ['allocate_courses', 'create_result_config', 'manage_grading_rules'],
        'children' => [
            ['page' => 'courseAllocations', 'label' => 'Course Allocation', 'permission' => 'allocate_courses'],
            ['page' => 'resultConfig', 'label' => 'Result Configuration', 'permission' => 'create_result_config'],
            ['page' => 'gradingRules', 'label' => 'Grading Rules', 'permission' => 'manage_grading_rules'],
        ]
    ],

    [
        'title' => 'Payments',
        'icon' => 'ph ph-currency-ngn',
        'roles' => ['bursary', 'super'],
        'permission' => 'manage_payments',
        'children' => [
            ['page' => 'payment_assign', 'label' => 'Assign Payment', 'roles' => ['super']],
            ['page' => 'payment_remark', 'label' => 'Review Payment'],
            ['page' => 'internetPaymentReview', 'label' => 'Internet Payments', 'roles' => ['super']],
            ['page' => 'payment_config', 'label' => 'Payment Config', 'roles' => ['super']]
        ]
    ],

    [
        'title' => 'Clearance Manager',
        'icon' => 'ph ph-clipboard-text',
        'roles' => ['bursary', 'super'],
        'children' => [
            ['page' => 'manage_clearance', 'label' => 'Clearance Manager'],
            ['page' => 'courseformMgr', 'label' => 'Course Forms', 'roles' => ['super']],
            ['page' => 'payment_clearance', 'label' => 'Payment Clearance']
        ]
    ],

    [
        'title' => 'Students',
        'icon' => 'ph ph-student',
        'roles' => ['registry', 'super','bursary'],
        'permission' => 'view_students',
        'children' => [
            ['page' => 'students', 'label' => 'Manage Students', 'permission' => 'view_students'],
            ['page' => 'semregistrationStatus', 'label' => 'Semester Reg Status', 'roles' => ['super']],
        ]
    ],

    [
        'title' => 'Admission',
        'icon' => 'ph ph-identification-card',
        'roles' => ['admission', 'registry', 'super'],
        'permission' => 'manage_admission',
        'children' => [
            ['page' => 'admissionDashboard', 'label' => 'Dashboard'],
            ['page' => 'admissionSessions', 'label' => 'Sessions', 'roles' => ['super', 'admission']],
            ['page' => 'admissionCriteria', 'label' => 'Criteria', 'roles' => ['super', 'admission']],
            ['page' => 'admissionApplications', 'label' => 'Applications'],
        ]
    ],

    [
        'title' => 'Logs & Monitoring',
        'icon' => 'ph ph-clipboard-text',
        'roles' => ['registry', 'bursary', 'log', 'super'],
        'permission' => 'view_audit_logs',
        'children' => [
            ['page' => 'student-trail', 'label' => 'Student Log'],
            ['page' => 'audit-trail', 'label' => 'Audit Trail', 'roles' => ['super']]
        ]
    ]
];

// =========================
// MENU RENDERER
// =========================
function renderMenu($menu, $roles, $current_page, $utility, $rbac)
{
    foreach ($menu as $group) {

        if (!canAccessAdminMenu($group, $roles, $rbac)) {
            continue;
        }

        $visibleChildren = [];

        foreach ($group['children'] as $item) {
            if (canAccessAdminMenu($item, $roles, $rbac)) {
                $visibleChildren[] = $item;
            }
        }

        if (empty($visibleChildren)) {
            continue;
        }

        $pages = array_column($visibleChildren, 'page');
        $isOpen = isMenuOpen($current_page, $pages);

        echo '<li class="pc-item pc-hasmenu ' . $isOpen . '">';
        echo '<a href="#" class="pc-link">';
        echo '<span class="pc-micon"><i class="' . $group['icon'] . '"></i></span>';
        echo '<span class="pc-mtext">' . $group['title'] . '</span>';
        echo '<span class="pc-arrow"><i class="ph ph-caret-down"></i></span>';
        echo '</a>';

        echo '<ul class="pc-submenu">';

        foreach ($visibleChildren as $item) {
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
                <img src="../assets/images/logo.png" class="sidebar-logo" style="width:50px;height:50px;" alt="logo" />
                <span class="portal-brand-copy">
                    <strong>Course Portal</strong>
                    <small><?= htmlspecialchars($portalSuiteLabel); ?></small>
                </span>
            </a>
        </div>

        <div class="navbar-content">
            <div class="portal-sidebar-user">
                <span class="portal-sidebar-avatar"><?= htmlspecialchars($portalInitials); ?></span>
                <span class="portal-sidebar-copy">
                    <strong><?= htmlspecialchars($portalUserName); ?></strong>
                    <small><?= htmlspecialchars($portalUserContext); ?></small>
                </span>
            </div>

            <ul class="pc-navbar">

                <!-- ========================= -->
                <!-- DASHBOARD -->
                <!-- ========================= -->
                <li class="pc-item <?= isActive($isAdmin ? 'adminDashboard' : 'studentDashboard', $current_page); ?>">
                    <a href="<?= $isAdmin ? route('adminDashboard', $utility) : route('studentDashboard', $utility); ?>" class="pc-link">
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

                        <li class="pc-item <?= isActive('uploadReceipt', $current_page); ?>">
                            <a href="<?= route('uploadReceipt', $utility); ?>" class="pc-link">
                                <span class="pc-micon"><i class="ph ph-upload"></i></span>
                                <span class="pc-mtext">Upload Receipt</span>
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
                                <span class="pc-mtext">My Course Form</span>
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

                    <?php renderMenu($adminMenu, $adminRoles, $current_page, $utility, $rbac); ?>


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
