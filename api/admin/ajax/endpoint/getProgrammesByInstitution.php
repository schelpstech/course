<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$id = $_GET['institution_id'];

$programmes = $model->getRows('programmes', [
    'where' => ['institution_id' => $id],
    'order_by' => 'name ASC'
]);

echo json_encode([
    "data" => $programmes
]);

exit;