<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$response = ["data" => []];

try {

    $departments = $model->getRows('department', [
        'select' => 'department.*, programmes.name AS programme_name',
        'join' => [
            'programmes' => 'ON programmes.id = department.programme_id'
        ],
        'order_by' => 'department.id DESC'
    ]);

    // ✅ SAFETY CHECK (CRITICAL FIX)
    if (!is_array($departments) || empty($departments)) {
        echo json_encode($response);
        exit;
    }

    foreach ($departments as $dept) {

        $isActive = !empty($dept['is_active']);

        $status = '<span class="badge '.($isActive ? 'bg-success' : 'bg-danger').'">'
            .($isActive ? 'Active' : 'Disabled').
        '</span>';

        $toggleBtn = '<button class="btn btn-sm toggleBtn '.($isActive ? 'btn-success' : 'btn-danger').'"
            data-id="'.$dept['id'].'">
            '.($isActive ? 'Disable' : 'Enable').'
        </button>';

        $response["data"][] = [
            "programme" => htmlspecialchars($dept["programme_name"] ?? 'N/A'),
            "name" => htmlspecialchars($dept["name"] ?? ''),
            "code" => htmlspecialchars($dept["code"] ?? ''),
            "status" => $status,

            "actions" => '
                <button class="btn btn-sm btn-primary editBtn"
                    data-id="'.$dept['id'].'"
                    data-programme="'.$dept['programme_id'].'"
                    data-name="'.htmlspecialchars($dept['name']).'"
                    data-code="'.htmlspecialchars($dept['code']).'">
                    Edit
                </button><br><br>

                <button class="btn btn-sm btn-danger deleteBtn"
                    data-id="'.$dept['id'].'">
                    Delete
                </button><br><br>

                '.$toggleBtn.'
            '
        ];
    }

} catch (Throwable $e) {

    // ❌ NEVER break JSON structure
    $response = [
        "data" => [],
        "error" => $e->getMessage()
    ];
}

echo json_encode($response);
exit;