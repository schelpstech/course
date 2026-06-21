<?php
require_once __DIR__ . '/bootstrap.php';

admission_require_post();
admission_require_csrf($admission);

try {
    $applicant = $admission->loginApplicant($_POST['email'] ?? '', $_POST['password'] ?? '');

    $_SESSION['admission_applicant_id'] = (int) $applicant['id'];
    $_SESSION['admission_application_no'] = $applicant['application_no'];

    admission_json([
        'status' => true,
        'message' => 'Login successful.',
        'redirect' => 'admission.php'
    ]);
} catch (Throwable $e) {
    admission_json(['status' => false, 'message' => $e->getMessage()], 422);
}
