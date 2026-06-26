<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$id = (int)($_GET['institution_id'] ?? 0);
$scopeDepartmentId = isset($rbac) ? $rbac->departmentScopeId() : null;

if ($scopeDepartmentId) {
    $programmes = $model->query("
        SELECT DISTINCT p.*
        FROM programmes p
        JOIN department d ON d.programme_id = p.id
        WHERE p.institution_id = :institution_id
        AND d.id = :department_id
        ORDER BY p.name ASC
    ", [
        'institution_id' => $id,
        'department_id' => $scopeDepartmentId
    ]) ?: [];
} else {
    $programmes = $model->getRows('programmes', [
        'where' => ['institution_id' => $id],
        'order_by' => 'name ASC'
    ]);
}

echo json_encode([
    "data" => $programmes
]);

exit;
