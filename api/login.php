<?php
require_once '../start.inc.php';
require_once 'query.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Bad request.'
    ];
    header("Location: ../index.php");
    exit;
}

// ✅ CSRF CHECK
if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'authenticateUser')) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Invalid or expired request.'
    ];
    header("Location: ../index.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// 🔐 LOGIN ATTEMPT CHECK (ENHANCED)
$attemptData = $utility->checkLoginAttempts($email);

if (!$attemptData['allowed']) {

    $lockUntil = date('h:i A', strtotime($attemptData['lock_until']));

    $utility->logActivityUsers('Account locked due to failed attempts till ' . $lockUntil, $email);

    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => "Account locked. Try again after {$lockUntil}"
    ];

    header("Location: ../index.php");
    exit;
}


// 🔍 FETCH USER
$userData = $model->getRows('users', [
    'where' => ['email' => $email],
    'return_type' => 'single'
]);

// ❌ EMAIL NOT FOUND
if (!$userData) {

    $utility->recordFailedLogin($email);
    $utility->logActivityUsers('Invalid email attempt', $email);

    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Invalid credentials'
    ];

    header("Location: ../index.php");
    exit;
}

// ❌ WRONG PASSWORD
if (!password_verify($password, $userData['password'])) {

    $utility->recordFailedLogin($email);
    $utility->logActivityUsers('Wrong password attempt', $email);

    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Invalid credentials'
    ];

    header("Location: ../index.php");
    exit;
}

// ✅ SUCCESS
$_SESSION['user_id'] = $userData['id'];
$_SESSION['user_email'] = $userData['email'];
$_SESSION['role'] = $userData['role'];

$utility->resetLoginAttempts($email);
$utility->logActivityUsers('User logged in', $email);

// 🔁 FORCE PASSWORD CHANGE
if ($userData['is_default_password'] == 1) {

    $_SESSION['force_password_change'] = true;

    $_SESSION['toast'] = [
        'type' => 'success',
        'message' => 'Login successful. Change your default password to continue.'
    ];

    header("Location: ../controller/router.php?pageid=" . $utility->secureEncode("change-password"));
    exit;
}

// 🎯 ROLE-BASED REDIRECT
$page = match ($userData['role']) {
    'admin' => 'adminDashboard',
    'lecturer' => 'lecturerDashboard',
    default => 'studentDashboard'
};

$_SESSION['toast'] = [
    'type' => 'success',
    'message' => 'Login successful.'
];

header("Location: ../controller/router.php?pageid=" . $utility->secureEncode($page));
exit;