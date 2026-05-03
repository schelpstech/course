<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();

$id = $_POST['id'] ?? null;

if (!$id || !is_numeric($id)) {
    exit(json_encode([
        'status' => 'error',
        'message' => 'Invalid ID'
    ]));
}

$data = $model->getById('institution_payment_terms', $id);

// Optional: Ensure record belongs to expected context
// e.g., institution exists, or admin has rights

if ($data['institution_id'] == null) {
    exit(json_encode([
        'status' => 'error',
        'message' => 'Invalid record'
    ]));
}

if (!$data) {
    exit(json_encode([
        'status' => 'error',
        'message' => 'Record not found'
    ]));
}

echo json_encode([
    'status' => 'success',
    'data' => $data
]);
