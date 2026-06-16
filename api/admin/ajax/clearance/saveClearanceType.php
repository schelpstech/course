<?php

require_once '../../../../start.inc.php';

header('Content-Type: application/json');

$utility->requireAdmin();

$id             = $_POST['id'] ?? null;
$institution_id = $_POST['institution_id'] ?? null;
$name           = trim($_POST['name'] ?? '');
$code           = strtoupper(trim($_POST['code'] ?? ''));
$description    = trim($_POST['description'] ?? '');
$is_mandatory   = $_POST['is_mandatory'] ?? 1;
$status         = $_POST['status'] ?? 1;

if (
    empty($institution_id) ||
    empty($name) ||
    empty($code)
) {

    exit(json_encode([
        'status' => 'error',
        'message' => 'All required fields must be supplied'
    ]));
}

$data = [
    'institution_id' => $institution_id,
    'name' => $name,
    'code' => $code,
    'description' => $description,
    'is_mandatory' => $is_mandatory,
    'status' => $status
];

if ($id) {

    $model->update(
        'clearance_types',
        $data,
        ['id' => $id]
    );

    $utility->logActivity(
        "Updated Clearance Type : {$name}"
    );

    echo json_encode([
        'status' => 'success',
        'message' => 'Clearance Type Updated'
    ]);
} else {

    $exists = $model->exists(
        'clearance_types',
        [
            'institution_id' => $institution_id,
            'code' => $code
        ]
    );

    if ($exists) {

        exit(json_encode([
            'status' => 'error',
            'message' => 'Code already exists'
        ]));
    }

    $model->insert_data(
        'clearance_types',
        $data
    );

    $utility->logActivity(
        "Created Clearance Type : {$name}"
    );

    echo json_encode([
        'status' => 'success',
        'message' => 'Clearance Type Created'
    ]);
}
