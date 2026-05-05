<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();

$id = $_POST['id'] ?? null;

$data = [
    'semester_id' => $_POST['semester_id'],
    'department_id' => $_POST['department_id'],
    'level_id' => $_POST['level_id'],
    'amount' => $_POST['amount'],
    'status' => $_POST['status']
];

// Prevent duplicates
$exists = $model->getRows('school_fee_settings', [
    'where' => [
        'semester_id' => $data['semester_id'],
        'department_id' => $data['department_id'],
        'level_id' => $data['level_id']
    ],
    'return_type' => 'single'
]);

if ($exists && !$id) {
    exit(json_encode([
        'status' => 'error',
        'message' => 'Fee already assigned for this combination'
    ]));
}

if ($id) {
    $model->update('school_fee_settings', $data, ['id' => $id]);
    $utility->logActivity('Updated Fee for Semester : ' . $_POST['semester_id'] . ' for Department : ' . $_POST['department_id'] . ' in Level : ' . $_POST['level_id']);
    echo json_encode(['status' => 'success', 'message' => 'Updated successfully']);
    exit;
} else {
    $model->insert_data('school_fee_settings', $data);
    $utility->logActivity('Created New Fee for Semester : ' . $_POST['semester_id'] . ' for Department : ' . $_POST['department_id'] . ' in Level : ' . $_POST['level_id']);
    echo json_encode(['status' => 'success', 'message' => 'Assigned successfully']);
    exit;
}
