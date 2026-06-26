<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_admin_users');

$response = [
    'status' => false,
    'message' => 'Unable to update staff status.',
    'csrf_token' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request.');
    }

    if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'staff_toggle')) {
        throw new Exception('Invalid or expired request.');
    }

    $id = (int)($_POST['id'] ?? 0);

    if ($id < 1) {
        throw new Exception('Invalid staff user.');
    }

    if ($id === (int)$_SESSION['admin_id']) {
        throw new Exception('You cannot disable your own account.');
    }

    $staff = $rbac->getAdmin($id);

    if (!$staff) {
        throw new Exception('Staff user not found.');
    }

    $newStatus = (int)$staff['ix_active'] === 1 ? 0 : 1;

    $model->update('admins', ['ix_active' => $newStatus], ['id' => $id]);
    $rbac->logAudit('Staff status changed', 'admin', (string)$id, [
        'ix_active' => $staff['ix_active']
    ], [
        'ix_active' => $newStatus
    ]);

    $response['status'] = true;
    $response['message'] = $newStatus ? 'Staff account activated.' : 'Staff account disabled.';
} catch (Throwable $e) {
    $response['message'] = $e->getMessage();
}

$response['csrf_token'] = $utility->generateCsrf('staff_toggle');
echo json_encode($response);
exit;
