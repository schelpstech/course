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
        $pageId = is_array($decoded) ? ($decoded['value'] ?? 'adminlogin') : $decoded;
    }
}

// ==========================
// ADMIN ROUTES ONLY
// ==========================
$navigationSettings = [

    'adminlogin' => [
        'type' => 'public',
    ],

    'adminDashboard' => [
        'type' => 'private'
    ],
    'institutions' => [
        'type' => 'private'
    ],
    'programs' => [
        'type' => 'private'
    ],
    'departments' => [
        'type' => 'private'
    ],
    'manageLevels' => [
        'type' => 'private'
    ],
    'academicSessions' => [
        'type' => 'private'
    ],
    'manageSemesters' => [
        'type' => 'private'
    ],

    'students' => [
        'type' => 'private'
    ],

    'studentView' => [
        'type' => 'private'
    ],

    'courses' => [
        'type' => 'private'
    ],

    'payments' => [
        'type' => 'private'
    ],

    'registrations' => [
        'type' => 'private'
    ],

    'change-password' => [
        'type' => 'private'
    ],
    'audit-trail' => [
        'type' => 'private'
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
// AUTH CHECK (ADMIN ONLY)
// ==========================
if ($route['type'] === 'private' && !isset($_SESSION['admin_id'])) {
    $pageId = 'adminlogin';
}

$currentFingerprint = hash('sha256', $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);

if (!isset($_SESSION['admin_fingerprint']) || $_SESSION['admin_fingerprint'] !== $currentFingerprint) {
    session_destroy();
    header("Location: ../console.php");
    exit;
}
// ==========================
// PREVENT ADMIN FROM SEEING LOGIN AGAIN
// ==========================
if ($pageId === 'adminlogin' && isset($_SESSION['admin_id'])) {
    $pageId = 'adminDashboard';
}

// ==========================
// ROUTING
// ==========================

// 🔓 LOGIN PAGE
if ($pageId === 'adminlogin') {
    header("Location: ../console.php");
    exit;
}

// 🔐 AUTHENTICATED ADMIN PAGES
$_SESSION['admin_pageid'] = $pageId;

header("Location: ../view/adminviewer.php?pageid=" . $utility->secureEncode($pageId));
exit;