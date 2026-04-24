<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$id = $_GET['programme_id'];

$department = $model->getRows('department', [
    'where' => ['programme_id' => $id],
    'order_by' => 'name ASC'
]);

echo json_encode([
    "data" => $department
]);

exit;
