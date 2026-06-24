<?php
require_once __DIR__ . '/bootstrap.php';

admission_require_post();
admission_require_csrf($admission);
$application = admission_current_application($admission);

try {
    $registrationNo = $admission->submitApplication((int) $application['id']);

    admission_json([
        'status' => true,
        'message' => 'Application submitted successfully.',
        'registration_no' => $registrationNo,
        'redirect' => 'dashboard.php'
    ]);
} catch (Throwable $e) {
    admission_json(['status' => false, 'message' => $e->getMessage()], 422);
}
