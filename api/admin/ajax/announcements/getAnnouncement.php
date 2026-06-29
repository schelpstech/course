<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_announcements');

try {
    $id = (int)($_GET['id'] ?? 0);

    if ($id < 1) {
        throw new Exception('Invalid announcement.');
    }

    echo json_encode([
        'status' => true,
        'announcement' => $announcementService->get($id)
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
}
exit;
