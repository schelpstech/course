<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requireAny(['allocate_courses', 'create_result_config']);

$levelId = (int)($_GET['level_id'] ?? 0);
$departmentId = (int)($_GET['department_id'] ?? 0);
$sessionId = (int)($_GET['session_id'] ?? 0);
$semesterId = (int)($_GET['semester_id'] ?? 0);
$excludeAllocationId = (int)($_GET['exclude_allocation_id'] ?? 0);

try {
    $scopeDepartmentId = $rbac->departmentScopeId();

    if ($scopeDepartmentId) {
        if ($departmentId > 0 && $departmentId !== $scopeDepartmentId) {
            throw new Exception('You cannot access courses outside your assigned department.');
        }

        $departmentId = $scopeDepartmentId;
    }

    $params = [];
    $where = "WHERE c.course_status = 1";

    if ($levelId > 0) {
        $where .= " AND c.level_id = :level_id";
        $params['level_id'] = $levelId;
    }

    if ($departmentId > 0) {
        $where .= " AND l.department_id = :department_id";
        $params['department_id'] = $departmentId;
    }

    if ($sessionId > 0 && $semesterId > 0) {
        $where .= "
            AND NOT EXISTS (
                SELECT 1
                FROM course_allocations ca
                WHERE ca.course_id = c.id
                AND ca.academic_session_id = :session_id
                AND ca.semester_id = :semester_id
                AND ca.status = 'active'
                AND (:exclude_allocation_id = 0 OR ca.id <> :exclude_allocation_id)
            )
        ";
        $params['session_id'] = $sessionId;
        $params['semester_id'] = $semesterId;
        $params['exclude_allocation_id'] = $excludeAllocationId;
    }

    $courses = $model->query("
        SELECT c.id, CONCAT(c.course_code, ' - ', c.course_title) AS name
        FROM courses c
        JOIN levels l ON l.id = c.level_id
        {$where}
        ORDER BY c.course_code ASC
    ", $params) ?: [];

    echo json_encode(['status' => true, 'data' => $courses]);
} catch (Throwable $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage(), 'data' => []]);
}
exit;
