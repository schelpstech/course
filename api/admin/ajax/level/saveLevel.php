<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$id = $_POST['level_id'] ?? null;

$data = [
    "department_id" => $_POST['department_id'],
    "name" => $_POST['name'],
    "code" => $_POST['code'],
    "is_active" => 1
];

if ($id) {
    $model->update("levels", $data, ["id" => $id]);
    $msg = "Updated successfully";
} else {
    $model->insert_data("levels", $data);
    $msg = "Created successfully";
}

echo json_encode(["status" => true, "message" => $msg]);
exit;
