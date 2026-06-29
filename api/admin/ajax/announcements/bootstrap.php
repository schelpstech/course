<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_announcements');

try {
    $announcementService->ensureSchema();

    $institutions = $model->query("
        SELECT id, name
        FROM institutions
        WHERE is_active = 1
        ORDER BY name ASC
    ") ?: [];

    echo json_encode([
        'status' => true,
        'institutions' => $institutions
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
}
exit;
