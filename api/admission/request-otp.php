<?php
require_once __DIR__ . '/bootstrap.php';

admission_require_post();
admission_require_csrf($admission);

try {
    $result = $admission->requestRegistrationOtp($_POST['email'] ?? '');
    $_SESSION['signup_email'] = $_POST['email'] ?? '';
    admission_json([
        'status' => $result['sent'],
        'message' => $result['message']
    ], $result['sent'] ? 200 : 500);
} catch (Throwable $e) {
    admission_json(['status' => false, 'message' => $e->getMessage()], 422);
}
