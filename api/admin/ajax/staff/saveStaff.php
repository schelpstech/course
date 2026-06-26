<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_admin_users');

function staff_legacy_role(array $roleSlugs): ?string
{
    foreach ($roleSlugs as $slug) {
        if (in_array($slug, ['super', 'registry', 'log', 'bursary', 'admission'], true)) {
            return $slug;
        }
    }

    return null;
}

function staff_temp_password(): string
{
    return 'Staff#' . random_int(100000, 999999);
}

$response = [
    'status' => false,
    'message' => 'Unable to save staff user.',
    'csrf_token' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request.');
    }

    if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'staff_save')) {
        throw new Exception('Invalid or expired request.');
    }

    $id = (int)($_POST['id'] ?? 0);
    $fullname = trim($_POST['fullname'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $staffNo = trim($_POST['staff_no'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $roleIds = $_POST['role_ids'] ?? [];
    $scopeType = trim($_POST['scope_type'] ?? 'global');
    $institutionId = (int)($_POST['institution_id'] ?? 0);
    $programmeId = (int)($_POST['programme_id'] ?? 0);
    $departmentId = (int)($_POST['department_id'] ?? 0);
    $levelId = (int)($_POST['level_id'] ?? 0);

    if ($fullname === '' || $email === '') {
        throw new Exception('Full name and email are required.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Enter a valid email address.');
    }

    if (!is_array($roleIds) || empty($roleIds)) {
        throw new Exception('Assign at least one role.');
    }

    $roleIds = array_values(array_unique(array_map('intval', $roleIds)));
    $rolePlaceholders = implode(',', array_fill(0, count($roleIds), '?'));
    $roleStmt = $db->prepare("
        SELECT id, slug
        FROM roles
        WHERE id IN ({$rolePlaceholders})
        AND status = 1
    ");
    $roleStmt->execute($roleIds);
    $selectedRoles = $roleStmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($selectedRoles) !== count($roleIds)) {
        throw new Exception('One or more selected roles are invalid.');
    }

    $roleSlugs = array_column($selectedRoles, 'slug');

    if ($id === (int)$_SESSION['admin_id'] && !in_array('super', $roleSlugs, true) && $rbac->hasRole('super', $id)) {
        throw new Exception('You cannot remove your own Super Admin role.');
    }

    if (!in_array($scopeType, ['global', 'institution', 'programme', 'department', 'level', 'lecturer'], true)) {
        throw new Exception('Invalid scope type.');
    }

    if ($scopeType !== 'global' && $institutionId < 1) {
        throw new Exception('Institution scope is required.');
    }

    if (in_array($scopeType, ['programme', 'department', 'level'], true) && $programmeId < 1) {
        throw new Exception('Programme scope is required.');
    }

    if (in_array($scopeType, ['department', 'level', 'lecturer'], true) && $departmentId < 1) {
        throw new Exception('Department scope is required.');
    }

    if ($scopeType === 'level' && $levelId < 1) {
        throw new Exception('Level scope is required.');
    }

    $duplicate = $model->queryOne("
        SELECT id
        FROM admins
        WHERE email = :email
        AND id <> :id
        LIMIT 1
    ", [
        'email' => $email,
        'id' => $id
    ]);

    if ($duplicate) {
        throw new Exception('A staff user with this email already exists.');
    }

    $oldValue = $id ? [
        'admin' => $rbac->getAdmin($id),
        'roles' => $rbac->roleSlugs($id),
        'scope' => $rbac->getScope($id)
    ] : null;

    $generatedPassword = null;

    $model->beginTransaction();

    $legacyRole = staff_legacy_role($roleSlugs);
    $staffData = [
        'fullname' => $fullname,
        'email' => $email,
        'role' => $legacyRole
    ];

    foreach ([
        'phone' => $phone,
        'staff_no' => $staffNo,
        'title' => $title
    ] as $column => $value) {
        if ($rbac->columnExists('admins', $column)) {
            $staffData[$column] = $value !== '' ? $value : null;
        }
    }

    if ($id > 0) {
        if ($password !== '') {
            if (strlen($password) < 6) {
                throw new Exception('Password must be at least 6 characters.');
            }

            $staffData['password'] = password_hash($password, PASSWORD_DEFAULT);
            $staffData['is_default_password'] = 0;
        }

        $model->update('admins', $staffData, ['id' => $id]);
        $adminId = $id;
    } else {
        if ($password === '') {
            $generatedPassword = staff_temp_password();
            $password = $generatedPassword;
        }

        if (strlen($password) < 6) {
            throw new Exception('Password must be at least 6 characters.');
        }

        $staffData['password'] = password_hash($password, PASSWORD_DEFAULT);
        $staffData['is_default_password'] = 1;
        $staffData['ix_active'] = 1;
        $adminId = (int)$model->insert_data('admins', $staffData);

        if (!$adminId) {
            throw new Exception('Unable to create staff user.');
        }
    }

    $model->delete('admin_user_roles', ['admin_id' => $adminId]);

    foreach ($roleIds as $roleId) {
        $model->insert_data('admin_user_roles', [
            'admin_id' => $adminId,
            'role_id' => $roleId,
            'assigned_by' => $_SESSION['admin_id'] ?? null
        ]);
    }

    $scopeSql = "
        INSERT INTO admin_user_scope (
            admin_id,
            institution_id,
            programme_id,
            department_id,
            level_id,
            scope_type
        ) VALUES (
            :admin_id,
            :institution_id,
            :programme_id,
            :department_id,
            :level_id,
            :scope_type
        )
        ON DUPLICATE KEY UPDATE
            institution_id = VALUES(institution_id),
            programme_id = VALUES(programme_id),
            department_id = VALUES(department_id),
            level_id = VALUES(level_id),
            scope_type = VALUES(scope_type),
            updated_at = CURRENT_TIMESTAMP
    ";

    $scopeStmt = $db->prepare($scopeSql);
    $scopeStmt->execute([
        'admin_id' => $adminId,
        'institution_id' => $scopeType === 'global' ? null : $institutionId,
        'programme_id' => in_array($scopeType, ['programme', 'department', 'level'], true) ? $programmeId : null,
        'department_id' => in_array($scopeType, ['department', 'level', 'lecturer'], true) ? $departmentId : null,
        'level_id' => $scopeType === 'level' ? $levelId : null,
        'scope_type' => $scopeType
    ]);

    if (in_array('lecturer', $roleSlugs, true)) {
        $lecturerStmt = $db->prepare("
            INSERT INTO lecturers (
                admin_id,
                institution_id,
                department_id,
                staff_no,
                title,
                phone,
                email,
                status
            ) VALUES (
                :admin_id,
                :institution_id,
                :department_id,
                :staff_no,
                :title,
                :phone,
                :email,
                1
            )
            ON DUPLICATE KEY UPDATE
                institution_id = VALUES(institution_id),
                department_id = VALUES(department_id),
                staff_no = VALUES(staff_no),
                title = VALUES(title),
                phone = VALUES(phone),
                email = VALUES(email),
                status = 1,
                updated_at = CURRENT_TIMESTAMP
        ");
        $lecturerStmt->execute([
            'admin_id' => $adminId,
            'institution_id' => $institutionId ?: null,
            'department_id' => $departmentId ?: null,
            'staff_no' => $staffNo !== '' ? $staffNo : null,
            'title' => $title !== '' ? $title : null,
            'phone' => $phone !== '' ? $phone : null,
            'email' => $email
        ]);
    } else {
        $model->update('lecturers', ['status' => 0], ['admin_id' => $adminId]);
    }

    $newValue = [
        'admin' => $rbac->getAdmin($adminId),
        'roles' => $roleSlugs,
        'scope' => [
            'scope_type' => $scopeType,
            'institution_id' => $institutionId ?: null,
            'programme_id' => $programmeId ?: null,
            'department_id' => $departmentId ?: null,
            'level_id' => $levelId ?: null
        ]
    ];

    $rbac->logAudit(
        $id ? 'Staff user updated' : 'Staff user created',
        'admin',
        (string)$adminId,
        $oldValue,
        $newValue
    );

    $model->commit();

    $response['status'] = true;
    $response['message'] = $id ? 'Staff user updated successfully.' : 'Staff user created successfully.';
    $response['generated_password'] = $generatedPassword;
} catch (Throwable $e) {
    $model->rollBack();
    $response['message'] = $e->getMessage();
}

$response['csrf_token'] = $utility->generateCsrf('staff_save');
echo json_encode($response);
exit;
