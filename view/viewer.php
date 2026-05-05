<?php
require_once '../start.inc.php';
require_once '../api/query.php';
require_once '../api/helpers.php';
// Redirect if already logged in
if (empty($_SESSION['user_id'])) {

    $_SESSION['toast'] = [
        'type' => 'error', // success | error | info
        'message' => 'Login required to access this page.'
    ];

    header("Location: ../controller/router.php?pageid=" . $utility->secureEncode('loginPage'));
    exit;
}
// Default page
$pageId = 'loginPage';

if (!empty($_SESSION['force_password_change']) && $utility->secureDecode($_GET['pageid']) !== 'change-password') {
    $_GET['pageid'] = $utility->secureEncode('change-password');
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Please change your default password before proceeding.'
    ];
}
// Decode pageid from URL
if (!empty($_GET['pageid'])) {
    $decoded = $utility->secureDecode($_GET['pageid']);
    if ($decoded) {
        $pageId = is_array($decoded) ? ($decoded['value'] ?? 'loginPage') : $decoded;
    }
}


// Page mapping (VERY IMPORTANT - matches your structure)
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

// Determine if it's login page
$isLoginPage = ($pageId === 'loginPage');


// ✅ HEADER
include './layouts/head.php';



// ✅ SIDEBAR (ONLY when logged in and NOT login page)
if (!$isLoginPage && isset($_SESSION['user_id'])) {
    include './layouts/sidebar.php';
} else {
    header("Location: ../controller/router.php?pageid=" . $utility->secureEncode('loginPage'));
    exit;
}
include './layouts/header.php';



// ✅ LOAD PAGE
if (isset($pages[$pageId])) {
    include $pages[$pageId];
} else {
    echo "404 - Page not found";
}


// ✅ FOOTER
include './layouts/footer.php';
