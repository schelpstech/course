<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();

$id = $_POST['id'] ?? '';
$status = $_POST['status'] ?? '';

$allowed = ['pending','submitted','approved','rejected'];

if (!$id || !in_array($status, $allowed)) {
    echo json_encode(["status" => "error"]);
    exit;
}

$update = $model->update("course_registered", [
    "approval_status" => $status,
    "approval_date" => date("Y-m-d H:i:s")
], [
    "course_regID" => $id
]);

if ($update) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error"]);
}