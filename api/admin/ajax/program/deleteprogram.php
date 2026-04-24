<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$response = ["status" => false];

try {

    $id = $_POST['id'] ?? null;

    if (!$id) {
        throw new Exception("Invalid programme ID");
    }

    $model->delete("programmes", ["id" => $id]);

    $response["status"] = true;
    $response["message"] = "Programme deleted successfully";

} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
exit;