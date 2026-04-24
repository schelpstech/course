<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$response = ["status" => false];

try {

    $id = $_POST['id'] ?? null;
    $session_id = $_POST['session_id'];
    $name = $_POST['name'];
    $start = $_POST['start_date'] ?? null;
    $end = $_POST['end_date'] ?? null;

    // VALIDATION
    if (!$session_id || !$name) {
        throw new Exception("All fields are required");
    }

    if ($start && $end && $start > $end) {
        throw new Exception("Invalid date range");
    }

    // DUPLICATE CHECK (session_id + name)
    $existing = $model->getRows("semesters", [
        'where' => [
            'session_id' => $session_id,
            'name' => $name
        ]
    ]);

    if ($existing) {
        if (!$id || $existing[0]['id'] != $id) {
            throw new Exception("Semester already exists for this session");
        }
    }

    $data = [
        "session_id" => $session_id,
        "name" => $name,
        "start_date" => $start,
        "end_date" => $end
    ];

    if ($id) {
        $model->update("semesters", $data, ["id" => $id]);
        $msg = "Updated successfully";
    } else {
        $model->insert_data("semesters", $data);
        $msg = "Created successfully";
    }

    $response["status"] = true;
    $response["message"] = $msg;

} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
exit;