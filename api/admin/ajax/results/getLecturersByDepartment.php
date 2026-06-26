<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('allocate_courses');

$departmentId = (int)($_GET['department_id'] ?? 0);

try {
    $scopeDepartmentId = $rbac->requireDepartmentScope($departmentId > 0 ? $departmentId : null);
    $departmentId = $scopeDepartmentId ?: $departmentId;

    $lecturers = $model->query("
        SELECT l.id, CONCAT(COALESCE(a.title, ''), ' ', a.fullname) AS name
        FROM lecturers l
        JOIN admins a ON a.id = l.admin_id
        WHERE l.status = 1
        AND a.ix_active = 1
        AND (:department_id = 0 OR l.department_id = :department_id)
        ORDER BY a.fullname ASC
    ", ['department_id' => $departmentId]) ?: [];

    echo json_encode(['status' => true, 'data' => $lecturers]);
} catch (Throwable $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage(), 'data' => []]);
}
exit;
