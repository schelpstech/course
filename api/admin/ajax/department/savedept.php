<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$response = ["status" => false];

try {

    $id = $_POST['dept_id'] ?? null;

    $data = [
        "programme_id" => $_POST['programme_id'],
        "name" => $_POST['name'],
        "code" => $_POST['code'],
        "is_active" => 1
    ];

    if (empty($data['programme_id']) || empty($data['name'])) {
        throw new Exception("Programme and Department Name required");
    }

    if ($id) {

        $model->update("department", $data, ["id" => $id]);
        $response["message"] = "Department updated";

    } else {

        $model->insert_data("department", $data);
        $response["message"] = "Department created";
    }

    $response["status"] = true;

} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
exit;