<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

// Optional: enforce GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request method"
    ]);
    exit;
}

try {

    // 📚 Get Academic Sessions
    $sessions = $model->getRows("academic_sessions", [
        "select" => "id, name",
        "order_by" => "id DESC"
    ]);

    $semesters = $model->getRows("semesters", [
        "select" => "id, name",
        "order_by" => "id ASC"
    ]);
    echo json_encode([
        "status" => "success",
        "sessions" => $sessions,
        "semesters" => $semesters
    ]);
} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
