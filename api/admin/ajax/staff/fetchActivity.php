<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_admin_users');

$id = (int)($_GET['id'] ?? 0);

if ($id < 1) {
    echo json_encode(['status' => false, 'data' => []]);
    exit;
}

$staff = $rbac->getAdmin($id);

if (!$staff) {
    echo json_encode(['status' => false, 'data' => []]);
    exit;
}

$logs = $model->query("
    SELECT action, ip_address, created_at
    FROM user_logs
    WHERE admin_id = :admin_id
    OR user_id = :email
    ORDER BY created_at DESC
    LIMIT 200
", [
    'admin_id' => $id,
    'email' => $staff['email']
]) ?: [];

$data = [];

foreach ($logs as $log) {
    $data[] = [
        'action' => htmlspecialchars($log['action'] ?? ''),
        'ip_address' => htmlspecialchars($log['ip_address'] ?? ''),
        'date' => !empty($log['created_at']) ? date('d M Y, h:i A', strtotime($log['created_at'])) : ''
    ];
}

echo json_encode(['status' => true, 'data' => $data]);
exit;
