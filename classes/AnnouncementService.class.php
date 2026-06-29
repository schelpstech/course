<?php

class AnnouncementService
{
    private PDO $db;
    private model $model;
    private Rbac $rbac;
    private bool $schemaReady = false;

    public function __construct(PDO $db, model $model, Rbac $rbac)
    {
        $this->db = $db;
        $this->model = $model;
        $this->rbac = $rbac;
    }

    public function ensureSchema(): void
    {
        if ($this->schemaReady) {
            return;
        }

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS portal_announcements (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(180) NOT NULL,
                body TEXT NOT NULL,
                start_date DATETIME NOT NULL,
                end_date DATETIME NOT NULL,
                visibility ENUM('all','institution','programme','department','level') NOT NULL DEFAULT 'all',
                institution_id INT NULL,
                programme_id INT NULL,
                department_id INT NULL,
                level_id INT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                must_read TINYINT(1) NOT NULL DEFAULT 1,
                created_by INT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT NULL,
                INDEX idx_announcement_window (is_active, start_date, end_date),
                INDEX idx_announcement_visibility (visibility, institution_id, programme_id, department_id, level_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS portal_announcement_reads (
                id INT AUTO_INCREMENT PRIMARY KEY,
                announcement_id INT NOT NULL,
                student_id INT NOT NULL,
                read_at DATETIME NOT NULL,
                UNIQUE KEY uq_announcement_student (announcement_id, student_id),
                INDEX idx_student_reads (student_id, read_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->ensurePermission();
        $this->schemaReady = true;
    }

    private function ensurePermission(): void
    {
        if (!$this->rbac->tableExists('permissions')) {
            return;
        }

        $this->db->prepare("
            INSERT INTO permissions (name, slug, module, description)
            VALUES (:name, :slug, :module, :description)
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                module = VALUES(module),
                description = VALUES(description),
                updated_at = CURRENT_TIMESTAMP
        ")->execute([
            'name' => 'Manage Announcements',
            'slug' => 'manage_announcements',
            'module' => 'Communications',
            'description' => 'Create, edit and publish portal announcements.'
        ]);
    }

    public function adminList(): array
    {
        $this->ensureSchema();

        return $this->model->query("
            SELECT
                a.*,
                i.name AS institution_name,
                p.name AS programme_name,
                d.name AS department_name,
                l.name AS level_name,
                ad.fullname AS created_by_name,
                (
                    SELECT COUNT(*)
                    FROM portal_announcement_reads r
                    WHERE r.announcement_id = a.id
                ) AS read_count
            FROM portal_announcements a
            LEFT JOIN institutions i ON i.id = a.institution_id
            LEFT JOIN programmes p ON p.id = a.programme_id
            LEFT JOIN department d ON d.id = a.department_id
            LEFT JOIN levels l ON l.id = a.level_id
            LEFT JOIN admins ad ON ad.id = a.created_by
            ORDER BY a.created_at DESC, a.id DESC
        ") ?: [];
    }

    public function get(int $id): array
    {
        $this->ensureSchema();

        $announcement = $this->model->queryOne("
            SELECT *
            FROM portal_announcements
            WHERE id = :id
            LIMIT 1
        ", ['id' => $id]);

        if (!$announcement) {
            throw new Exception('Announcement not found.');
        }

        return $announcement;
    }

    public function save(array $input, int $adminId): int
    {
        $this->ensureSchema();

        $id = (int)($input['id'] ?? 0);
        $title = trim((string)($input['title'] ?? ''));
        $body = trim((string)($input['body'] ?? ''));
        $visibility = (string)($input['visibility'] ?? 'all');
        $isActive = (int)($input['is_active'] ?? 1) === 1 ? 1 : 0;
        $mustRead = isset($input['must_read']) ? 1 : 0;
        $startDate = $this->normalizeDate((string)($input['start_date'] ?? ''), 'Start date is required.');
        $endDate = $this->normalizeDate((string)($input['end_date'] ?? ''), 'End date is required.');

        if ($title === '') {
            throw new Exception('Title is required.');
        }

        if (strlen($title) > 180) {
            throw new Exception('Title cannot exceed 180 characters.');
        }

        if ($body === '') {
            throw new Exception('Notice content is required.');
        }

        if (strtotime($endDate) < strtotime($startDate)) {
            throw new Exception('End date must be after the start date.');
        }

        $target = $this->resolveTarget($visibility, $input);
        $payload = [
            'title' => $title,
            'body' => $body,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'visibility' => $visibility,
            'institution_id' => $target['institution_id'],
            'programme_id' => $target['programme_id'],
            'department_id' => $target['department_id'],
            'level_id' => $target['level_id'],
            'is_active' => $isActive,
            'must_read' => $mustRead,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $oldValue = null;

        if ($id > 0) {
            $oldValue = $this->get($id);
            $this->model->update('portal_announcements', $payload, ['id' => $id]);
            $announcementId = $id;
        } else {
            $payload['created_by'] = $adminId;
            $announcementId = (int)$this->model->insert_data('portal_announcements', $payload);
        }

        if ($announcementId < 1) {
            throw new Exception('Unable to save announcement.');
        }

        $this->rbac->logAudit(
            $id > 0 ? 'Announcement updated' : 'Announcement created',
            'portal_announcement',
            (string)$announcementId,
            $oldValue,
            $payload,
            $adminId
        );

        return $announcementId;
    }

    public function toggle(int $id, int $adminId): array
    {
        $this->ensureSchema();

        $announcement = $this->get($id);
        $newStatus = (int)$announcement['is_active'] === 1 ? 0 : 1;

        $this->model->update('portal_announcements', [
            'is_active' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);

        $this->rbac->logAudit(
            $newStatus ? 'Announcement enabled' : 'Announcement disabled',
            'portal_announcement',
            (string)$id,
            $announcement,
            ['is_active' => $newStatus],
            $adminId
        );

        return [
            'id' => $id,
            'is_active' => $newStatus
        ];
    }

    public function activeForStudent(int $studentUserId, ?int $announcementId = null): array
    {
        $this->ensureSchema();

        $student = $this->model->queryOne("
            SELECT *
            FROM students
            WHERE student_id = :student_id
            LIMIT 1
        ", ['student_id' => $studentUserId]);

        if (!$student) {
            return [];
        }

        $params = [
            'student_id' => $studentUserId,
            'institution_id' => (int)$student['institution_id'],
            'programme_id' => (int)$student['programme_id'],
            'department_id' => (int)$student['department_id'],
            'level_id' => (int)$student['level_id']
        ];

        $idFilter = '';

        if ($announcementId !== null) {
            $idFilter = 'AND a.id = :announcement_id';
            $params['announcement_id'] = $announcementId;
        }

        return $this->model->query("
            SELECT
                a.*,
                r.read_at
            FROM portal_announcements a
            LEFT JOIN portal_announcement_reads r
                ON r.announcement_id = a.id
                AND r.student_id = :student_id
            WHERE a.is_active = 1
            AND a.start_date <= CURRENT_TIMESTAMP
            AND a.end_date >= CURRENT_TIMESTAMP
            {$idFilter}
            AND (
                a.visibility = 'all'
                OR (a.visibility = 'institution' AND a.institution_id = :institution_id)
                OR (a.visibility = 'programme' AND a.programme_id = :programme_id)
                OR (a.visibility = 'department' AND a.department_id = :department_id)
                OR (a.visibility = 'level' AND a.level_id = :level_id)
            )
            ORDER BY a.must_read DESC, a.start_date DESC, a.id DESC
        ", $params) ?: [];
    }

    public function markRead(int $announcementId, int $studentUserId): void
    {
        $this->ensureSchema();

        $announcements = $this->activeForStudent($studentUserId, $announcementId);

        if (empty($announcements)) {
            throw new Exception('Announcement is not available for this student.');
        }

        $this->db->prepare("
            INSERT INTO portal_announcement_reads (announcement_id, student_id, read_at)
            VALUES (:announcement_id, :student_id, :read_at)
            ON DUPLICATE KEY UPDATE read_at = VALUES(read_at)
        ")->execute([
            'announcement_id' => $announcementId,
            'student_id' => $studentUserId,
            'read_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function targetLabel(array $announcement): string
    {
        return match ($announcement['visibility'] ?? 'all') {
            'institution' => $announcement['institution_name'] ?? 'Institution',
            'programme' => $announcement['programme_name'] ?? 'Programme',
            'department' => $announcement['department_name'] ?? 'Department',
            'level' => $announcement['level_name'] ?? 'Level',
            default => 'All students'
        };
    }

    private function normalizeDate(string $value, string $message): string
    {
        $value = trim(str_replace('T', ' ', $value));

        if ($value === '') {
            throw new Exception($message);
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            throw new Exception('Invalid date supplied.');
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function resolveTarget(string &$visibility, array $input): array
    {
        $allowed = ['all', 'institution', 'programme', 'department', 'level'];

        if (!in_array($visibility, $allowed, true)) {
            $visibility = 'all';
        }

        $target = [
            'institution_id' => null,
            'programme_id' => null,
            'department_id' => null,
            'level_id' => null
        ];

        if ($visibility === 'all') {
            return $target;
        }

        $institutionId = (int)($input['institution_id'] ?? 0);
        $programmeId = (int)($input['programme_id'] ?? 0);
        $departmentId = (int)($input['department_id'] ?? 0);
        $levelId = (int)($input['level_id'] ?? 0);

        if ($institutionId < 1) {
            throw new Exception('Institution is required for this visibility.');
        }

        if (!$this->exists('institutions', ['id' => $institutionId])) {
            throw new Exception('Selected institution was not found.');
        }

        $target['institution_id'] = $institutionId;

        if ($visibility === 'institution') {
            return $target;
        }

        if ($programmeId < 1) {
            throw new Exception('Programme is required for this visibility.');
        }

        if (!$this->exists('programmes', ['id' => $programmeId, 'institution_id' => $institutionId])) {
            throw new Exception('Selected programme does not belong to the institution.');
        }

        $target['programme_id'] = $programmeId;

        if ($visibility === 'programme') {
            return $target;
        }

        if ($departmentId < 1) {
            throw new Exception('Department is required for this visibility.');
        }

        if (!$this->exists('department', ['id' => $departmentId, 'programme_id' => $programmeId])) {
            throw new Exception('Selected department does not belong to the programme.');
        }

        $target['department_id'] = $departmentId;

        if ($visibility === 'department') {
            return $target;
        }

        if ($levelId < 1) {
            throw new Exception('Level is required for this visibility.');
        }

        if (!$this->exists('levels', ['id' => $levelId, 'department_id' => $departmentId])) {
            throw new Exception('Selected level does not belong to the department.');
        }

        $target['level_id'] = $levelId;

        return $target;
    }

    private function exists(string $table, array $where): bool
    {
        return $this->model->exists($table, $where);
    }
}
