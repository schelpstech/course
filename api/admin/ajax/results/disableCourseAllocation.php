<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requireAny(['allocate_courses', 'allocate_dept_courses']);

$response = [
    'status' => false,
    'message' => 'Unable to disable allocation.',
    'csrf_token' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request.');
    }

    if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'course_allocation_disable')) {
        throw new Exception('Invalid or expired request.');
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id < 1) {
        throw new Exception('Invalid allocation record.');
    }

    $allocation = $model->queryOne("
        SELECT ca.*, c.course_code, c.course_title
        FROM course_allocations ca
        JOIN courses c ON c.id = ca.course_id
        WHERE ca.id = :id
        LIMIT 1
    ", ['id' => $id]);

    if (!$allocation) {
        throw new Exception('Allocation record was not found.');
    }

    $rbac->requireDepartmentScope((int)$allocation['department_id']);

    if ($allocation['status'] === 'inactive') {
        throw new Exception('This allocation is already disabled.');
    }

    $model->update('course_allocations', [
        'status' => 'inactive'
    ], ['id' => $id]);

    $rbac->logAudit('Course allocation disabled', 'course_allocation', (string)$id, $allocation, [
        'status' => 'inactive'
    ]);

    $response['status'] = true;
    $response['message'] = 'Allocation disabled. You can now modify it for reallocation.';
} catch (Throwable $e) {
    $response['message'] = $e->getMessage();
}

$response['csrf_token'] = $utility->generateCsrf('course_allocation_disable');
echo json_encode($response);
exit;
