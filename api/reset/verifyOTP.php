<?php
require_once '../../start.inc.php';

// ==========================
// CSRF CHECK
// ==========================
if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'verifyOtp')) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Invalid or expired request.'
    ];
    header("Location: ../../passwordreset.php");
    exit;
}

// ==========================
// BASIC VALIDATION
// ==========================
$otp = $_POST['otp'] ?? '';

if (empty($otp)) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'OTP is required.'
    ];
    header("Location: ../../passwordreset.php");
    exit;
}

// ==========================
// CHECK SESSION STATE
// ==========================
if (!isset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_expiry'])) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Session expired. Please request a new OTP.'
    ];
    header("Location: ../../passwordreset.php");
    exit;
}

// ==========================
// OTP EXPIRY CHECK
// ==========================
if (time() > $_SESSION['reset_expiry']) {

    unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_expiry'], $_SESSION['reset_step']);

    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'OTP expired. Please request a new one.'
    ];

    header("Location: ../../passwordreset.php");
    exit;
}

// ==========================
// OTP ATTEMPT LIMIT (SESSION BASED)
// ==========================
if (!isset($_SESSION['otp_attempts'])) {
    $_SESSION['otp_attempts'] = 0;
}

$_SESSION['otp_attempts']++;

// max 5 attempts
if ($_SESSION['otp_attempts'] > 5) {

    unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_expiry'], $_SESSION['reset_step'], $_SESSION['otp_attempts']);

    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Too many incorrect attempts. Request a new OTP.'
    ];

    header("Location: ../../passwordreset.php");
    exit;
}

// ==========================
// VERIFY OTP
// ==========================
if ($otp != $_SESSION['reset_otp']) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Invalid OTP.'
    ];

    header("Location: ../../passwordreset.php");
    exit;
}

// ==========================
// SUCCESS
// ==========================

// reset attempt counter
unset($_SESSION['otp_attempts']);

// move to password reset step
$_SESSION['reset_step'] = 3;

$_SESSION['toast'] = [
    'type' => 'success',
    'message' => 'OTP verified successfully.'
];

header("Location: ../../passwordreset.php");
exit;