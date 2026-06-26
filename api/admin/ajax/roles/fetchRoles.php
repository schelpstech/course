<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_roles');

$roles = $model->query("
    SELECT
        r.*,
        COUNT(DISTINCT rp.permission_id) AS permission_count,
        COUNT(DISTINCT aur.admin_id) AS user_count
    FROM roles r
    LEFT JOIN role_permissions rp ON rp.role_id = r.id
    LEFT JOIN admin_user_roles aur ON aur.role_id = r.id
    GROUP BY r.id
    ORDER BY r.name ASC
") ?: [];

$data = [];

foreach ($roles as $role) {
    $isActive = (int)$role['status'] === 1;
    $status = $isActive
        ? '<span class="badge bg-success">Active</span>'
        : '<span class="badge bg-danger">Disabled</span>';
    $toggleClass = $isActive ? 'btn-outline-danger' : 'btn-outline-success';
    $toggleText = $isActive ? 'Disable' : 'Activate';
    $toggleButton = $role['slug'] === 'super'
        ? ''
        : '<button type="button" class="btn ' . $toggleClass . ' toggleRole" data-id="' . (int)$role['id'] . '">' . $toggleText . '</button>';

    $data[] = [
        'name' => htmlspecialchars($role['name']),
        'slug' => htmlspecialchars($role['slug']),
        'permission_count' => (int)$role['permission_count'],
        'user_count' => '<button type="button" class="btn btn-link p-0 viewRoleUsers" data-id="' . (int)$role['id'] . '">' . (int)$role['user_count'] . '</button>',
        'status' => $status,
        'actions' => '
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-primary editRole" data-id="' . (int)$role['id'] . '">
                    <i class="ph ph-pencil-simple"></i>
                </button>
                ' . $toggleButton . '
            </div>
        '
    ];
}

echo json_encode(['data' => $data]);
exit;
