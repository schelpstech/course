<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    admission_admin_json(['status' => false, 'message' => 'Bad request.'], 405);
}

try {
    if (!$admission->verifyCsrf($_POST['csrf_token'] ?? '')) {
        throw new Exception('Invalid or expired request.');
    }

    $admission->saveAdmissionSession($_POST);
    $utility->logActivity('Saved admission session', $_SESSION['admin_email'] ?? null);
    admission_admin_json(['status' => true, 'message' => 'Admission session saved.']);
} catch (Throwable $e) {
    admission_admin_json(['status' => false, 'message' => $e->getMessage()], 422);
}
