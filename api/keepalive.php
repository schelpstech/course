<?php
require_once '../start.inc.php';

header('Content-Type: application/json');

// session expired check
if (!isset($_SESSION['LAST_ACTIVITY'])) {
    echo json_encode(["status" => "expired"]);
    exit;
}

// timeout logic
define('SESSION_TIMEOUT', 5 * 60);

if ((time() - $_SESSION['LAST_ACTIVITY']) > SESSION_TIMEOUT) {
    session_unset();
    session_destroy();

    echo json_encode(["status" => "expired"]);
    exit;
}

// refresh activity
$_SESSION['LAST_ACTIVITY'] = time();

echo json_encode([
    "status" => "active",
    "expires_at" => $_SESSION['LAST_ACTIVITY'] + SESSION_TIMEOUT
]);
exit;