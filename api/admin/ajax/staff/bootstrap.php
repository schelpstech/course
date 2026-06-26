<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_admin_users');

echo json_encode([
    'status' => true,
    'roles' => $rbac->getRoles(true),
    'institutions' => $model->query("
        SELECT id, name
        FROM institutions
        WHERE is_active = 1
        ORDER BY name ASC
    ") ?: []
]);
exit;
