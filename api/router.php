<?php
require_once './start.inc.php';
require_once './query.php';

// Default page
$pageId = 'loginPage';

// Decode incoming page
if (!empty($_GET['pageid'])) {
    $decoded = $utility->secureDecode($_GET['pageid']);
    if ($decoded) {
        $pageId = is_array($decoded) ? ($decoded['value'] ?? 'loginPage') : $decoded;
    }
}

// Navigation config
$navigationSettings = [

    'loginPage' => [
        'page_name' => 'Login',
        'module' => 'Authentication',
        'auth' => false
    ],

    'studentDashboard' => [
        'page_name' => 'Dashboard',
        'module' => 'Dashboard',
        'auth' => true,
        'roles' => ['student']
    ],

    'adminDashboard' => [
        'page_name' => 'Admin Dashboard',
        'module' => 'Admin',
        'auth' => true,
        'roles' => ['admin']
    ]
];

// Fallback
if (!isset($navigationSettings[$pageId])) {
    $pageId = 'loginPage';
}

$route = $navigationSettings[$pageId];


// 🔐 AUTH CHECK (UPDATED)
if (!empty($route['auth']) && !isset($_SESSION['user_id'])) {
    // Force to login page
    $pageId = 'loginPage';
    $route = $navigationSettings['loginPage'];
}


// 🔐 ROLE CHECK (only if logged in)
if (isset($_SESSION['user_id']) && !empty($route['roles'])) {
    if (!in_array($_SESSION['role'] ?? '', $route['roles'])) {
        die('403 - Unauthorized');
    }
}


// ✅ Set session navigation
$_SESSION['pageid'] = $pageId;
$_SESSION['page_name'] = $route['page_name'];
$_SESSION['module'] = $route['module'];


// ✅ Log activity (only if logged in)
if (isset($_SESSION['user_id'])) {
    $utility->logActivity("Visited {$pageId}", $_SESSION['user_id']);
}


// ✅ Redirect to viewer (NO index.php anymore)
$encodedPage = $utility->secureEncode($pageId);

$utility->redirect('../view/viewer.php?pageid=' . $encodedPage);
exit;