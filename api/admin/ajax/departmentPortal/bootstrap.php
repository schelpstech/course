<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requireAny(['view_department_students', 'view_course_forms', 'manage_dept_courses', 'allocate_dept_courses', 'moderate_results', 'approve_results']);

try {
    $departmentId = $rbac->requireDepartmentScope();
    $where = $departmentId ? 'WHERE d.id = :department_id' : 'WHERE 1=1';
    $params = $departmentId ? ['department_id' => $departmentId] : [];

    $departments = $model->query("
        SELECT d.id, d.name, p.institution_id, d.programme_id
        FROM department d
        JOIN programmes p ON p.id = d.programme_id
        {$where}
        ORDER BY d.name ASC
    ", $params) ?: [];

    $levels = $model->query("
        SELECT lv.id, lv.name, lv.code, lv.department_id
        FROM levels lv
        JOIN department d ON d.id = lv.department_id
        {$where}
        ORDER BY lv.code ASC
    ", $params) ?: [];

    echo json_encode([
        'status' => true,
        'department_id' => $departmentId,
        'departments' => $departments,
        'levels' => $levels,
        'sessions' => $model->query("SELECT id, name FROM academic_sessions ORDER BY id DESC") ?: [],
        'semesters' => $model->query("SELECT id, session_id, name FROM semesters ORDER BY session_id DESC, id ASC") ?: []
    ]);
} catch (Throwable $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
exit;
