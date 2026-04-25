<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$response = ["status" => false];

try {

    $id = $_POST['id'] ?? null;

    if (!$id) {
        throw new Exception("Invalid ID");
    }

    $programme = $model->getById("programmes", $id);

    if (!$programme) {
        throw new Exception("Programme not found");
    }

    $newStatus = $programme['is_active'] ? 0 : 1;

    $model->update("programmes", [
        "is_active" => $newStatus
    ], ["id" => $id]);
    $utility->logActivity(($newStatus ? 'Enabled' : 'Disabled') . ' Programme with ID : ' . $id);
    $response["status"] = true;
    $response["message"] = $newStatus ? "Programme enabled" : "Programme disabled";

} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
exit;