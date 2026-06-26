<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();

$response = [
    'status' => false,
    'message' => 'Unable to moderate result sheet.',
    'csrf_token' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request.');
    }

    if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'department_result_moderate')) {
        throw new Exception('Invalid or expired request.');
    }

    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');

    if (!in_array($action, ['approve', 'return', 'reject'], true)) {
        throw new Exception('Invalid moderation action.');
    }

    if ($action === 'approve') {
        $rbac->requirePermission('approve_results');
    } else {
        $rbac->requirePermission('moderate_results');
    }

    if (in_array($action, ['return', 'reject'], true) && $remarks === '') {
        throw new Exception('Remarks are required when returning or rejecting a result sheet.');
    }

    $departmentId = $rbac->requireDepartmentScope();
    $params = ['id' => $id];
    $scopeSql = '';
    if ($departmentId) {
        $scopeSql = 'AND ca.department_id = :department_id';
        $params['department_id'] = $departmentId;
    }

    $sheet = $model->queryOne("
        SELECT rs.*
        FROM result_sheets rs
        JOIN course_allocations ca ON ca.id = rs.course_allocation_id
        WHERE rs.id = :id
        {$scopeSql}
        LIMIT 1
    ", $params);

    if (!$sheet) {
        throw new Exception('Result sheet not found in your department.');
    }

    if ($action === 'approve' && (!in_array($sheet['ca_status'], ['submitted', 'approved'], true) || !in_array($sheet['exam_status'], ['submitted', 'approved'], true))) {
        throw new Exception('CA and Exam must be submitted before approval.');
    }

    $update = [
        'moderated_by' => $_SESSION['admin_id'] ?? null,
        'moderated_at' => date('Y-m-d H:i:s'),
        'remarks' => $remarks
    ];

    if ($action === 'approve') {
        $update['ca_status'] = 'approved';
        $update['exam_status'] = 'approved';
        $update['moderation_status'] = 'approved';
        $message = 'Result sheet approved.';
    } elseif ($action === 'return') {
        $update['ca_status'] = 'returned';
        $update['exam_status'] = 'returned';
        $update['moderation_status'] = 'returned';
        $message = 'Result sheet returned to lecturer.';
    } else {
        $update['moderation_status'] = 'rejected';
        $message = 'Result sheet rejected.';
    }

    $model->update('result_sheets', $update, ['id' => $id]);
    $rbac->logAudit('Department result sheet ' . $action, 'result_sheet', (string)$id, $sheet, $update);

    $response['status'] = true;
    $response['message'] = $message;
} catch (Throwable $e) {
    $response['message'] = $e->getMessage();
}

$response['csrf_token'] = $utility->generateCsrf('department_result_moderate');
echo json_encode($response);
exit;
