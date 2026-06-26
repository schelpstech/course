<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_roles');

$id = (int)($_GET['id'] ?? 0);

if ($id < 1) {
    echo json_encode(['status' => false, 'message' => 'Invalid role.']);
    exit;
}

$role = $model->queryOne("
    SELECT *
    FROM roles
    WHERE id = :id
    LIMIT 1
", ['id' => $id]);

if (!$role) {
    echo json_encode(['status' => false, 'message' => 'Role not found.']);
    exit;
}

$permissions = $model->query("
    SELECT permission_id
    FROM role_permissions
    WHERE role_id = :id
", ['id' => $id]) ?: [];

echo json_encode([
    'status' => true,
    'role' => $role,
    'permission_ids' => array_map('intval', array_column($permissions, 'permission_id'))
]);
exit;
