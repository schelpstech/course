<?php
require_once '../start.inc.php';
require_once '../api/query.php';
require_once '../api/helpers.php';

// ==========================
// 🔐 LOGIN CHECK
// ==========================
if (empty($_SESSION['user_id'])) {

    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Login required to access this page.'
    ];

    header("Location: ../controller/router.php?pageid=" . $utility->secureEncode('loginPage'));
    exit;
}

// ==========================
// DEFAULT VALUES
// ==========================
$pageId = 'studentDashboard';
$pageTitle = 'Dashboard';
$pageDescription = '';
$pageModule = 'General';

// ==========================
// FORCE PASSWORD CHANGE
// ==========================
if (!empty($_SESSION['force_password_change'])) {

    $decodedPage = null;

    if (!empty($_GET['pageid'])) {
        $decodedPage = $utility->secureDecode($_GET['pageid']);
    }

    $currentPage = is_array($decodedPage)
        ? ($decodedPage['page'] ?? null)
        : $decodedPage;

    if ($currentPage !== 'change-password') {
        header("Location: ../controller/router.php?pageid=" . $utility->secureEncode('change-password'));
        exit;
    }
}

// ==========================
// DECODE ROUTE PAYLOAD
// ==========================
if (!empty($_GET['pageid'])) {

    $decoded = $utility->secureDecode($_GET['pageid']);

    if ($decoded) {

        if (is_array($decoded)) {

            $pageId = $decoded['page'] ?? 'studentDashboard';
            $pageTitle = $decoded['title'] ?? 'Student Portal';
            $pageDescription = $decoded['description'] ?? '';
            $pageModule = $decoded['module'] ?? 'General';

        } else {
            // fallback legacy support
            $pageId = $decoded;
        }
    }
}

// ==========================
// PAGE MAP
// ==========================
$pages = [

    'loginPage' => './pages/auth/login.php',

    'change-password' => './forms/changePassword.php',

    'studentDashboard' => './dashboard/student.php',

    'updateStudentProfile' => './forms/studentprofile.php',

    'uploadReceipt' => './forms/semesterRegistration.php',

    'transactionHistory' => './pages/student/transactionHistory.php',

    'payCourseForm' => './pages/student/paycourseform.php',

    'courseRegistration' => './pages/student/courseRegistration.php',

    'editCourseRegistration' => './pages/student/editcourseRegistration.php',

    'myCourses' => './pages/student/studentCourseForm.php'
];

// ==========================
// VALIDATE PAGE
// ==========================
if (!isset($pages[$pageId])) {
    $pageId = 'studentDashboard';
}

// ==========================
// MAKE GLOBAL PAGE DATA (FOR UI)
// ==========================
$GLOBALS['pageId'] = $pageId;
$GLOBALS['pageTitle'] = $pageTitle;
$GLOBALS['pageDescription'] = $pageDescription;
$GLOBALS['pageModule'] = $pageModule;

// ==========================
// LAYOUT
// ==========================
include './layouts/head.php';
include './layouts/sidebar.php';
include './layouts/header.php';

// ==========================
// CONTENT
// ==========================
include $pages[$pageId];

// ==========================
// FOOTER
// ==========================
include './layouts/footer.php';