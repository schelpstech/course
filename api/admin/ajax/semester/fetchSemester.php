<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL


$session_id = $_GET['session_id'] ?? null;

$rows = $model->getRows("semesters", [
    'select' => 'semesters.*, academic_sessions.name as session_name',
    'join' => ['academic_sessions' => 'ON academic_sessions.id = semesters.session_id'],
    'where' => $session_id ? ['semesters.session_id' => $session_id] : [],
]);

$data = [];
if (!is_array($rows) || empty($rows)) {
    echo json_encode($response);
    exit;
}
foreach ($rows as $r) {

    $status = $r['is_active']
        ? '<span class="badge bg-success">Active</span>'
        : '<span class="badge bg-secondary">Inactive</span>';

    $data[] = [
        "name" => $r['name'],
        "session_name" => $r['session_name'],
        "status" => $status,
        "actions" => '
<button class="btn btn-success btn-sm activateSemester"
    data-id="' . $r['id'] . '">Activate</button>
'
    ];
}

echo json_encode(["data" => $data]);
