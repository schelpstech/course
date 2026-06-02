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

    $rows = $model->getRows('students', [
        'select' => '
        department.id AS dept_id,
        department.code AS dept_name,
        COUNT(students.id) AS total_students,
        SUM(CASE 
            WHEN semesterregistration.courses_registered = 1 THEN 1 
            ELSE 0 
        END) AS registered_students
    ',
        'join' => [
            'department' => 'ON department.id = students.department_id',
            'semesterregistration' => '
            ON semesterregistration.student_id = students.id
            AND semesterregistration.session_id = ' . $currentsemester['session_id'] . '
            AND semesterregistration.semester_id = ' . $currentsemester['id']
        ],
        'group_by' => 'students.department_id'
    ]);

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
