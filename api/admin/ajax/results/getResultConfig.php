<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('create_result_config');

$id = (int)($_GET['id'] ?? 0);

$config = $model->queryOne("
    SELECT *
    FROM result_config
    WHERE id = :id
    LIMIT 1
", ['id' => $id]);

if ($config && !empty($config['submission_deadline'])) {
    $config['submission_deadline_input'] = date('Y-m-d\TH:i', strtotime($config['submission_deadline']));
}

echo json_encode([
    'status' => (bool)$config,
    'config' => $config
]);
exit;
