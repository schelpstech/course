<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$id = $_POST['id'];

$user = $model->getRows("users", ["where" => ["id" => $id]])[0];

$new = $user['is_active'] ? 0 : 1;

$model->update("users", ["is_active" => $new], ["id" => $id]);

echo json_encode([
    "status" => true,
    "message" => $new ? "Student activated" : "Student disabled"
]);