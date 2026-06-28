<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requireAny(['allocate_courses', 'allocate_dept_courses']);

$id = (int)($_GET['id'] ?? 0);

try {
    $allocation = $model->queryOne("
        SELECT ca.*, d.programme_id
        FROM course_allocations ca
        JOIN department d ON d.id = ca.department_id
        WHERE ca.id = :id
        LIMIT 1
    ", ['id' => $id]);

    if ($allocation) {
        $rbac->requireDepartmentScope((int)$allocation['department_id']);
    }

    echo json_encode([
        'status' => (bool)$allocation,
        'allocation' => $allocation
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage(),
        'allocation' => null
    ]);
}
exit;
