<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_roles');

$id = (int)($_GET['id'] ?? 0);

if ($id < 1) {
    echo json_encode(['status' => false, 'data' => []]);
    exit;
}

$users = $model->query("
    SELECT a.fullname, a.email, a.ix_active
    FROM admin_user_roles aur
    JOIN admins a ON a.id = aur.admin_id
    WHERE aur.role_id = :id
    ORDER BY a.fullname ASC
", ['id' => $id]) ?: [];

$data = [];

foreach ($users as $user) {
    $data[] = [
        'name' => htmlspecialchars($user['fullname'] ?? ''),
        'email' => htmlspecialchars($user['email'] ?? ''),
        'status' => (int)$user['ix_active'] === 1
            ? '<span class="badge bg-success">Active</span>'
            : '<span class="badge bg-danger">Disabled</span>'
    ];
}

echo json_encode(['status' => true, 'data' => $data]);
exit;
