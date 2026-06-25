<?php
require_once __DIR__ . '/bootstrap.php';

admission_require_post();
admission_require_csrf($admission);
$application = admission_current_application($admission);
$applicantId = admission_require_applicant();

try {
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action !== 'reject') {
        throw new Exception('Invalid offer response.');
    }

    $admission->rejectAdmissionOffer((int) $application['id'], $applicantId);

    admission_json([
        'status' => true,
        'message' => 'Admission offer rejected.',
        'redirect' => 'dashboard.php'
    ]);
} catch (Throwable $e) {
    admission_json(['status' => false, 'message' => $e->getMessage()], 422);
}
