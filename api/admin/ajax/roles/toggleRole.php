<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_roles');

$response = [
    'status' => false,
    'message' => 'Unable to update role.',
    'csrf_token' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request.');
    }

    if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'role_toggle')) {
        throw new Exception('Invalid or expired request.');
    }

    $id = (int)($_POST['id'] ?? 0);
    $role = $model->queryOne("
        SELECT *
        FROM roles
        WHERE id = :id
        LIMIT 1
    ", ['id' => $id]);

    if (!$role) {
        throw new Exception('Role not found.');
    }

    if ($role['slug'] === 'super') {
        throw new Exception('Super Admin cannot be disabled.');
    }

    $newStatus = (int)$role['status'] === 1 ? 0 : 1;
    $model->update('roles', ['status' => $newStatus], ['id' => $id]);
    $rbac->logAudit('Role status changed', 'role', (string)$id, [
        'status' => $role['status']
    ], [
        'status' => $newStatus
    ]);

    $response['status'] = true;
    $response['message'] = $newStatus ? 'Role activated.' : 'Role disabled.';
} catch (Throwable $e) {
    $response['message'] = $e->getMessage();
}

$response['csrf_token'] = $utility->generateCsrf('role_toggle');
echo json_encode($response);
exit;
