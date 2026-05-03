<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();

$id = $_POST['id'] ?? null;
$institution_id = $_POST['institution_id'] ?? null;
$min_percent = $_POST['min_percent'] ?? null;
$status = $_POST['status'] ?? 1;


// Validation
if (!$institution_id || !$min_percent) {
    exit(json_encode([
        'status' => 'error',
        'message' => 'All fields are required'
    ]));
}

if ($min_percent < 1 || $min_percent > 100) {
    exit(json_encode([
        'status' => 'error',
        'message' => 'Percentage must be between 1 and 100'
    ]));
}

$exists = $model->exists('institution_payment_terms', [
    'institution_id' => $institution_id
]);

if ($exists && !$id) {
    exit(json_encode([
        'status' => 'error',
        'message' => 'Payment terms already exist for this institution'
    ]));
}


$data = [
    'institution_id' => $institution_id,
    'min_percent' => $min_percent,
    'status' => $status
];

if ($id) {
    $model->update('institution_payment_terms', $data, ['id' => $id]);
    $utility->logActivity('Updated Payment Terms for Institution : ' . $_POST['institution_id']);
    echo json_encode([
        'status' => 'success',
        'message' => 'Payment terms updated'
    ]);
} else {
    $model->insert_data('institution_payment_terms', $data);
    $utility->logActivity('Created Payment Terms for Institution : ' . $_POST['institution_id']);
    echo json_encode([
        'status' => 'success',
        'message' => 'Payment terms created'
    ]);
}
