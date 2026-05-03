<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();

$id = $_POST['id'] ?? null;
$status = $_POST['status'] ?? null;

/**
 * ----------------------------------------
 * VALIDATION
 * ----------------------------------------
 */

// Validate ID
if (!$id || !is_numeric($id)) {
    exit(json_encode([
        'status' => 'error',
        'message' => 'Invalid ID'
    ]));
}

// Validate status (must be 0 or 1)
if (!in_array($status, ['0', '1', 0, 1], true)) {
    exit(json_encode([
        'status' => 'error',
        'message' => 'Invalid status value'
    ]));
}


/**
 * ----------------------------------------
 * CHECK IF RECORD EXISTS
 * ----------------------------------------
 */
$exists = $model->getById('school_fee_settings', $id);

if (!$exists) {
    exit(json_encode([
        'status' => 'error',
        'message' => 'Record not found'
    ]));
}


/**
 * ----------------------------------------
 * UPDATE STATUS
 * ----------------------------------------
 */
$model->update('school_fee_settings', [
    'status' => $status
], ['id' => $id]);
$utility->logActivity(($status == 1 ? 'Fee activated successfully' : 'Fee deactivated successfully') . ' for assigned School Fee Setting with ID : ' . $id);
echo json_encode([
    'status' => 'success',
    'message' => $status == 1 ? 'Fee activated successfully' : 'Fee deactivated successfully'
]);

exit;