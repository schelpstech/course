<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL
$response = ["data" => []];

$courses = $model->getRows("courses", [
    'select' => '
        courses.*, 

        levels.id AS level_id,
        levels.code AS level_name,

        department.id AS department_id,

        programmes.id AS programme_id,

        institutions.id AS institution_id,

        semesters.id AS semester_id,
        semesters.name AS semester_name
    ',
    'join' => [
        'levels' => 'ON levels.id = courses.level_id',
        'department' => 'ON department.id = levels.department_id',
        'programmes' => 'ON programmes.id = department.programme_id',
        'institutions' => 'ON institutions.id = programmes.institution_id',
        'semesters' => 'ON semesters.id = courses.semester_id'
    ],
    'order_by' => 'courses.id DESC'
]);

if (!is_array($courses)) {
    echo json_encode($response);
    exit;
}

foreach ($courses as $c) {

    $status = $c['course_status']
        ? '<span class="badge bg-success">Active</span>'
        : '<span class="badge bg-danger">Disabled</span>';

    $toggleBtn = '<button class="btn btn-sm toggleCourse ' .
        ($c['course_status'] ? 'btn-success' : 'btn-danger') . '"
        data-id="' . $c['id'] . '">
        ' . ($c['course_status'] ? 'Disable' : 'Enable') . '
    </button>';

    $response["data"][] = [
        "code" => htmlspecialchars($c["course_code"]),
        "title" => htmlspecialchars($c["course_title"]),
        "unit" => $c["unit"],
        "level" => $c["level_name"],
        "semester" => $c["semester_name"],
        "status" => $status,
        "actions" => '
    <button class="btn btn-primary btn-sm editCourse"
        data-id="' . $c['id'] . '"
        data-code="' . htmlspecialchars($c['course_code']) . '"
        data-title="' . htmlspecialchars($c['course_title']) . '"
        data-unit="' . $c['unit'] . '"
        data-type="' . $c['course_type'] . '"

        data-institution="' . $c['institution_id'] . '"
        data-programme="' . $c['programme_id'] . '"
        data-department="' . $c['department_id'] . '"
        data-level="' . $c['level_id'] . '"
        data-semester="' . $c['semester_id'] . '">
        Edit
    </button>

    <br><br>

    ' . $toggleBtn . '
'
    ];
}

echo json_encode($response);
exit;
