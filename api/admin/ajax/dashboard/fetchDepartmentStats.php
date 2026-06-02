<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$response = ["data" => []];

$currentsemester = $model->getRows('semesters', [
    'where' => ['is_active' => 1],
    'return_type' => 'single'
]);

try {


    $sql = "
SELECT 
    d.id AS dept_id,
    d.code AS dept_name,
    COUNT(s.student_id) AS total_students,
    SUM(CASE 
        WHEN sr.courses_registered = 1 THEN 1 
        ELSE 0 
    END) AS registered_students

FROM students s

LEFT JOIN department d 
    ON d.id = s.department_id

LEFT JOIN semesterregistration sr 
    ON sr.student_id = s.student_id
    AND sr.session_id = {$currentsemester['session_id']}
    AND sr.semester_id = {$currentsemester['id']}

GROUP BY s.department_id
";

    $rows = $model->query($sql);

    if (!is_array($rows)) {
        echo json_encode($response);
        exit;
    }

    foreach ($rows as $row) {
        $response["data"][] = [
            "department" => htmlspecialchars($row["dept_name"]),
            "total" => $row["total_students"],
            "registered" => $row["registered_students"],
            "percentage" => $row["total_students"] > 0
                ? round(($row["registered_students"] / $row["total_students"]) * 100, 2)
                : 0
        ];
    }
} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
exit;
