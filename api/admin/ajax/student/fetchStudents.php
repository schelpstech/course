<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$response = ["data" => []];

$students = $model->getRows("students", [
    "select" => "
        students.*,
        users.is_active,
        users.email,
        users.institution_id AS institution_id,
        programmes.name AS programme_name,
        levels.code AS level_name
    ",
    "join" => [
        "users" => "ON users.id = students.student_id",
        "programmes" => "ON programmes.id = students.programme_id",
        "levels" => "ON levels.id = students.level_id"
    ],
    "order_by" => "students.id DESC"
]);

if (!is_array($students)) {
    echo json_encode($response);
    exit;
}

foreach ($students as $s) {

    $status = $s['is_active']
        ? '<span class="badge bg-success">Active</span>'
        : '<span class="badge bg-danger">Disabled</span>';

    $toggleBtn = '<button class="btn btn-sm toggleStudent ' .
        ($s['is_active'] ? 'btn-danger' : 'btn-success') . '"
        data-id="' . $s['student_id'] . '">
        ' . ($s['is_active'] ? 'Disable' : 'Enable') . '
    </button>';

    $resetpwd = '<br><hr><button class="btn btn-sm resetPassword btn-success' . '"
        data-id="' . $s['student_id'] . '">
        Reset Password
    </button>';

    $response["data"][] = [
        "name" => htmlspecialchars(ucfirst($s["first_name"]) . ' ' . htmlspecialchars(ucfirst($s["other_name"])) . ' ' . htmlspecialchars(ucfirst($s["last_name"]))),
        "matric" => htmlspecialchars(strtoupper($s["matric_no"])) . "<br>" . htmlspecialchars($s["email"]),
        "programme" => htmlspecialchars($s["programme_name"]) . "<br>" . htmlspecialchars($s["level_name"]),
        "status" => $status,
        "actions" => '
            <button class="btn btn-primary btn-sm editStudent"
                data-id="' . $s['student_id'] . '"
                data-matric="' . htmlspecialchars(strtoupper($s['matric_no']), ENT_QUOTES) . '"
                data-email="' . htmlspecialchars($s['email'], ENT_QUOTES) . '"
                data-first="' . htmlspecialchars(ucfirst($s['first_name']), ENT_QUOTES) . '"
                data-other="' . htmlspecialchars(ucfirst($s['other_name']), ENT_QUOTES) . '"
                data-last="' . htmlspecialchars(ucfirst($s['last_name']), ENT_QUOTES) . '"
                data-dob="' . $s['dateofbirth'] . '"
                data-gender="' . $s['gender'] . '"
                data-institution="' . $s['institution_id'] . '"
                data-programme="' . $s['programme_id'] . '"
                data-department="' . $s['department_id'] . '"
                data-level="' . $s['level_id'] . '">
                Edit
            </button>

            <br><br>

            <button class="btn btn-danger btn-sm deleteStudent"
                data-id="' . $s['student_id'] . '">
                Delete
            </button>

            <br><br>

            ' . $toggleBtn . '
            ' . $resetpwd . '
        '
    ];
}

echo json_encode($response);
exit;
