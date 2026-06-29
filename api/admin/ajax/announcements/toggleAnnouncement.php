<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_announcements');

$response = [
    'status' => false,
    'message' => 'Unable to update announcement.',
    'csrf_token' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request.');
    }

    if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'announcement_toggle')) {
        throw new Exception('Invalid or expired request.');
    }

    $id = (int)($_POST['id'] ?? 0);

    if ($id < 1) {
        throw new Exception('Invalid announcement.');
    }

    $result = $announcementService->toggle($id, (int)($_SESSION['admin_id'] ?? 0));

    $response['status'] = true;
    $response['message'] = $result['is_active'] ? 'Announcement enabled.' : 'Announcement disabled.';
} catch (Throwable $e) {
    $response['message'] = $e->getMessage();
}

$response['csrf_token'] = $utility->generateCsrf('announcement_toggle');
echo json_encode($response);
exit;
