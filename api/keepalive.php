<?php
require_once '../start.inc.php';
header('Content-Type: application/json');
if (isset($_SESSION['LAST_ACTIVITY'])) {
    $_SESSION['LAST_ACTIVITY'] = time();
    echo json_encode([
        "status" => "active",
        "expires_at" => $_SESSION['LAST_ACTIVITY'] + SESSION_TIMEOUT
    ]);
} else {
    echo json_encode(["status" => "expired"]);
}