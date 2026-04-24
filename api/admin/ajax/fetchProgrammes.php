<?php
require_once '../../../start.inc.php';

header('Content-Type: application/json');

$data = [];


$programmes = $model->getRows('programmes', [
    'select' => 'programmes.*, institutions.name AS institution_name',
    'join' => [
        'institutions' => 'ON programmes.institution_id = institutions.id'
    ],
    'order_by' => 'programmes.id DESC'
]);


// ✅ SAFETY CHECK (CRITICAL FIX)
if (!is_array($programmes) || empty($programmes)) {
    echo json_encode($response);
    exit;
}

foreach ($programmes as $prog) {

    $isActive = $prog['is_active'] ? true : false;

    $statusBadge = '<span class="badge ' . ($isActive ? 'bg-success' : 'bg-danger') . '">'
        . ($isActive ? 'Active' : 'Disabled') .
        '</span>';

    $toggleBtn = '<button class="btn btn-sm toggleBtn ' . ($isActive ? 'btn-success' : 'btn-danger') . '" 
        data-id="' . $prog['id'] . '">
        ' . ($isActive ? 'Disable' : 'Enable') . '
    </button>';

    $data[] = [
        'institution' => htmlspecialchars($prog['institution_name']),
        'name' => htmlspecialchars($prog['name']),
        'code' => htmlspecialchars($prog['code']),
        'status' => $statusBadge,

        'actions' => '
            <button class="btn btn-sm btn-primary editBtn"
                data-id="' . $prog['id'] . '"
                data-institution="' . $prog['institution_id'] . '"
                data-name="' . htmlspecialchars($prog['name']) . '"
                data-code="' . htmlspecialchars($prog['code']) . '">
                Edit
            </button><br><br>

            <button class="btn btn-sm btn-danger deleteBtn"
                data-id="' . $prog['id'] . '">
                Delete
            </button><br><br>

            ' . $toggleBtn . '
        '
    ];
}

echo json_encode(['data' => $data]);
exit;
