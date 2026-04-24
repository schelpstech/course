<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');

$response = ["status" => false, "message" => ""];
$utility->requireAdmin(); // 🔐  FIREWALL

try {

    $id = $_POST['prog_id'] ?? null;

    $data = [
        "institution_id" => $_POST['institution_id'],
        "name" => $_POST['name'],
        "code" => $_POST['code'],
        "is_active" => 1
    ];

    if (empty($data['institution_id']) || empty($data['name'])) {
        throw new Exception("Institution and Name are required");
    }

    if ($id) {

        // UPDATE
        $model->update("programmes", $data, ["id" => $id]);

        $response["status"] = true;
        $response["message"] = "Programme updated successfully";

    } else {

        // INSERT
        $model->insert_data("programmes", $data);

        $response["status"] = true;
        $response["message"] = "Programme created successfully";
    }

} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
exit;