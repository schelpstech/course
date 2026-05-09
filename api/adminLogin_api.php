<?php
require_once '../start.inc.php';
require_once 'adminQuery.php';

function redirectToConsole($type, $message)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['toast'] = [
        'type' => $type,
        'message' => $message
    ];

    // Ensure session is written before redirect
    session_write_close();

    $path1 = "../../console.php";
    $path2 = "../console.php";

    $redirectPath = file_exists($path1) ? $path1 : $path2;

    header("Location: {$redirectPath}");
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectToConsole('error', 'Bad request.');
    exit;
}

// ✅ CSRF CHECK (ADMIN CONTEXT)
if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'adminLogin')) {
    redirectToConsole('error', 'Invalid or expired request.');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// 🔐 LOGIN ATTEMPT CHECK (ENHANCED)
$attemptData = $utility->checkLoginAttempts($email);
if (!$attemptData['allowed']) {
    $lockUntil = date('h:i A', strtotime($attemptData['lock_until']));

    $utility->logActivity('Account locked due to failed attempts till ' . $lockUntil, $email);
    redirectToConsole('error', "Account locked. Try again after {$lockUntil}");
    exit;
}


// 🔍 FETCH ADMIN
$adminData = $model->getRows('admins', [
    'where' => ['email' => $email],
    'return_type' => 'single'
]);

// ❌ EMAIL NOT FOUND
if (!$adminData) {

    $utility->recordFailedLogin($email);
    $utility->logActivity('Invalid admin email attempt', $email);

    redirectToConsole('error', 'Invalid credential');
    exit;
}

// ❌ PASSWORD WRONG
if (!password_verify($password, $adminData['password'])) {

    $utility->recordFailedLogin($email);
    $utility->logActivity('Wrong password attempt', $email);

    redirectToConsole('error', 'Login credential invalid');
    exit;
}

// ✅ SUCCESS
$_SESSION['admin_id'] = $adminData['id'];
$_SESSION['admin_email'] = $adminData['email'];

$_SESSION['admin_fingerprint'] = hash(
    'sha256',
    $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']
);

$utility->resetLoginAttempts($email);
$utility->logActivity('Admin logged in', $email);

// Optional: Force password change
if (!empty($adminData['is_default_password']) && $adminData['is_default_password'] == 1) {

    $_SESSION['force_password_change'] = true;

    redirectWithToast(
        'success',
        'Login successful. Please change your default password.',
        'change-password'
    );
    exit;
}

redirectWithToast('success', 'Welcome to Admin Dashboard', 'adminDashboard');
exit;
