<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    admission_admin_json(['status' => false, 'message' => 'Bad request.'], 405);
}

try {
    if (!$admission->verifyCsrf($_POST['csrf_token'] ?? '')) {
        throw new Exception('Invalid or expired request.');
    }

    $applicationId = (int) ($_POST['application_id'] ?? 0);
    $migration = $admission->migrateApplicantToStudent($applicationId);

    $utility->logActivity(
        'Migrated admission applicant to student for application ' . $applicationId,
        $_SESSION['admin_email'] ?? null
    );

    admission_admin_json([
        'status' => true,
        'message' => 'Applicant migrated successfully. Matric No: ' . ($migration['matric_no'] ?? ''),
        'migration' => $migration
    ]);
} catch (Throwable $e) {
    admission_admin_json(['status' => false, 'message' => $e->getMessage()], 422);
}
