<?php
require_once '../start.inc.php';
require_once 'query.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['toast'] = [
        'type' => 'error', // success | error | info
        'message' => 'Bad request.'
    ];
    header("Location: ../controller/router.php?pageid=" . $utility->secureEncode('loginPage'));
    exit;
    exit;
}

// ✅ CSRF CHECK
if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'authenticateUser')) {
    $_SESSION['toast'] = [
        'type' => 'error', // success | error | info
        'message' => 'Invalid or expired request.'
    ];
    header("Location: ../controller/router.php?pageid=" . $utility->secureEncode('loginPage'));
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// 🔐 LOGIN ATTEMPT CHECK
if (!$utility->checkLoginAttempts($email)) {
    $_SESSION['toast'] = [
        'type' => 'error', // success | error | info
        'message' => 'Too many attempts. Try again later.'
    ];
    header("Location: ../controller/router.php?pageid=" . $utility->secureEncode('loginPage'));
    exit;
}

// 🔍 FETCH USER
$userData = $model->getRows('users', [
    'where' => ['email' => $email],
    'return_type' => 'single'
]);

// ✅ SUCCESS
if ($userData && password_verify($password, $userData['password'])) {

    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['role'] = $userData['role'];

    $utility->resetLoginAttempts($email);
    $utility->logActivityUsers('User logged in', $email);

    if ($userData['is_default_password'] == 1) {
        $_SESSION['force_password_change'] = true;
        $_SESSION['toast'] = [
            'type' => 'success', // success | error | info
            'message' => 'Login successful. Change your default password to continue.'
        ];
        header("Location: ../controller/router.php?pageid=" . $utility->secureEncode("change-password"));
        exit;
    } elseif ($userData['is_default_password'] == 0) {
        // decide page
        $page = match ($userData['role']) {
            'admin' => 'adminDashboard',
            'lecturer' => 'lecturerDashboard',
            default => 'studentDashboard'
        };
        $_SESSION['toast'] = [
            'type' => 'success', // success | error | info
            'message' => 'Login successful.'
        ];

        header("Location: ../controller/router.php?pageid=" . $utility->secureEncode($page));
        exit;
    }
}

// ❌ FAILURE
$utility->recordFailedLogin($email);
$utility->logActivityUsers('Failed login attempt', $email);

$_SESSION['toast'] = [
    'type' => 'error', // success | error | info
    'message' => 'Invalid email or password'
];

header("Location: ../controller/router.php?pageid=" . $utility->secureEncode('loginPage'));
exit;
