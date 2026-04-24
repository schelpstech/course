<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$institutions = $model->getRows('institutions', [
    'order_by' => 'name ASC'
]);

echo json_encode([
    'data' => $institutions
]);

exit;