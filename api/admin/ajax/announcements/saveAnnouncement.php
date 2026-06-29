<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_announcements');

$response = [
    'status' => false,
    'message' => 'Unable to save announcement.',
    'csrf_token' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request.');
    }

    if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'announcement_save')) {
        throw new Exception('Invalid or expired request.');
    }

    $adminId = (int)($_SESSION['admin_id'] ?? 0);
    $announcementId = $announcementService->save($_POST, $adminId);

    $response['status'] = true;
    $response['message'] = 'Announcement saved successfully.';
    $response['id'] = $announcementId;
} catch (Throwable $e) {
    $response['message'] = $e->getMessage();
}

$response['csrf_token'] = $utility->generateCsrf('announcement_save');
echo json_encode($response);
exit;
