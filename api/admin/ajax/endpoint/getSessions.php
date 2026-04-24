<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$activeSession = $model->getRows('academic_sessions', [
    'order_by' => 'name ASC'
]);

echo json_encode([
    'data' => $activeSession
]);

exit;