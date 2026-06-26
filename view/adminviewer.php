<?php
require_once '../start.inc.php';
require_once '../api/adminQuery.php';

// ==========================
// 🔐 ADMIN AUTH CHECK
// ==========================
if (!isset($_SESSION['admin_id'])) {

    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Admin login required.'
    ];
    header("Location: ../console.php");
    exit;
}

// ==========================
// 🔐 FINGERPRINT CHECK (FIXED)
// ==========================
$currentFingerprint = hash(
    'sha256',
    $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']
);

if (!isset($_SESSION['admin_fingerprint'])) {
    $_SESSION['admin_fingerprint'] = $currentFingerprint;
}

if ($_SESSION['admin_fingerprint'] !== $currentFingerprint) {
    session_destroy();
    session_start();
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Session mismatch. Please login again.'
    ];

    header("Location: ../console.php");
    exit;
}

// ==========================
// DEFAULT VALUES
// ==========================
$pageId = 'adminDashboard';
$pageTitle = 'Dashboard';
$pageDescription = '';
$pageModule = 'General';

// ==========================
// DECODE PAYLOAD FROM ROUTER
// ==========================
if (!empty($_GET['pageid'])) {

    $decoded = $utility->secureDecode($_GET['pageid']);

    if ($decoded) {

        if (is_array($decoded)) {

            $pageId = $decoded['page'] ?? 'adminDashboard';
            $pageTitle = $decoded['title'] ?? 'Admin Panel';
            $pageDescription = $decoded['description'] ?? '';
            $pageModule = $decoded['module'] ?? 'General';
        } else {
            // fallback for legacy links
            $pageId = $decoded;
        }
    }
}

// ==========================
// 🔒 FORCE PASSWORD CHANGE
// ==========================
if (
    !empty($_SESSION['force_password_change']) &&
    $pageId !== 'change-password'
) {

    header("Location: ../adminrouter.php?pageid=" . $utility->secureEncode('change-password'));
    exit;
}

// ==========================
// ADMIN PAGE MAP
// ==========================
$pages = [

    'adminDashboard' => './pages/admin/dashboard.php',

    'institutions'   => './pages/admin/forms/manageInstitution.php',

    'programs'       => './pages/admin/forms/managePrograms.php',

    'departments'    => './pages/admin/forms/manageDepartments.php',

    'manageLevels'   => './pages/admin/forms/manageLevels.php',

    'academicSessions' => './pages/admin/forms/academicSessions.php',

    'manageSemesters' => './pages/admin/forms/manageSemester.php',

    'students'       => './pages/admin/manageStudents.php',

    'studentView'    => './pages/students/view.php',

    'courses'        => './pages/admin/forms/courseManager.php',

    'staffUsers' => './pages/admin/staff/staffUsers.php',

    'rolesPermissions' => './pages/admin/staff/rolesPermissions.php',

    'courseAllocations' => './pages/admin/results/courseAllocations.php',

    'resultConfig' => './pages/admin/results/resultConfig.php',

    'gradingRules' => './pages/admin/results/gradingRules.php',

    'lecturerDashboard' => './pages/admin/results/lecturerDashboard.php',

    'lecturerScoresheet' => './pages/admin/results/lecturerScoresheet.php',

    'departmentDashboard' => './pages/admin/department/dashboard.php',

    'departmentStudents' => './pages/admin/department/students.php',

    'departmentCourseForms' => './pages/admin/department/courseForms.php',

    'departmentCourses' => './pages/admin/department/courses.php',

    'departmentModeration' => './pages/admin/department/moderation.php',

    'payment_assign' => './pages/admin/payment/paymentAssign.php',

    'payment_config' => './pages/admin/payment/paymentConfig.php',

    'payment_remark' => './pages/admin/payment/paymentReview.php',

    'registrations'  => './pages/registrations/index.php',

    'change-password' => './pages/admin/changePassword.php',

    'audit-trail' => './pages/admin/report/auditTrail.php',

    'student-trail' => './pages/admin/report/auditrailStudent.php',

    'semregistrationStatus' => './pages/admin/report/semRegistrationStatus.php',

    'courseformMgr' => './pages/admin/report/courseformmgr.php',

    'internetPaymentReview' => './pages/admin/payment/internetPaymentReview.php',

    'manage_clearance' => './pages/admin/forms/manageclearance.php',

    'payment_clearance' => './pages/admin/payment/paymentclearance.php',

    'admissionDashboard' => './pages/admin/admission/dashboard.php',

    'admissionSessions' => './pages/admin/admission/sessions.php',

    'admissionApplications' => './pages/admin/admission/applications.php',

    'admissionCriteria' => './pages/admin/admission/criteria.php'
];

