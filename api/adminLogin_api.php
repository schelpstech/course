<?php
require_once '../start.inc.php';
require_once 'adminQuery.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithToast('error', 'Bad request.', 'adminlogin');
    exit;
}

// ✅ CSRF CHECK (ADMIN CONTEXT)
if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'adminLogin')) {
    redirectWithToast('error', 'Invalid or expired request.', 'adminlogin');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// 🔐 LOGIN ATTEMPT CHECK
if (!$utility->checkLoginAttempts($email)) {
    redirectWithToast('error', 'Too many attempts. Try again later.', 'adminlogin');
    exit;
}

// 🔍 FETCH ADMIN
$adminData = $model->getRows('admins', [
    'where' => ['email' => $email],
    'return_type' => 'single'
]);

// ✅ SUCCESS
if ($adminData && password_verify($password, $adminData['password'])) {

    // 🔐 USE ADMIN SESSION
    $_SESSION['admin_id'] = $adminData['id'];
    $_SESSION['admin_email'] = $adminData['email'];
    // 🔒 Add fingerprint
    $_SESSION['admin_fingerprint'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);

    $utility->resetLoginAttempts($email);
    $utility->logActivity('Admin logged in', $email);

    // Optional: Force password change
    if (!empty($adminData['is_default_password']) && $adminData['is_default_password'] == 1) {

        $_SESSION['force_password_change'] = true;
        redirectWithToast('success', 'Login successful. Please change your default password.', 'change-password');
        exit;
    }
    redirectWithToast('success', 'Welcome to Admin Dashboard', 'adminDashboard');
    exit;
}

// ❌ FAILURE
$utility->recordFailedLogin($email);
$utility->logActivity('Failed admin login attempt', $email);
redirectWithToast('error', 'Invalid email or password', 'adminlogin');
exit;
