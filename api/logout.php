<?php
require_once '../start.inc.php';

// ==========================
// CAPTURE USER BEFORE DESTROY
// ==========================
$userId = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;
$userType = isset($_SESSION['admin_id']) ? 'Admin' : 'User';

// ==========================
// LOG ACTIVITY
// ==========================
if ($userId) {
    $utility->logActivity("$userType logged out", $userId);
}

// ==========================
// DESTROY SESSION COMPLETELY
// ==========================
$_SESSION = [];

// Destroy cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// ==========================
// START FRESH SESSION (SAFE)
// ==========================
session_start();
session_regenerate_id(true); // 🔐 IMPORTANT

// ==========================
// SET TOAST
// ==========================
$_SESSION['toast'] = [
    'type' => 'success',
    'message' => 'You have been logged out successfully'
];

// ==========================
// REDIRECT BASED ON ROLE
// ==========================
if ($userType === 'Admin') {
    header("Location: ../console.php");
} else {
    header("Location: ../index.php");
}

exit;