<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐 Protect route

$response = ["status" => false];

try {

    // =========================
    // VALIDATE INPUT
    // =========================
    $id = $_POST['id'] ?? null;

    if (!$id) {
        throw new Exception("User ID is required");
    }

    // =========================
    // CHECK USER EXISTS
    // =========================
    $user = $model->getRows("users", [
        "where" => ["id" => $id],
        "return_type" => "single"
    ]);

    if (!$user) {
        throw new Exception("User not found");
    }

    // =========================
    // GENERATE NEW PASSWORD
    // =========================
    $passwordPlain = "abcd1234";
    $passwordHash  = password_hash($passwordPlain, PASSWORD_DEFAULT);

    // =========================
    // UPDATE PASSWORD
    // =========================
    $update = $model->Newupdate(
        "users",
        [
            "password" => $passwordHash,
            "is_default_password" => 1
        ],
        ["id" => $id]
    );

    /**
     * NOTE:
     * update() returns:
     * - >0 → updated
     * - 0  → no change (still OK)
     * - false → query failed
     */

    if ($update === false) {
        throw new Exception("Failed to reset password");
    }

    // =========================
    // LOG ACTIVITY
    // =========================
    $utility->logActivity(
        'Password reset for Student ID: ' . $id . 
        ' by Admin ID: ' . $_SESSION['admin_id']
    );

    // =========================
    // SUCCESS RESPONSE
    // =========================
    $response["status"]  = true;
    $response["message"] = "Password reset successfully. Default password: {$passwordPlain}";

} catch (Exception $e) {

    $response["status"]  = false;
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
exit;