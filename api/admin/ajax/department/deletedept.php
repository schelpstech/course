<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$response = ["status" => false];

try {

    $id = $_POST['id'];

    $model->delete("departments", ["id" => $id]);

    $response["status"] = true;
    $response["message"] = "Department deleted";

} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
exit;