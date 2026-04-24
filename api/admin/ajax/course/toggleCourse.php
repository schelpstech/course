<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

try {

    $id = $_POST['id'];

    // get current status
    $course = $model->getRows("courses", [
        "where" => ["id" => $id]
    ]);

    if (!$course) {
        throw new Exception("Course not found");
    }

    $current = $course[0]['course_status'];
    $newStatus = $current ? 0 : 1;

    $model->update("courses", ["course_status" => $newStatus], ["id" => $id]);

    echo json_encode([
        "status" => true,
        "message" => $newStatus ? "Course enabled" : "Course disabled"
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => false,
        "message" => $e->getMessage()
    ]);

}