<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');

$response = ["status" => false];

try {

    $id = $_POST['id'] ?? null;

    $course_code = trim($_POST['course_code']);
    $course_title = trim($_POST['course_title']);
    $course_type = $_POST['course_type'];
    $unit = $_POST['unit'];

    $level_id = $_POST['level_id'];
    $semester = $_POST['semester'];

    // ======================
    // VALIDATION
    // ======================
    if (
        empty($course_code) || empty($course_title) ||
        empty($course_type) || empty($unit) ||
        empty($level_id) || empty($semester)
    ) {
        throw new Exception("All fields are required");
    }

    // ======================
    // DUPLICATE CHECK
    // ======================
    $existing = $model->getRows("courses", [
        "where" => [
            "course_code" => $course_code,
            "level_id" => $level_id,
            "semester_id" => $semester
        ]
    ]);

    if ($existing) {
        if (!$id || $existing[0]['id'] != $id) {
            throw new Exception("Course code already exists for this programme");
        }
    }

    $data = [
        "course_code" => $course_code,
        "course_title" => $course_title,
        "course_type" => $course_type,
        "unit" => $unit,
        "level_id" => $level_id,
        "semester_id" => $semester
    ];

    if ($id) {

        $model->update("courses", $data, ["id" => $id]);
        $utility->logActivity('Updated the details of Course   : ' . $course_title . ' with course code ' . $course_code . ' for level ID ' . $level_id . ' and semester ID ' . $semester);
        $msg = "Course updated successfully";

    } else {

        $model->insert_data("courses", $data);
        $utility->logActivity('Created New Course   : ' . $course_title . ' with course code ' . $course_code . ' for level ID ' . $level_id . ' and semester ID ' . $semester);
        $msg = "Course created successfully";

    }

    $response["status"] = true;
    $response["message"] = $msg;

} catch (Exception $e) {

    $response["message"] = $e->getMessage();

}

echo json_encode($response);
exit;