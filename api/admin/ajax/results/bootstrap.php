<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requireAny(['allocate_courses', 'create_result_config', 'manage_grading_rules']);

$scopeDepartmentId = $rbac->departmentScopeId();
$institutionParams = [];
$institutionScopeSql = 'WHERE i.is_active = 1';

if ($scopeDepartmentId) {
    $institutionScopeSql .= ' AND d.id = :department_id';
    $institutionParams['department_id'] = $scopeDepartmentId;
}

echo json_encode([
    'status' => true,
    'department_scope_id' => $scopeDepartmentId,
    'sessions' => $model->query("
        SELECT id, name
        FROM academic_sessions
        ORDER BY id DESC
    ") ?: [],
    'semesters' => $model->query("
        SELECT id, session_id, name
        FROM semesters
        ORDER BY session_id DESC, id ASC
    ") ?: [],
    'institutions' => $model->query("
        SELECT DISTINCT i.id, i.name
        FROM institutions i
        " . ($scopeDepartmentId ? "JOIN programmes p ON p.institution_id = i.id JOIN department d ON d.programme_id = p.id" : "") . "
        {$institutionScopeSql}
        ORDER BY i.name ASC
    ", $institutionParams) ?: []
]);
exit;
