<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

try {

    $id = $_POST['id'];

    // deactivate all
    $model->update("semesters", ["is_active" => 0], ["is_active" => 1]);

    // activate one
    $model->update("semesters", ["is_active" => 1], ["id" => $id]);

    echo json_encode([
        "status" => true,
        "message" => "Semester activated"
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => false,
        "message" => $e->getMessage()
    ]);
}