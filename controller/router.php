<?php
require_once '../start.inc.php';
require_once '../api/query.php';

// ==========================
// DEFAULT PAGE
// ==========================
$pageId = 'loginPage';

// ==========================
// DECODE PAGE (NEW STRUCTURE READY)
// ==========================
if (!empty($_GET['pageid'])) {

    $decoded = $utility->secureDecode($_GET['pageid']);

    if ($decoded) {

        if (is_array($decoded)) {
            $pageId = $decoded['page'] ?? 'loginPage';
        } else {
            $pageId = $decoded;
        }
    }
}

// ==========================
// ROUTES CONFIG (UPGRADED)
// ==========================
$navigationSettings = [

    // ======================
    // AUTH
    // ======================
    'loginPage' => [
        'type' => 'public',
        'module' => 'Authentication',
        'title' => 'Student Login',
        'description' => 'Access your student portal'
    ],

    // ======================
    // DASHBOARD
    // ======================
    'studentDashboard' => [
        'type' => 'private',
        'module' => 'Dashboard',
        'title' => 'Student Dashboard',
        'description' => 'Overview of your academic activities'
    ],

    'adminDashboard' => [
        'type' => 'private',
        'module' => 'Dashboard',
        'title' => 'Admin Dashboard',
        'description' => 'System administration panel'
    ],

    'lecturerDashboard' => [
        'type' => 'private',
        'module' => 'Dashboard',
        'title' => 'Lecturer Dashboard',
        'description' => 'Manage your teaching activities'
    ],

    // ======================
    // PROFILE & ACCOUNT
    // ======================
    'change-password' => [
        'type' => 'private',
        'module' => 'Account',
        'title' => 'Change Password',
        'description' => 'Update your account password'
    ],

    'updateStudentProfile' => [
        'type' => 'private',
        'module' => 'Account',
        'title' => 'Update Profile',
        'description' => 'Edit your personal information'
    ],

    // ======================
    // PAYMENTS
    // ======================
    'uploadReceipt' => [
        'type' => 'private',
        'module' => 'Payments',
        'title' => 'Upload Receipt',
        'description' => 'Submit payment proof'
    ],

    'transactionHistory' => [
        'type' => 'private',
        'module' => 'Payments',
        'title' => 'Transaction History',
        'description' => 'View all payment records'
    ],

    'payCourseForm' => [
        'type' => 'private',
        'module' => 'Payments',
        'title' => 'Course Form Payment',
        'description' => 'Pay for course registration form'
    ],

    // ======================
    // COURSE REGISTRATION
    // ======================
    'courseRegistration' => [
        'type' => 'private',
        'module' => 'Academics',
        'title' => 'Course Registration',
        'description' => 'Register your courses'
    ],

    'editCourseRegistration' => [
        'type' => 'private',
        'module' => 'Academics',
        'title' => 'Edit Registration',
        'description' => 'Modify registered courses'
    ],

    'myCourses' => [
        'type' => 'private',
        'module' => 'Academics',
        'title' => 'My Courses',
        'description' => 'View your registered courses'
    ],

    'printExamClearance' => [
        'type' => 'private',
        'module' => 'Academics',
        'title' => 'Semester Exam Clearance',
        'description' => 'Semester Examination Clearance Slip'
    ],
];

// ==========================
// FALLBACK
// ==========================
if (!isset($navigationSettings[$pageId])) {
    $pageId = 'loginPage';
}

$route = $navigationSettings[$pageId];

// ==========================
// AUTH CHECK
// ==========================
if ($route['type'] === 'private' && !isset($_SESSION['user_id'])) {
    $pageId = 'loginPage';
    $route = $navigationSettings[$pageId];
}

// ==========================
// REDIRECT LOGGED-IN USERS AWAY FROM LOGIN
// ==========================
if ($pageId === 'loginPage' && isset($_SESSION['user_id'])) {

    $pageId = match ($_SESSION['role'] ?? 'student') {
        'admin' => 'adminDashboard',
        'lecturer' => 'lecturerDashboard',
        default => 'studentDashboard'
    };

    $route = $navigationSettings[$pageId];
}

// ==========================
// LOGIN ROUTE
// ==========================
if ($pageId === 'loginPage') {
    header("Location: ../index.php");
    exit;
}

// ==========================
// PREPARE PAYLOAD (NEW STANDARD)
// ==========================
$pageTitle = $route['title'] ?? 'Student Portal';
$pageDescription = $route['description'] ?? '';
$pageModule = $route['module'] ?? 'General';

$payload = [
    'page' => $pageId,
    'title' => $pageTitle,
    'description' => $pageDescription,
    'module' => $pageModule
];

// ==========================
// STORE SESSION
// ==========================
$_SESSION['pageid'] = $pageId;

// ==========================
// ROUTE TO VIEWER
// ==========================
header("Location: ../view/viewer.php?pageid=" . $utility->secureEncode($payload));
exit;