<?php

class Rbac
{
    private PDO $db;
    private model $model;
    private array $tableCache = [];
    private array $columnCache = [];

    public function __construct(PDO $db, model $model)
    {
        $this->db = $db;
        $this->model = $model;
    }

    public function tableExists(string $table): bool
    {
        if (isset($this->tableCache[$table])) {
            return $this->tableCache[$table];
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = :table
        ");
        $stmt->execute(['table' => $table]);

        return $this->tableCache[$table] = ((int)$stmt->fetchColumn() > 0);
    }

    public function columnExists(string $table, string $column): bool
    {
        $cacheKey = $table . '.' . $column;

        if (isset($this->columnCache[$cacheKey])) {
            return $this->columnCache[$cacheKey];
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = :table
            AND COLUMN_NAME = :column
        ");
        $stmt->execute([
            'table' => $table,
            'column' => $column
        ]);

        return $this->columnCache[$cacheKey] = ((int)$stmt->fetchColumn() > 0);
    }

    public function isReady(): bool
    {
        foreach (['roles', 'permissions', 'role_permissions', 'admin_user_roles', 'admin_user_scope'] as $table) {
            if (!$this->tableExists($table)) {
                return false;
            }
        }

        return true;
    }

    public function getAdmin(int $adminId): ?array
    {
        $admin = $this->model->getRows('admins', [
            'where' => ['id' => $adminId],
            'return_type' => 'single'
        ]);

        return is_array($admin) ? $admin : null;
    }

    public function currentAdminId(): ?int
    {
        return !empty($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
    }

    public function roleSlugs(?int $adminId = null): array
    {
        $adminId = $adminId ?: $this->currentAdminId();

        if (!$adminId) {
            return [];
        }

        if (!$this->isReady()) {
            $admin = $this->getAdmin($adminId);
            return !empty($admin['role']) ? [$admin['role']] : [];
        }

        $rows = $this->model->query("
            SELECT r.slug
            FROM admin_user_roles aur
            JOIN roles r ON r.id = aur.role_id
            WHERE aur.admin_id = :admin_id
            AND r.status = 1
            ORDER BY r.name ASC
        ", ['admin_id' => $adminId]) ?: [];

        $slugs = array_values(array_filter(array_column($rows, 'slug')));

        if (empty($slugs)) {
            $admin = $this->getAdmin($adminId);
            if (!empty($admin['role'])) {
                $slugs[] = $admin['role'];
            }
        }

        return array_values(array_unique($slugs));
    }

    public function hasRole(string|array $roles, ?int $adminId = null): bool
    {
        $allowed = is_array($roles) ? $roles : [$roles];
        return count(array_intersect($allowed, $this->roleSlugs($adminId))) > 0;
    }

    public function primaryRole(?int $adminId = null): string
    {
        $roles = $this->roleSlugs($adminId);
        return $roles[0] ?? '';
    }

    public function can(string $permissionSlug, ?int $adminId = null): bool
    {
        $adminId = $adminId ?: $this->currentAdminId();

        if (!$adminId) {
            return false;
        }

        $roles = $this->roleSlugs($adminId);

        if (in_array('super', $roles, true)) {
            return true;
        }

        if (!$this->isReady()) {
            return false;
        }

        $row = $this->model->queryOne("
            SELECT 1
            FROM admin_user_roles aur
            JOIN roles r ON r.id = aur.role_id
            JOIN role_permissions rp ON rp.role_id = r.id
            JOIN permissions p ON p.id = rp.permission_id
            WHERE aur.admin_id = :admin_id
            AND r.status = 1
            AND p.slug = :permission
            LIMIT 1
        ", [
            'admin_id' => $adminId,
            'permission' => $permissionSlug
        ]);

        return (bool)$row;
    }

    public function canAny(array $permissionSlugs, ?int $adminId = null): bool
    {
        foreach ($permissionSlugs as $permissionSlug) {
            if ($this->can($permissionSlug, $adminId)) {
                return true;
            }
        }

        return false;
    }

    public function requirePermission(string $permissionSlug): void
    {
        if (!$this->can($permissionSlug)) {
            http_response_code(403);
            echo json_encode([
                'status' => false,
                'message' => 'You do not have permission to perform this action.'
            ]);
            exit;
        }
    }

    public function requireAny(array $permissionSlugs): void
    {
        if (!$this->canAny($permissionSlugs)) {
            http_response_code(403);
            echo json_encode([
                'status' => false,
                'message' => 'You do not have permission to perform this action.'
            ]);
            exit;
        }
    }

    public function getScope(?int $adminId = null): array
    {
        $adminId = $adminId ?: $this->currentAdminId();

        if (!$adminId) {
            return ['scope_type' => 'none'];
        }

        if (!$this->isReady()) {
            return $this->hasRole('super', $adminId)
                ? ['scope_type' => 'global']
                : ['scope_type' => 'limited'];
        }

        $scope = $this->model->queryOne("
            SELECT aus.*,
                i.name AS institution_name,
                p.name AS programme_name,
                d.name AS department_name,
                l.name AS level_name
            FROM admin_user_scope aus
            LEFT JOIN institutions i ON i.id = aus.institution_id
            LEFT JOIN programmes p ON p.id = aus.programme_id
            LEFT JOIN department d ON d.id = aus.department_id
            LEFT JOIN levels l ON l.id = aus.level_id
            WHERE aus.admin_id = :admin_id
            LIMIT 1
        ", ['admin_id' => $adminId]);

        if ($scope) {
            return $scope;
        }

        return $this->hasRole('super', $adminId)
            ? ['scope_type' => 'global']
            : ['scope_type' => 'limited'];
    }

    public function scopeLabel(array $scope): string
    {
        $type = $scope['scope_type'] ?? 'limited';

        if ($type === 'global') {
            return 'Global';
        }

        $labels = [];

        foreach (['institution_name', 'programme_name', 'department_name', 'level_name'] as $key) {
            if (!empty($scope[$key])) {
                $labels[] = $scope[$key];
            }
        }

        return !empty($labels) ? implode(' / ', $labels) : ucfirst($type);
    }

    public function departmentScopeId(?int $adminId = null): ?int
    {
        $adminId = $adminId ?: $this->currentAdminId();

        if (!$adminId) {
            return null;
        }

        if ($this->hasRole('super', $adminId)) {
            return null;
        }

        $scope = $this->getScope($adminId);
        $departmentId = (int)($scope['department_id'] ?? 0);

        return $departmentId > 0 ? $departmentId : null;
    }

    public function requireDepartmentScope(?int $departmentId = null): ?int
    {
        $adminId = $this->currentAdminId();

        if ($adminId && $this->hasRole('super', $adminId)) {
            return $departmentId && $departmentId > 0 ? $departmentId : null;
        }

        $scopeDepartmentId = $this->departmentScopeId($adminId);

        if (!$scopeDepartmentId) {
            throw new Exception('No department scope is assigned to this staff account.');
        }

        if ($departmentId !== null && $departmentId > 0 && $scopeDepartmentId !== $departmentId) {
            throw new Exception('You cannot access records outside your assigned department.');
        }

        return $scopeDepartmentId;
    }

    public function getRoles(bool $activeOnly = false): array
    {
        if (!$this->tableExists('roles')) {
            return [];
        }

        $where = $activeOnly ? 'WHERE status = 1' : '';
        return $this->model->query("
            SELECT *
            FROM roles
            {$where}
            ORDER BY name ASC
        ") ?: [];
    }

    public function getPermissionsGrouped(): array
    {
        if (!$this->tableExists('permissions')) {
            return [];
        }

        $permissions = $this->model->query("
            SELECT *
            FROM permissions
            ORDER BY module ASC, name ASC
        ") ?: [];

        $grouped = [];

        foreach ($permissions as $permission) {
            $module = $permission['module'] ?: 'General';
            $grouped[$module][] = $permission;
        }

        return $grouped;
    }

    public function logAudit(
        string $action,
        ?string $entityType = null,
        ?string $entityId = null,
        mixed $oldValue = null,
        mixed $newValue = null,
        ?int $adminId = null
    ): void {
        $adminId = $adminId ?: $this->currentAdminId();
        $admin = $adminId ? $this->getAdmin($adminId) : null;
        $email = $admin['email'] ?? ($_SESSION['admin_email'] ?? 'Unknown');

        $payload = [
            'user_id' => $email,
            'action' => $action,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];

        if ($this->columnExists('user_logs', 'admin_id')) {
            $payload['admin_id'] = $adminId;
        }

        if ($this->columnExists('user_logs', 'role_slug')) {
            $payload['role_slug'] = $this->primaryRole($adminId);
        }

        if ($this->columnExists('user_logs', 'entity_type')) {
            $payload['entity_type'] = $entityType;
        }

        if ($this->columnExists('user_logs', 'entity_id')) {
            $payload['entity_id'] = $entityId;
        }

        if ($this->columnExists('user_logs', 'old_value')) {
            $payload['old_value'] = $oldValue === null ? null : json_encode($oldValue);
        }

        if ($this->columnExists('user_logs', 'new_value')) {
            $payload['new_value'] = $newValue === null ? null : json_encode($newValue);
        }

        $this->model->insert_data('user_logs', $payload);
    }
}
