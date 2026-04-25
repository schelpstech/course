<?php
require_once '../../../../start.inc.php';
$utility->requireAdmin(); // 🔐  FIREWALL

$model->update("academic_sessions", ["is_active" => 0], ["is_active" => 1]);
$model->update("academic_sessions", ["is_active" => 1], ["id" => $_POST['id']]);
$utility->logActivity('Activated Academic Session with ID : ' . $_POST['id']);
echo json_encode(["status" => true, "message" => "Session activated"]);
