<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');

// 🔐 Protect route
$utility->requireAdmin();

$logs = $model->getRows('user_logs_users', [
    'select' => '
        user_logs_users.*,
        users.name,
        users.email
    ',
    'join' => [
        'users' => ' ON users.email = user_logs_users.user_id'
    ],
    'order_by' => 'user_logs_users.created_at DESC',
    'limit' => 1000
]);

$logs = is_array($logs) ? $logs : [];

$data = [];

$i = 1;

foreach ($logs as $row) {

    $name  = $row['name'] ?? 'Guest';
    $email = $row['email'] ?? '';
    $action = strtoupper($row['action'] ?? 'UNKNOWN');

    $badge = 'secondary';
    if (str_contains($action, 'CREATE')) $badge = 'success';
    elseif (str_contains($action, 'UPDATE')) $badge = 'info';
    elseif (str_contains($action, 'DELETE')) $badge = 'danger';
    elseif (str_contains($action, 'LOGIN')) $badge = 'primary';

    $data[] = [
        $i++,
        htmlspecialchars($name) . "<br><small class='text-muted'>" . htmlspecialchars($email) . "</small>",
        "<span class='badge bg-{$badge}'>{$action}</span>",
        htmlspecialchars($row['ip_address'] ?? ''),
        [
            'display' => date('d M Y, h:i A', strtotime($row['created_at'])),
            'sort' => strtotime($row['created_at'])
        ]
    ];
}

echo json_encode([
    "data" => $data
]);
