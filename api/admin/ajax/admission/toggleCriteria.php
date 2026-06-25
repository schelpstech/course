<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    admission_admin_json(['status' => false, 'message' => 'Bad request.'], 405);
}

try {
    if (!$admission->verifyCsrf($_POST['csrf_token'] ?? '')) {
        throw new Exception('Invalid or expired request.');
    }

    $admission->toggleAdmissionCriteria((int) ($_POST['id'] ?? 0), $_POST['status'] ?? '');
    $utility->logActivity('Toggled admission criteria ' . ($_POST['id'] ?? ''), $_SESSION['admin_email'] ?? null);

    admission_admin_json(['status' => true, 'message' => 'Criteria status updated.']);
} catch (Throwable $e) {
    admission_admin_json(['status' => false, 'message' => $e->getMessage()], 422);
}
