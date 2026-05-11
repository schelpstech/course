<?php
require_once '../start.inc.php';
require_once 'query.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit;
}

// Validate CSRF
if (!$utility->validateRequest($_POST['csrf_token'], 'change-password')) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid request'];
    header("Location: ../controller/router.php?pageid=" . $utility->secureEncode("change-password"));
    exit;
}

$user_id = $_SESSION['user_id'];

$current = $_POST['current_password'];
$new = $_POST['new_password'];
$confirm = $_POST['confirm_password'];

// Validate match
if ($new !== $confirm) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Passwords do not match'];
    header("Location: ../controller/router.php?pageid=" . $utility->secureEncode("change-password"));
    exit;
}

// Fetch user
$user = getUserByID($model);

// Verify current password
if (!password_verify($current, $user['password'])) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Incorrect current password'];
    header("Location: ../controller/router.php?pageid=" . $utility->secureEncode("change-password"));
    $utility->logActivityUsers('Failed to change password for student with user ID: ' . $user_id, $user['email']);
    exit;
}

// Update password
$model->update('users', [
    'password' => password_hash($new, PASSWORD_DEFAULT),
    'is_default_password' => 0
], ['id' => $user_id]);
$utility->logActivityUsers('Successfully changed password for student with user ID: ' . $user_id, $user['email']);
// Remove force flag
unset($_SESSION['force_password_change']);

$_SESSION['toast'] = ['type' => 'success', 'message' => 'Password updated successfully'];

// Redirect to dashboard
header("Location: ../controller/router.php?pageid=" . $utility->secureEncode("studentDashboard"));
exit;
