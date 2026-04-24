<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$response = ["data" => []];

$levels = $model->getRows('levels', [
    'select' => '
        levels.*, 
        department.name AS department_name, 
        programmes.name AS programme_name, 
        institutions.name AS institution_name,
        department.programme_id,
        programmes.institution_id
    ',
    'join' => [
        'department' => 'ON department.id = levels.department_id',
        'programmes' => 'ON programmes.id = department.programme_id',
        'institutions' => 'ON institutions.id = programmes.institution_id'
    ],
    'order_by' => 'levels.id DESC'
]);

if (!is_array($levels) || empty($levels)) {
    echo json_encode($response);
    exit;
}

foreach ($levels as $lvl) {

    $isActive = !empty($lvl['is_active']);

    $status = '<span class="badge ' . ($isActive ? 'bg-success' : 'bg-danger') . '">'
        . ($isActive ? 'Active' : 'Disabled') .
        '</span>';

    $toggleBtn = '<button class="btn btn-sm toggleBtn ' . ($isActive ? 'btn-success' : 'btn-danger') . '"
        data-id="' . $lvl['id'] . '">
        ' . ($isActive ? 'Disable' : 'Enable') . '
    </button>';

    $response["data"][] = [

        "department_name" => htmlspecialchars($lvl['department_name']),
        "programme_name" => htmlspecialchars($lvl['programme_name']),
        "institution_name" => htmlspecialchars($lvl['institution_name']),

        "department" => $lvl['department_id'],
        "programme_id" => $lvl['programme_id'],
        "institution_id" => $lvl['institution_id'],

        "name" => htmlspecialchars($lvl['name']),
        "code" => htmlspecialchars($lvl['code']),
        "status" => $status,

        "actions" => '
            <button class="btn btn-primary btn-sm editBtn"
                data-id="' . $lvl['id'] . '"
                data-institution="' . $lvl['institution_id'] . '"
                data-programme="' . $lvl['programme_id'] . '"
                data-department="' . $lvl['department_id'] . '"

                data-institution-name="' . htmlspecialchars($lvl['institution_name']) . '"
                data-programme-name="' . htmlspecialchars($lvl['programme_name']) . '"
                data-department-name="' . htmlspecialchars($lvl['department_name']) . '"

                data-name="' . htmlspecialchars($lvl['name']) . '"
                data-code="' . htmlspecialchars($lvl['code']) . '">
                Edit
            </button>

            <br><br>

            ' . $toggleBtn . '
        '
    ];
}

echo json_encode($response);
exit;
