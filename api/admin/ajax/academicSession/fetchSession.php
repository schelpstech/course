<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$response = ["data" => []];

$rows = $model->getRows("academic_sessions", ['order_by' => 'id DESC']);

$data = [];

foreach ($rows as $r) {

    $status = $r['is_active']
        ? '<span class="badge bg-success">Active</span>'
        : '<span class="badge bg-secondary">Inactive</span>';

    $data[] = [
        "name" => $r['name'],
        "duration" => $r['start_date'] . " - " . $r['end_date'],
        "status" => $status,
        "actions" => '
      <button class="btn btn-sm btn-primary editSession"
        data-id="' . $r['id'] . '"
        data-name="' . $r['name'] . '"
        data-start="' . $r['start_date'] . '"
        data-end="' . $r['end_date'] . '">Edit</button><br><br>

      <button class="btn btn-sm btn-success activateSession"
        data-id="' . $r['id'] . '">Activate</button>
    '
    ];
}

echo json_encode(["data" => $data]);
