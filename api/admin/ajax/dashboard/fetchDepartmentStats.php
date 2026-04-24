<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$response = ["data" => []];

try {

    $rows = $model->getRows('students', [
        'select' => 'department.id AS dept_id, department.name AS dept_name, COUNT(students.id) AS total_students',
        'join' => [
            'department' => 'ON department.id = students.department_id'
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
            "total" => $row["total_students"]
        ];
    }

} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
exit;