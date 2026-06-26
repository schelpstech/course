<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_grading_rules');

$id = (int)($_GET['id'] ?? 0);

$rule = $model->queryOne("
    SELECT *
    FROM grading_rules
    WHERE id = :id
    LIMIT 1
", ['id' => $id]);

echo json_encode([
    'status' => (bool)$rule,
    'rule' => $rule
]);
exit;
