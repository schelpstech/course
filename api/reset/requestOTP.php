<?php
require_once '../../start.inc.php';

// ==========================
// CSRF CHECK
// ==========================
if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'requestOtp')) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Invalid or expired request.'
    ];
    header("Location: ../../passwordreset.php");
    exit;
}

// ==========================
// INPUT SANITIZATION
// ==========================
$email = strtolower(trim($_POST['email'] ?? ''));
$math  = $_POST['math_answer'] ?? '';

if (empty($email) || empty($math)) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'All fields are required.'
    ];
    header("Location: ../../passwordreset.php");
    exit;
}

// ==========================
// HUMAN VERIFICATION
// ==========================
if ($math != ($_SESSION['math_ans'] ?? null)) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Incorrect verification answer.'
    ];
    header("Location: ../../passwordreset.php");
    exit;
}

// ==========================
// CHECK USER EXISTS
// ==========================
$user = $model->getRows('users', ['email' => $email]);

if (!$user) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Email not found.'
    ];
    header("Location: ../../passwordreset.php");
    exit;
}

// ==========================
// RATE LIMIT CHECK (DB BASED)
// ==========================
$rateData = $model->getRows('otp_requests', ['email' => $email]);
$now = time();

if ($rateData) {

    $lastSent = (int)$rateData['last_sent'];
    $attempts = (int)$rateData['attempts'];

    // reset attempts if window expired (10 mins)
    if (($now - $lastSent) >= 600) {
        $attempts = 0;
    }

    // cooldown 60 seconds
    if (($now - $lastSent) < 60) {
        $_SESSION['toast'] = [
            'type' => 'error',
            'message' => 'Please wait before requesting another OTP.'
        ];
        header("Location: ../../passwordreset.php");
        exit;
    }

    // max 5 requests per 10 minutes
    if ($attempts >= 5) {
        $_SESSION['toast'] = [
            'type' => 'error',
            'message' => 'Too many attempts. Try again later.'
        ];
        header("Location: ../../passwordreset.php");
        exit;
    }

    // update record
    $model->update('otp_requests', [
        'attempts'  => $attempts + 1,
        'last_sent' => $now
    ], [
        'email' => $email
    ]);
} else {

    // first request
    $model->insert_data('otp_requests', [
        'email'     => $email,
        'attempts'  => 1,
        'last_sent' => $now
    ]);
}

// ==========================
// GENERATE OTP
// ==========================
$otp = random_int(100000, 999999);

// ==========================
// SEND EMAIL
// ==========================
$sent = $mailservice->sendOtp($email, $otp);

if (!$sent) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Failed to send OTP. Try again.'
    ];
    header("Location: ../../passwordreset.php");
    exit;
}

// ==========================
// SET RESET SESSION STATE
// ==========================
$_SESSION['reset_email']  = $email;
$_SESSION['reset_otp']    = $otp;
$_SESSION['reset_expiry'] = time() + (5 * 60);
$_SESSION['reset_step']   = 2;

// ==========================
// SUCCESS RESPONSE
// ==========================
$_SESSION['toast'] = [
    'type' => 'success',
    'message' => 'OTP sent successfully.'
];

header("Location: ../../passwordreset.php");
exit;
