<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    admission_admin_json(['status' => false, 'message' => 'Bad request.'], 405);
}

try {
    if (!$admission->verifyCsrf($_POST['csrf_token'] ?? '')) {
        throw new Exception('Invalid or expired request.');
    }

    $status = $admission->screeningAction(
        (int) ($_POST['application_id'] ?? 0),
        (int) $_SESSION['admin_id'],
        $_POST['action'] ?? '',
        trim($_POST['remarks'] ?? '')
    );

    $utility->logActivity('Admission screening action: ' . ($_POST['action'] ?? '') . ' for application ' . ($_POST['application_id'] ?? ''), $_SESSION['admin_email'] ?? null);

    admission_admin_json([
        'status' => true,
        'message' => 'Application updated to ' . $status . '.'
    ]);
} catch (Throwable $e) {
    admission_admin_json(['status' => false, 'message' => $e->getMessage()], 422);
}
