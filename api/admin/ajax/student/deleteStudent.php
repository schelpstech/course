<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

try {

    $id = $_POST['id'];

    $model->beginTransaction();

    $model->delete("students", ["student_id" => $id]);
    $model->delete("users", ["id" => $id]);
    $utility->logActivity('Deleted Student with ID : ' . $id);
    $model->commit();

    echo json_encode(["status" => true, "message" => "Student deleted"]);

} catch (Exception $e) {

    $model->rollBack();
    echo json_encode(["status" => false, "message" => "Delete failed"]);

}