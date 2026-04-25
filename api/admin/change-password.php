<?php
require_once '../../start.inc.php';
require_once '../adminQuery.php';

// ==========================
// METHOD CHECK
// ==========================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithToast('error', 'Invalid request', 'change-password');
    exit;
}

// ==========================
// CSRF VALIDATION
// ==========================
if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'admin_change_password')) {
    redirectWithToast('error', 'Bad request', 'change-password');
    exit;
}

// ==========================
// VERIFY ADMIN SESSION
// ==========================
if (!isset($_SESSION['admin_id'])) {
    redirectWithToast('error', 'Admin login required', 'adminlogin');
    exit;
}

$admin_id = $_SESSION['admin_id'];

// ==========================
// INPUTS
// ==========================
$current = trim($_POST['current_password'] ?? '');
$new     = trim($_POST['new_password'] ?? '');
$confirm = trim($_POST['confirm_password'] ?? '');

// ==========================
// VALIDATION
// ==========================
if ($new !== $confirm) {
    redirectWithToast('error', 'Passwords do not match', 'change-password');
    exit;
}

if (strlen($new) < 6) {
    redirectWithToast('error', 'Passwords must be at least 6 characters', 'change-password');
    exit;
}

// ==========================
// FETCH ADMIN
// ==========================
$admin = $adminModel->getadminById($admin_id);

if (!$admin) {
    session_destroy();
    session_start();
    session_regenerate_id(true); // 🔐 IMPORTANT

    // ==========================
    // SET TOAST
    // ==========================
    $_SESSION['toast'] = [
        'type' => 'success',
        'message' => 'Invalid Action. Authorised Users Only'
    ];

    header("Location: ../admin/index.php");
    exit;
}

// ==========================
// VERIFY CURRENT PASSWORD
// ==========================
if (!password_verify($current, $admin['password'])) {
    redirectWithToast('error', 'Incorrect current password', 'change-password');
    exit;
}

// ==========================
// UPDATE PASSWORD
// ==========================
$model->update('admins', [
    'password' => password_hash($new, PASSWORD_DEFAULT),
    'is_default_password' => 0
], [
    'id' => $admin_id
]);

// ==========================
// CLEAR FORCE FLAG
// ==========================
unset($_SESSION['force_password_change']);
$utility->logActivity('changed Admin password');
// ==========================
// SUCCESS RESPONSE
// ==========================
$_SESSION['toast'] = [
    'type' => 'success',
    'message' => 'Password updated successfully'
];
redirectWithToast('success', 'Password updated successfully', 'adminDashboard');
exit;
