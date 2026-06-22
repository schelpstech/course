<?php
require_once __DIR__ . '/bootstrap.php';

admission_require_post();
admission_require_csrf($admission);

try {
    if (($_POST['password'] ?? '') !== ($_POST['confirm_password'] ?? '')) {
        throw new Exception('Passwords do not match.');
    }

    $account = $admission->createApplicant(
        $_POST['email'] ?? '',
        $_POST['phone'] ?? '',
        $_POST['password'] ?? ''
    );

    $_SESSION['admission_applicant_id'] = $account['applicant_id'];
    $_SESSION['admission_application_no'] = $account['application_no'];
    unset($_SESSION['signup_email']);
    unset($_SESSION['signup_verified']);
    admission_json([
        'status' => true,
        'message' => 'Account created. Application invoice generated.',
        'redirect' => 'dashboard.php'
    ]);
} catch (Throwable $e) {
    admission_json(['status' => false, 'message' => $e->getMessage()], 422);
}
