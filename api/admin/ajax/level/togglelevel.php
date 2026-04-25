<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$response = ["status" => false];

try {

    $id = $_POST['id'];

    $level = $model->getById("levels", $id);

    $newStatus = $level['is_active'] ? 0 : 1;

    $model->update("levels", [
        "is_active" => $newStatus
    ], ["id" => $id]);
    $utility->logActivity(($newStatus ? 'Enabled' : 'Disabled') . ' Level with ID : ' . $id);
    $response["status"] = true;
    $response["message"] = $newStatus ? "Enabled" : "Disabled";

} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
exit;