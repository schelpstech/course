<?php
require_once __DIR__ . '/../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();

$admission = new Admission($db, $model, $utility, $qrcode, $mailservice);
$admin = $model->getRows('admins', [
    'where' => ['id' => $_SESSION['admin_id']],
    'return_type' => 'single'
]);

if (!$admin || !in_array($admin['role'], ['super', 'admission', 'registry'], true)) {
    http_response_code(403);
    echo json_encode(['status' => false, 'message' => 'Admission officer access required.']);
    exit;
}

function admission_admin_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}
