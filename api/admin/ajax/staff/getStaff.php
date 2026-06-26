<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_admin_users');

$id = (int)($_GET['id'] ?? 0);

if ($id < 1) {
    echo json_encode(['status' => false, 'message' => 'Invalid staff user.']);
    exit;
}

$staff = $model->queryOne("
    SELECT id, fullname, staff_no, title, email, phone, ix_active
    FROM admins
    WHERE id = :id
    LIMIT 1
", ['id' => $id]);

if (!$staff) {
    echo json_encode(['status' => false, 'message' => 'Staff user not found.']);
    exit;
}

$roles = $model->query("
    SELECT role_id
    FROM admin_user_roles
    WHERE admin_id = :id
", ['id' => $id]) ?: [];

$scope = $model->queryOne("
    SELECT scope_type, institution_id, programme_id, department_id, level_id
    FROM admin_user_scope
    WHERE admin_id = :id
    LIMIT 1
", ['id' => $id]) ?: [
    'scope_type' => 'global',
    'institution_id' => '',
    'programme_id' => '',
    'department_id' => '',
    'level_id' => ''
];

echo json_encode([
    'status' => true,
    'staff' => $staff,
    'role_ids' => array_map('intval', array_column($roles, 'role_id')),
    'scope' => $scope
]);
exit;
