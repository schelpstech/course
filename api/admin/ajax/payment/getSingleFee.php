<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();

$id = $_POST['id'] ?? null;

// 🔒 Validate ID
if (!$id || !is_numeric($id)) {
    exit(json_encode([
        'status' => 'error',
        'message' => 'Invalid ID'
    ]));
}

// Fetch record
$data = $model->getById('school_fee_settings', $id);

if (!$data) {
    exit(json_encode([
        'status' => 'error',
        'message' => 'Record not found'
    ]));
}

// Success
echo json_encode([
    'status' => 'success',
    'data' => $data
]);

exit;