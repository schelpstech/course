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

    'admissionApplications' => './pages/admin/admission/applications.php'
];

// ==========================
// VALIDATE PAGE
// ==========================
if (!isset($pages[$pageId])) {
    $pageId = 'adminDashboard';
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