$pagePermissions = [
    'institutions' => 'manage_institutions',
    'programs' => 'manage_institutions',
    'departments' => 'manage_departments',
    'manageLevels' => 'manage_departments',
    'academicSessions' => 'manage_institutions',
    'manageSemesters' => 'manage_institutions',
    'students' => 'view_students',
    'courses' => 'manage_courses',
    'courseformMgr' => 'view_course_forms',
    'staffUsers' => 'manage_admin_users',
    'rolesPermissions' => 'manage_roles',
    'courseAllocations' => 'allocate_courses',
    'resultConfig' => 'create_result_config',
    'gradingRules' => 'manage_grading_rules',
    'lecturerDashboard' => ['view_results', 'enter_ca_scores', 'enter_exam_scores', 'submit_scores'],
    'lecturerScoresheet' => ['enter_ca_scores', 'enter_exam_scores', 'submit_scores'],
    'departmentDashboard' => ['view_department_students', 'view_course_forms', 'manage_courses', 'allocate_courses', 'moderate_results', 'approve_results'],
    'departmentStudents' => ['view_department_students', 'view_students'],
    'departmentCourseForms' => 'view_course_forms',
    'departmentCourses' => 'manage_courses',
    'departmentModeration' => ['moderate_results', 'approve_results'],
    'payment_assign' => 'manage_payments',
    'payment_config' => 'manage_payments',
    'payment_remark' => 'manage_payments',
    'internetPaymentReview' => 'manage_payments',
    'registrations' => 'view_course_forms',
    'audit-trail' => 'view_audit_logs',
    'student-trail' => 'view_audit_logs',
    'semregistrationStatus' => 'view_students',
    'manage_clearance' => 'manage_payments',
    'payment_clearance' => 'manage_payments',
    'admissionDashboard' => 'manage_admission',
    'admissionSessions' => 'manage_admission',
    'admissionApplications' => 'manage_admission',
    'admissionCriteria' => 'manage_admission'
];

// ==========================
// VALIDATE PAGE
// ==========================
if (!isset($pages[$pageId])) {
    $pageId = 'adminDashboard';
}

if (!empty($pagePermissions[$pageId]) && isset($rbac)) {
    $requiredPermission = $pagePermissions[$pageId];
    $hasAccess = is_array($requiredPermission)
        ? $rbac->canAny($requiredPermission)
        : $rbac->can($requiredPermission);

    if (!$hasAccess) {
        $_SESSION['toast'] = [
            'type' => 'error',
            'message' => 'You do not have permission to access that page.'
        ];

        $pageId = 'adminDashboard';
    }
}

// ==========================
// MAKE PAGE DATA GLOBAL (VERY IMPORTANT)
// ==========================
// Now accessible in header.php, sidebar.php, etc.
$GLOBALS['pageTitle'] = $pageTitle;
$GLOBALS['pageDescription'] = $pageDescription;
$GLOBALS['pageModule'] = $pageModule;
$GLOBALS['pageId'] = $pageId;

// ==========================
// LOAD LAYOUT
// ==========================
include './layouts/head.php';

// Sidebar
include './layouts/sidebar.php';

// Header (Topbar)
include './layouts/header.php';

// ==========================
// LOAD CONTENT
// ==========================
include $pages[$pageId];

// ==========================
// FOOTER
// ==========================
include './layouts/footer.php';
