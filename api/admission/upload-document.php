<?php
require_once __DIR__ . '/bootstrap.php';

admission_require_post();
admission_require_csrf($admission);
$application = admission_current_application($admission);

try {
    $type = $_POST['document_type'] ?? '';
    $file = $_FILES['document'] ?? [];
    $document = $admission->uploadDocument((int) $application['id'], $type, $file);

    admission_json([
        'status' => true,
        'message' => 'Document uploaded.',
        'document' => $document,
        'completion' => $admission->completion((int) $application['id'])
    ]);
} catch (Throwable $e) {
    admission_json(['status' => false, 'message' => $e->getMessage()], 422);
}
