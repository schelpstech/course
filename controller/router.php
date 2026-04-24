<?php
require_once '../start.inc.php';
require_once '../api/query.php';

// ==========================
// DEFAULT PAGE
// ==========================
$pageId = 'loginPage';

// ==========================
// DECODE PAGE
// ==========================
if (!empty($_GET['pageid'])) {
    $decoded = $utility->secureDecode($_GET['pageid']);
    if ($decoded) {
        $pageId = is_array($decoded) ? ($decoded['value'] ?? 'loginPage') : $decoded;
    }
}

// ==========================
// ROUTES CONFIG
// ==========================
$navigationSettings = [

    'loginPage' => [
        'type' => 'public', // handled by index.php
    ],

    'studentDashboard' => [
        'type' => 'private'
    ],

    'adminDashboard' => [
        'type' => 'private'
    ],

    'lecturerDashboard' => [
        'type' => 'private'
    ],

    'change-password' => [
        'type' => 'private'
    ],
    'updateStudentProfile' => [
        'type' => 'private'
    ],
    'uploadReceipt' => [
        'type' => 'private'
    ],
    'transactionHistory' => [
        'type' => 'private'
    ],
    'payCourseForm' => [
        'type' => 'private'
    ],
    'courseRegistration' => [
        'type' => 'private'
    ],
    'editCourseRegistration' => [
        'type' => 'private'
    ],
    'myCourses' => [
        'type' => 'private'
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
}


// ==========================
// PREVENT LOGGED-IN USER FROM LOGIN PAGE
// ==========================
if ($pageId === 'loginPage' && isset($_SESSION['user_id'])) {

    $pageId = match ($_SESSION['role']) {
        'admin' => 'adminDashboard',
        'lecturer' => 'lecturerDashboard',
        default => 'studentDashboard'
    };
}


// ==========================
// ROUTE HANDLING
// ==========================

// 🔓 LOGIN PAGE (index.php handles UI)
if ($pageId === 'loginPage') {
    header("Location: ../index.php");
    exit;
}


// 🔐 AUTHENTICATED PAGES → viewer.php
$_SESSION['pageid'] = $pageId;

header("Location: ../view/viewer.php?pageid=" . $utility->secureEncode($pageId));
exit;
