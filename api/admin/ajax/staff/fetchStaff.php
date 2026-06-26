<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_admin_users');

$staffRows = $model->query("
    SELECT
        a.id,
        a.fullname,
        a.staff_no,
        a.title,
        a.email,
        a.phone,
        a.ix_active,
        a.last_login,
        GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ', ') AS role_names,
        GROUP_CONCAT(DISTINCT r.slug ORDER BY r.slug SEPARATOR ',') AS role_slugs,
        aus.scope_type,
        i.name AS institution_name,
        p.name AS programme_name,
        d.name AS department_name,
        l.name AS level_name
    FROM admins a
    LEFT JOIN admin_user_roles aur ON aur.admin_id = a.id
    LEFT JOIN roles r ON r.id = aur.role_id
    LEFT JOIN admin_user_scope aus ON aus.admin_id = a.id
    LEFT JOIN institutions i ON i.id = aus.institution_id
    LEFT JOIN programmes p ON p.id = aus.programme_id
    LEFT JOIN department d ON d.id = aus.department_id
    LEFT JOIN levels l ON l.id = aus.level_id
    GROUP BY a.id
    ORDER BY a.id DESC
") ?: [];

$data = [];

foreach ($staffRows as $row) {
    $isActive = (int)($row['ix_active'] ?? 0) === 1;
    $roles = $row['role_names'] ?: ucfirst((string)($row['role'] ?? 'Unassigned'));
    $institution = $row['institution_name'] ?: (($row['scope_type'] ?? '') === 'global' ? 'All institutions' : 'Not assigned');
    $department = $row['department_name'] ?: (($row['scope_type'] ?? '') === 'global' ? 'All departments' : 'Not assigned');
    $statusBadge = $isActive
        ? '<span class="badge bg-success">Active</span>'
        : '<span class="badge bg-danger">Disabled</span>';

    $lastLogin = !empty($row['last_login'])
        ? date('d M Y, h:i A', strtotime($row['last_login']))
        : 'Never';

    $toggleLabel = $isActive ? 'Disable' : 'Activate';
    $toggleClass = $isActive ? 'btn-outline-danger' : 'btn-outline-success';

    $data[] = [
        'name' => htmlspecialchars(trim(($row['title'] ? $row['title'] . ' ' : '') . ($row['fullname'] ?? ''))),
        'email' => htmlspecialchars($row['email'] ?? ''),
        'phone' => htmlspecialchars($row['phone'] ?? ''),
        'roles' => htmlspecialchars($roles),
        'institution' => htmlspecialchars($institution),
        'department' => htmlspecialchars($department),
        'status' => $statusBadge,
        'last_login' => htmlspecialchars($lastLogin),
        'actions' => '
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-primary editStaff" data-id="' . (int)$row['id'] . '">
                    <i class="ph ph-pencil-simple"></i>
                </button>
                <button type="button" class="btn ' . $toggleClass . ' toggleStaff" data-id="' . (int)$row['id'] . '">
                    ' . $toggleLabel . '
                </button>
                <button type="button" class="btn btn-outline-secondary resetStaffPassword" data-id="' . (int)$row['id'] . '">
                    Reset
                </button>
                <button type="button" class="btn btn-outline-info viewStaffActivity" data-id="' . (int)$row['id'] . '">
                    Activity
                </button>
            </div>
        '
    ];
}

echo json_encode(['data' => $data]);
exit;
