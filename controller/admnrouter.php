<?php
require_once '../start.inc.php';
require_once '../api/adminQuery.php';

// ==========================
// DEFAULT PAGE
// ==========================
$pageId = 'adminlogin';

// ==========================
// DECODE PAGE
// ==========================
if (!empty($_GET['pageid'])) {
    $decoded = $utility->secureDecode($_GET['pageid']);

    if ($decoded) {
        if (is_array($decoded)) {
            $pageId = $decoded['page'] ?? 'adminlogin';
        } else {
            $pageId = $decoded;
        }
    }
}

// ==========================
// ADMIN ROUTES CONFIG
// ==========================
$navigationSettings = [

    // ======================
    // AUTH MODULE
    // ======================
    'adminlogin' => [
        'type' => 'public',
        'module' => 'Authentication',
        'title' => 'Admin Login',
        'description' => 'Access the admin control panel'
    ],

    // ======================
    // DASHBOARD MODULE
    // ======================
    'adminDashboard' => [
        'type' => 'private',
        'module' => 'Dashboard',
        'title' => 'Dashboard',
        'description' => 'Overview of system activities and statistics'
    ],

    // ======================
    // ACADEMIC STRUCTURE
    // ======================
    'institutions' => [
        'type' => 'private',
        'module' => 'Academic Structure',
        'title' => 'Institutions',
        'description' => 'Manage all institutions'
    ],

    'programs' => [
        'type' => 'private',
        'module' => 'Academic Structure',
        'title' => 'Programs',
        'description' => 'Manage academic programs'
    ],

    'departments' => [
        'type' => 'private',
        'module' => 'Academic Structure',
        'title' => 'Departments',
        'description' => 'Manage departments'
    ],

    'manageLevels' => [
        'type' => 'private',
        'module' => 'Academic Structure',
        'title' => 'Levels',
        'description' => 'Manage academic levels'
    ],

    'academicSessions' => [
        'type' => 'private',
        'module' => 'Academic Structure',
        'title' => 'Sessions',
        'description' => 'Manage academic sessions'
    ],

    'manageSemesters' => [
        'type' => 'private',
        'module' => 'Academic Structure',
        'title' => 'Semesters',
        'description' => 'Manage semesters'
    ],

    // ======================
    // STUDENT MANAGEMENT
    // ======================
    'students' => [
        'type' => 'private',
        'module' => 'Student Management',
        'title' => 'Students',
        'description' => 'Manage student records'
    ],

    'studentView' => [
        'type' => 'private',
        'module' => 'Student Management',
        'title' => 'Student Profile',
        'description' => 'View student details'
    ],

    // ======================
    // COURSE MANAGEMENT
    // ======================
    'courses' => [
        'type' => 'private',
        'module' => 'Course Management',
        'title' => 'Courses',
        'description' => 'Manage course catalog'
    ],

    'courseformMgr' => [
        'type' => 'private',
        'module' => 'Course Management',
        'title' => 'Course Forms',
        'description' => 'Manage student course registrations'
    ],

    // ======================
    // PAYMENT MANAGEMENT
    // ======================
    'payment_assign' => [
        'type' => 'private',
        'module' => 'Payments',
        'title' => 'Assign Payments',
        'description' => 'Assign fees to students'
    ],

    'payment_config' => [
        'type' => 'private',
        'module' => 'Payments',
        'title' => 'Payment Configuration',
        'description' => 'Configure payment settings'
    ],

    'payment_remark' => [
        'type' => 'private',
        'module' => 'Payments',
        'title' => 'Payment Remarks',
        'description' => 'Manage payment remarks'
    ],

    'internetPaymentReview' => [
        'type' => 'private',
        'module' => 'Payments',
        'title' => 'Payment Review',
        'description' => 'Review online payments'
    ],

    // ======================
    // REGISTRATION
    // ======================
    'registrations' => [
        'type' => 'private',
        'module' => 'Registration',
        'title' => 'Registrations',
        'description' => 'Manage course registrations'
    ],

    'semregistrationStatus' => [
        'type' => 'private',
        'module' => 'Registration',
        'title' => 'Registration Status',
        'description' => 'Monitor registration status'
    ],

    // ======================
    // SECURITY & AUDIT
    // ======================
    'change-password' => [
        'type' => 'private',
        'module' => 'Security',
        'title' => 'Change Password',
        'description' => 'Update admin password'
    ],

    'audit-trail' => [
        'type' => 'private',
        'module' => 'Security',
        'title' => 'Audit Trail',
        'description' => 'View system activity logs'
    ],

    'student-trail' => [
        'type' => 'private',
        'module' => 'Security',
        'title' => 'Student Activity',
        'description' => 'Track student actions'
    ],
];

// ==========================
// FALLBACK
// ==========================
if (!isset($navigationSettings[$pageId])) {
    $pageId = 'adminlogin';
}

$route = $navigationSettings[$pageId];

// ==========================
// AUTH CHECK
// ==========================
if ($route['type'] === 'private' && !isset($_SESSION['admin_id'])) {
    $pageId = 'adminlogin';
    $route = $navigationSettings[$pageId];
}



// ==========================
// PREVENT LOGIN LOOP
// ==========================
if ($pageId === 'adminlogin' && isset($_SESSION['admin_id'])) {
    $pageId = 'adminDashboard';
    $route = $navigationSettings[$pageId];
}

// ==========================
// PREPARE PAGE DATA
// ==========================
$pageTitle = $route['title'] ?? 'Admin Panel';
$pageDescription = $route['description'] ?? '';
$pageModule = $route['module'] ?? 'General';

// ==========================
// ROUTING
// ==========================

// LOGIN PAGE
if ($pageId === 'adminlogin') {
    header("Location: ../console.php");
    exit;
}

// STORE CURRENT PAGE
$_SESSION['admin_pageid'] = $pageId;

// ==========================
// PASS PAYLOAD TO VIEWER
// ==========================
$payload = [
    'page' => $pageId,
    'title' => $pageTitle,
    'description' => $pageDescription,
    'module' => $pageModule
];

header("Location: ../view/adminviewer.php?pageid=" . $utility->secureEncode($payload));
exit;