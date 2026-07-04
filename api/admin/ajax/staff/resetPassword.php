<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_admin_users');

function staff_reset_password_value(): string
{
    return 'Reset#' . random_int(100000, 999999);
}

$response = [
    'status' => false,
    'message' => 'Unable to reset password.',
    'csrf_token' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request.');
    }

    if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'staff_reset')) {
        throw new Exception('Invalid or expired request.');
    }

    $id = (int)($_POST['id'] ?? 0);

    if ($id < 1) {
        throw new Exception('Invalid staff user.');
    }

    $staff = $rbac->getAdmin($id);

    if (!$staff) {
        throw new Exception('Staff user not found.');
    }

    $newPassword = staff_reset_password_value();

    $model->update('admins', [
        'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        'is_default_password' => 1
    ], ['id' => $id]);

    $utility->resetLoginAttempts($staff['email'], true);

    $rbac->logAudit('Staff password reset', 'admin', (string)$id, null, [
        'email' => $staff['email']
    ]);

    $response['status'] = true;
    $response['message'] = 'Password reset successfully.';
    $response['temporary_password'] = $newPassword;
} catch (Throwable $e) {
    $response['message'] = $e->getMessage();
}

$response['csrf_token'] = $utility->generateCsrf('staff_reset');
echo json_encode($response);
exit;
