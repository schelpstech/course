<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$id = (int)($_GET['programme_id'] ?? 0);
$scopeDepartmentId = isset($rbac) ? $rbac->departmentScopeId() : null;

if ($scopeDepartmentId) {
    $department = $model->query("
        SELECT *
        FROM department
        WHERE programme_id = :programme_id
        AND id = :department_id
        ORDER BY name ASC
    ", [
        'programme_id' => $id,
        'department_id' => $scopeDepartmentId
    ]) ?: [];
} else {
    $department = $model->getRows('department', [
        'where' => ['programme_id' => $id],
        'order_by' => 'name ASC'
    ]);
}

echo json_encode([
    "data" => $department
]);

exit;
