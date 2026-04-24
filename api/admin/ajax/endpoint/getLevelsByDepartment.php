<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$id = $_GET['department_id'];

$level = $model->getRows('levels', [
    'where' => ['department_id' => $id],
    'order_by' => 'name ASC'
]);

echo json_encode([
    "data" => $level
]);

exit;
