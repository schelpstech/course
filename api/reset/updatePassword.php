<?php
require_once '../../start.inc.php';

// ==========================
// CSRF CHECK
// ==========================
if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'updatePassword')) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Invalid or expired request.'
    ];
    header("Location: ../../passwordreset.php");
    exit;
}

// ==========================
// STEP VALIDATION (IMPORTANT)
// ==========================
if (!isset($_SESSION['reset_step']) || $_SESSION['reset_step'] != 3) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Unauthorized action. Please complete OTP verification first.'
    ];
    header("Location: ../../passwordreset.php");
    exit;
}

// ==========================
// SESSION VALIDATION
// ==========================
if (!isset($_SESSION['reset_email'])) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Session expired. Please restart password reset.'
    ];
    header("Location: ../../passwordreset.php");
    exit;
}

// ==========================
// INPUT VALIDATION
// ==========================
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';

if (empty($password) || empty($confirm)) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'All fields are required.'
    ];
    header("Location: ../../passwordreset.php");
    exit;
}

// ==========================
// PASSWORD MATCH CHECK
// ==========================
if ($password !== $confirm) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Passwords do not match.'
    ];
    header("Location: ../../passwordreset.php");
    exit;
}

// ==========================
// PASSWORD STRENGTH CHECK (BASIC)
// ==========================
if (strlen($password) < 6) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Password must be at least 6 characters.'
    ];
    header("Location: ../../passwordreset.php");
    exit;
}

// ==========================
// UPDATE PASSWORD
// ==========================
$hashed = password_hash($password, PASSWORD_DEFAULT);

$model->update(
    'users',
    ['password' => $hashed],
    ['email' => $_SESSION['reset_email']]
);

// ==========================
// CLEANUP RESET SESSION (CRITICAL)
// ==========================
unset(
    $_SESSION['reset_email'],
    $_SESSION['reset_otp'],
    $_SESSION['reset_expiry'],
    $_SESSION['reset_step'],
    $_SESSION['otp_attempts']
);

// ==========================
// SUCCESS RESPONSE
// ==========================
$_SESSION['toast'] = [
    'type' => 'success',
    'message' => 'Password updated successfully. You can now login.'
];

header("Location: ../../index.php");
exit;