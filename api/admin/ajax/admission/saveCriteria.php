<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    admission_admin_json(['status' => false, 'message' => 'Bad request.'], 405);
}

try {
    if (!$admission->verifyCsrf($_POST['csrf_token'] ?? '')) {
        throw new Exception('Invalid or expired request.');
    }

    $criteriaId = $admission->saveAdmissionCriteria($_POST, (int) $_SESSION['admin_id']);

    $utility->logActivity('Saved admission criteria ' . $criteriaId, $_SESSION['admin_email'] ?? null);
    admission_admin_json([
        'status' => true,
        'message' => 'Admission criteria saved.',
        'criteria_id' => $criteriaId
    ]);
} catch (Throwable $e) {
    admission_admin_json(['status' => false, 'message' => $e->getMessage()], 422);
}
