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
    $utility->logActivity('Updated Level with Code : ' . $_POST['code']);
    $msg = "Updated successfully";
} else {
    $model->insert_data("levels", $data);
     $utility->logActivity('Created New Level with code : ' . $_POST['code']);
    $msg = "Created successfully";
}

echo json_encode(["status" => true, "message" => $msg]);
exit;
