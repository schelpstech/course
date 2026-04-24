<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$response = ["status" => false, "message" => ""];

try {

    $id = $_POST['id'] ?? null;

    $name = trim($_POST['name']);
    $start = $_POST['start_date'] ?? null;
    $end = $_POST['end_date'] ?? null;

    // 🔒 VALIDATION
    if (empty($name)) {
        throw new Exception("Session name is required");
    }

    // 🔒 DATE VALIDATION
    if (!empty($start) && !empty($end) && $start > $end) {
        throw new Exception("Start date cannot be after end date");
    }

    // 🔥 DUPLICATE CHECK
    $existing = $model->getRows("academic_sessions", [
        'where' => ['name' => $name]
    ]);

    if ($existing) {

        // If editing, allow same record
        if (!$id || $existing[0]['id'] != $id) {
            throw new Exception("Session already exists");
        }
    }

    $data = [
        "name" => $name,
        "start_date" => $start,
        "end_date" => $end
    ];

    if ($id) {

        $model->update("academic_sessions", $data, ["id" => $id]);
        $response["message"] = "Session updated successfully";

    } else {

        $model->insert_data("academic_sessions", $data);
        $response["message"] = "Session created successfully";

    }

    $response["status"] = true;

} catch (Exception $e) {

    $response["message"] = $e->getMessage();

}

echo json_encode($response);
exit;