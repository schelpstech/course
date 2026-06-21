<?php
require_once __DIR__ . '/bootstrap.php';

admission_require_post();
admission_require_csrf($admission);
$application = admission_current_application($admission);

try {
    $step = $_POST['step'] ?? '';

    switch ($step) {
        case 'bio':
            $admission->saveBioData((int) $application['id'], $_POST);
            $message = 'Bio data saved.';
            break;

        case 'contact':
            $admission->saveContactInfo((int) $application['id'], $_POST);
            $message = 'Contact information saved.';
            break;

        case 'academic':
            $admission->saveAcademicHistory((int) $application['id'], $_POST['history'] ?? []);
            $message = 'Academic history saved.';
            break;

        case 'olevel':
            $admission->saveOlevelResults((int) $application['id'], $_POST['sittings'] ?? []);
            $message = 'O-Level results saved.';
            break;

        case 'programme':
            $admission->saveProgrammeChoice((int) $application['id'], $_POST);
            $message = 'Programme selection saved.';
            break;

        default:
            throw new Exception('Unknown form section.');
    }

    admission_json([
        'status' => true,
        'message' => $message,
        'completion' => $admission->completion((int) $application['id'])
    ]);
} catch (Throwable $e) {
    admission_json(['status' => false, 'message' => $e->getMessage()], 422);
}
