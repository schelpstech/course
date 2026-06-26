<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_roles');

function role_slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '_', $value);
    return trim($value, '_');
}

$response = [
    'status' => false,
    'message' => 'Unable to save role.',
    'csrf_token' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request.');
    }

    if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'role_save')) {
        throw new Exception('Invalid or expired request.');
    }

    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $slug = role_slugify($_POST['slug'] ?? $name);
    $description = trim($_POST['description'] ?? '');
    $status = (int)($_POST['status'] ?? 1) === 1 ? 1 : 0;
    $permissionIds = $_POST['permission_ids'] ?? [];

    if ($name === '') {
        throw new Exception('Role name is required.');
    }

    if ($slug === '') {
        throw new Exception('Role slug is required.');
    }

    if (!is_array($permissionIds)) {
        $permissionIds = [];
    }

    $permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));

    $existing = $model->queryOne("
        SELECT *
        FROM roles
        WHERE id = :id
        LIMIT 1
    ", ['id' => $id]);

    if ($existing && $existing['slug'] === 'super') {
        $slug = 'super';
        $status = 1;
    }

    $duplicate = $model->queryOne("
        SELECT id
        FROM roles
        WHERE slug = :slug
        AND id <> :id
        LIMIT 1
    ", [
        'slug' => $slug,
        'id' => $id
    ]);

    if ($duplicate) {
        throw new Exception('Another role already uses this slug.');
    }

    if (!empty($permissionIds)) {
        $permissionPlaceholders = implode(',', array_fill(0, count($permissionIds), '?'));
        $permissionStmt = $db->prepare("
            SELECT id
            FROM permissions
            WHERE id IN ({$permissionPlaceholders})
        ");
        $permissionStmt->execute($permissionIds);

        if (count($permissionStmt->fetchAll(PDO::FETCH_ASSOC)) !== count($permissionIds)) {
            throw new Exception('One or more permissions are invalid.');
        }
    }

    $oldValue = $existing ? [
        'role' => $existing,
        'permissions' => $model->query("
            SELECT p.slug
            FROM role_permissions rp
            JOIN permissions p ON p.id = rp.permission_id
            WHERE rp.role_id = :id
        ", ['id' => $id])
    ] : null;

    $model->beginTransaction();

    if ($id > 0) {
        $model->update('roles', [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'status' => $status
        ], ['id' => $id]);
        $roleId = $id;
    } else {
        $roleId = (int)$model->insert_data('roles', [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'status' => $status
        ]);
    }

    if (!$roleId) {
        throw new Exception('Unable to save role.');
    }

    if ($slug === 'super') {
        $permissionIds = array_map('intval', array_column($model->query("SELECT id FROM permissions") ?: [], 'id'));
    }

    $model->delete('role_permissions', ['role_id' => $roleId]);

    foreach ($permissionIds as $permissionId) {
        $model->insert_data('role_permissions', [
            'role_id' => $roleId,
            'permission_id' => $permissionId
        ]);
    }

    $rbac->logAudit($id ? 'Role updated' : 'Role created', 'role', (string)$roleId, $oldValue, [
        'role' => [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'status' => $status
        ],
        'permission_ids' => $permissionIds
    ]);

    $model->commit();

    $response['status'] = true;
    $response['message'] = $id ? 'Role updated successfully.' : 'Role created successfully.';
} catch (Throwable $e) {
    $model->rollBack();
    $response['message'] = $e->getMessage();
}

$response['csrf_token'] = $utility->generateCsrf('role_save');
echo json_encode($response);
exit;
