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
// 🔐 VERIFY ADMIN EXISTS
// ==========================
$currentFingerprint = hash('sha256', $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);

if (!isset($_SESSION['admin_fingerprint']) || $_SESSION['admin_fingerprint'] !== $currentFingerprint) {
    session_destroy();
    header("Location: ../console.php");
    exit;
}

// ==========================
// 🔒 FORCE PASSWORD CHANGE
// ==========================
if (
    !empty($_SESSION['force_password_change']) &&
    $utility->secureDecode($_GET['pageid'] ?? '') !== 'change-password'
) {

    $_GET['pageid'] = $utility->secureEncode('change-password');

    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Please change your default password before proceeding.'
    ];
}

// ==========================
// DEFAULT PAGE
// ==========================
$pageId = 'adminDashboard';

// ==========================
// DECODE PAGE
// ==========================
if (!empty($_GET['pageid'])) {
    $decoded = $utility->secureDecode($_GET['pageid']);
    if ($decoded) {
        $pageId = is_array($decoded)
            ? ($decoded['value'] ?? 'adminDashboard')
            : $decoded;
    }
}

// ==========================
// ADMIN PAGE MAP
// ==========================
$pages = [

    'adminDashboard' => './pages/admin/dashboard.php',

    'institutions'   => './pages/admin/forms/manageInstitution.php',

    'programs'        => './pages/admin/forms/managePrograms.php',
    
    'departments'     => './pages/admin/forms/manageDepartments.php',

    'manageLevels'          => './pages/admin/forms/manageLevels.php',

    'academicSessions' => './pages/admin/forms/academicSessions.php',

    'manageSemesters' => './pages/admin/forms/manageSemester.php',

    'students'       => './pages/admin/manageStudents.php',

    'studentView'    => './pages/students/view.php',

    'courses'        => './pages/admin/forms/courseManager.php',

    'payments'       => './pages/payments/index.php',

    'registrations'  => './pages/registrations/index.php',

    'change-password' => './pages/admin/changePassword.php',

    'audit-trail' => './pages/admin/report/auditTrail.php',
];

// ==========================
// VALIDATE PAGE
// ==========================
if (!isset($pages[$pageId])) {
    $pageId = 'adminDashboard';
}

// ==========================
// LOAD LAYOUT
// ==========================
include './layouts/head.php';

// Sidebar (always for admin)
include './layouts/sidebar.php';

// Header (topbar)
include './layouts/header.php';

// ==========================
// LOAD CONTENT
// ==========================
include $pages[$pageId];

// ==========================
// FOOTER
// ==========================
include './layouts/footer.php';
