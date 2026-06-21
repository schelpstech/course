<?php
require_once __DIR__ . '/bootstrap.php';

admission_require_post();
admission_require_csrf($admission);

try {
    $admission->verifyRegistrationOtp($_POST['email'] ?? '', $_POST['otp'] ?? '');
    admission_json(['status' => true, 'message' => 'Email verified. Continue account creation.']);
} catch (Throwable $e) {
    admission_json(['status' => false, 'message' => $e->getMessage()], 422);
}
