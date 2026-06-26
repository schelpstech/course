<?php

class ResultService
{
    private PDO $db;
    private model $model;
    private Rbac $rbac;

    public function __construct(PDO $db, model $model, Rbac $rbac)
    {
        $this->db = $db;
        $this->model = $model;
        $this->rbac = $rbac;
    }

    public function currentLecturer(?int $adminId = null): ?array
    {
        $adminId = $adminId ?: (int)($_SESSION['admin_id'] ?? 0);

        if ($adminId < 1) {
            return null;
        }

        $lecturer = $this->model->queryOne("
            SELECT l.*, a.fullname, a.email AS admin_email
            FROM lecturers l
            JOIN admins a ON a.id = l.admin_id
            WHERE l.admin_id = :admin_id
            AND l.status = 1
            LIMIT 1
        ", ['admin_id' => $adminId]);

        return $lecturer ?: null;
    }

    public function allocationDetails(int $allocationId): ?array
    {
        $allocation = $this->model->queryOne("
            SELECT
                ca.*,
                c.course_code,
                c.course_title,
                c.unit,
                c.course_type,
                i.name AS institution_name,
                i.inst_logo,
                p.name AS programme_name,
                d.name AS department_name,
                d.code AS department_code,
                lv.name AS level_name,
                lv.code AS level_code,
                s.name AS session_name,
                sem.name AS semester_name,
                l.admin_id AS lecturer_admin_id,
                a.fullname AS lecturer_name,
                a.email AS lecturer_email
            FROM course_allocations ca
            JOIN courses c ON c.id = ca.course_id
            JOIN institutions i ON i.id = ca.institution_id
            JOIN levels lv ON lv.id = ca.level_id
            JOIN department d ON d.id = ca.department_id
            JOIN programmes p ON p.id = d.programme_id
            JOIN academic_sessions s ON s.id = ca.academic_session_id
            JOIN semesters sem ON sem.id = ca.semester_id
            JOIN lecturers l ON l.id = ca.lecturer_id
            JOIN admins a ON a.id = l.admin_id
            WHERE ca.id = :allocation_id
            LIMIT 1
        ", ['allocation_id' => $allocationId]);

        return $allocation ?: null;
    }

    public function assertLecturerAllocationAccess(int $allocationId, array $permissions): array
    {
        $adminId = (int)($_SESSION['admin_id'] ?? 0);
        $allocation = $this->allocationDetails($allocationId);

        if (!$allocation) {
            throw new Exception('Course allocation not found.');
        }

        if (!$this->rbac->canAny($permissions, $adminId)) {
            throw new Exception('You do not have permission to access this scoresheet.');
        }

        if ($this->rbac->hasRole('super', $adminId)) {
            return $allocation;
        }

        if ((int)$allocation['lecturer_admin_id'] !== $adminId) {
            throw new Exception('This course is not allocated to your lecturer account.');
        }

        if ($allocation['status'] !== 'active') {
            throw new Exception('This course allocation is not active.');
        }

        return $allocation;
    }

    public function activeConfig(int $sessionId, int $semesterId): ?array
    {
        $config = $this->model->queryOne("
            SELECT *
            FROM result_config
            WHERE academic_session_id = :session_id
            AND semester_id = :semester_id
            AND status = 'active'
            LIMIT 1
        ", [
            'session_id' => $sessionId,
            'semester_id' => $semesterId
        ]);

        return $config ?: null;
    }

    public function ensureSheet(array $allocation): array
    {
        $config = $this->activeConfig((int)$allocation['academic_session_id'], (int)$allocation['semester_id']);

        if (!$config) {
            throw new Exception('No active result configuration exists for this session and semester.');
        }

        $sheet = $this->model->queryOne("
            SELECT *
            FROM result_sheets
            WHERE course_allocation_id = :allocation_id
            AND result_config_id = :config_id
            LIMIT 1
        ", [
            'allocation_id' => $allocation['id'],
            'config_id' => $config['id']
        ]);

        if (!$sheet) {
            $sheetId = (int)$this->model->insert_data('result_sheets', [
                'course_allocation_id' => $allocation['id'],
                'result_config_id' => $config['id'],
                'ca_status' => 'draft',
                'exam_status' => 'draft',
                'moderation_status' => 'pending'
            ]);

            $sheet = $this->model->queryOne("
                SELECT *
                FROM result_sheets
                WHERE id = :id
                LIMIT 1
            ", ['id' => $sheetId]);
        }

        $sheet['config'] = $config;
        return $sheet;
    }

    public function registeredStudents(array $allocation): array
    {
        return $this->model->query("
            SELECT
                s.id AS student_record_id,
                s.student_id AS user_id,
                s.matric_no,
                s.first_name,
                s.other_name,
                s.last_name,
                s.gender,
                s.passport,
                p.name AS programme_name,
                d.name AS department_name,
                lv.name AS level_name,
                cr.approval_status,
                cr.course_regID
            FROM registered_course rc
            JOIN course_registered cr ON cr.course_regID = rc.course_regID
            JOIN students s ON s.student_id = cr.student_id
            JOIN programmes p ON p.id = s.programme_id
            JOIN department d ON d.id = s.department_id
            JOIN levels lv ON lv.id = s.level_id
            WHERE rc.course_id = :course_id
            AND cr.session = :session_id
            AND cr.semester = :semester_id
            AND cr.approval_status IN ('submitted', 'approved')
            ORDER BY s.matric_no ASC
        ", [
            'course_id' => $allocation['course_id'],
            'session_id' => $allocation['academic_session_id'],
            'semester_id' => $allocation['semester_id']
        ]) ?: [];
    }

    public function scoresheetRows(int $allocationId): array
    {
        $allocation = $this->allocationDetails($allocationId);

        if (!$allocation) {
            throw new Exception('Course allocation not found.');
        }

        $sheet = $this->ensureSheet($allocation);
        $students = $this->registeredStudents($allocation);

        $scores = $this->model->query("
            SELECT *
            FROM result_scores
            WHERE result_sheet_id = :sheet_id
        ", ['sheet_id' => $sheet['id']]) ?: [];

        $scoreMap = [];

        foreach ($scores as $score) {
            $scoreMap[(int)$score['student_id']] = $score;
        }

        foreach ($students as &$student) {
            $studentId = (int)$student['student_record_id'];
            $student['score'] = $scoreMap[$studentId] ?? null;
        }

        return [
            'allocation' => $allocation,
            'sheet' => $sheet,
            'students' => $students
        ];
    }

    public function gradeFor(int $institutionId, float $totalScore): ?array
    {
        $grade = $this->model->queryOne("
            SELECT *
            FROM grading_rules
            WHERE institution_id = :institution_id
            AND status = 1
            AND :score BETWEEN min_score AND max_score
            ORDER BY min_score DESC
            LIMIT 1
        ", [
            'institution_id' => $institutionId,
            'score' => $totalScore
        ]);

        return $grade ?: null;
    }

    public function saveScores(int $allocationId, string $component, array $entries, bool $submit = false): array
    {
        $component = strtolower($component);

        if (!in_array($component, ['ca', 'exam'], true)) {
            throw new Exception('Invalid score component.');
        }

        $permissions = $component === 'ca'
            ? ['enter_ca_scores', 'submit_scores']
            : ['enter_exam_scores', 'submit_scores'];
        $allocation = $this->assertLecturerAllocationAccess($allocationId, $permissions);

        if ($submit && !$this->rbac->can('submit_scores')) {
            throw new Exception('You do not have permission to submit final scores.');
        }

        $sheet = $this->ensureSheet($allocation);
        $config = $sheet['config'];

        $statusColumn = $component . '_status';
        $scoreColumn = $component . '_score';
        $submittedColumn = $component . '_submitted';
        $maxScore = (float)$config[$component . '_max_score'];

        if ((int)$config[$component . '_entry_enabled'] !== 1) {
            throw new Exception(strtoupper($component) . ' entry is disabled for this semester.');
        }

        if (!empty($config['submission_deadline'])) {
            $deadline = strtotime($config['submission_deadline']) + ((int)$config['grace_period'] * 60);

            if (time() > $deadline) {
                throw new Exception('The submission deadline has passed.');
            }
        }

        if (in_array($sheet[$statusColumn], ['submitted', 'approved'], true)) {
            throw new Exception(strtoupper($component) . ' scores have already been submitted.');
        }

        if ($sheet[$statusColumn] === 'returned' && (int)$config['editing_enabled'] !== 1) {
            throw new Exception(strtoupper($component) . ' editing is currently disabled.');
        }

        $registeredStudents = $this->registeredStudents($allocation);
        $allowedStudentIds = array_map('intval', array_column($registeredStudents, 'student_record_id'));
        $allowedMap = array_fill_keys($allowedStudentIds, true);

        $existingRows = $this->model->query("
            SELECT *
            FROM result_scores
            WHERE result_sheet_id = :sheet_id
        ", ['sheet_id' => $sheet['id']]) ?: [];

        $existingMap = [];
        foreach ($existingRows as $row) {
            $existingMap[(int)$row['student_id']] = $row;
        }

        $normalized = [];

        foreach ($entries as $studentId => $score) {
            $studentId = (int)$studentId;

            if (!isset($allowedMap[$studentId])) {
                throw new Exception('Invalid student found in score submission.');
            }

            if ($score === '' || $score === null) {
                if ($submit) {
                    throw new Exception('All registered students must have a score before final submission.');
                }
                continue;
            }

            if (!is_numeric($score)) {
                throw new Exception('Scores must be numeric.');
            }

            $score = (float)$score;

            if ($score < 0 || $score > $maxScore) {
                throw new Exception(strtoupper($component) . " score must be between 0 and {$maxScore}.");
            }

            $normalized[$studentId] = $score;
        }

        if ($submit) {
            foreach ($allowedStudentIds as $studentId) {
                $existingScore = $existingMap[$studentId][$scoreColumn] ?? null;

                if (!array_key_exists($studentId, $normalized) && ($existingScore === null || $existingScore === '')) {
                    throw new Exception('All registered students must have a score before final submission.');
                }
            }
        }

        $this->model->beginTransaction();

        try {
            foreach ($allowedStudentIds as $studentId) {
                if (!array_key_exists($studentId, $normalized)) {
                    continue;
                }

                $existing = $existingMap[$studentId] ?? [];
                $caScore = $component === 'ca' ? $normalized[$studentId] : ($existing['ca_score'] ?? null);
                $examScore = $component === 'exam' ? $normalized[$studentId] : ($existing['exam_score'] ?? null);
                $totalScore = ($caScore !== null && $examScore !== null) ? ((float)$caScore + (float)$examScore) : null;
                $grade = $totalScore !== null ? $this->gradeFor((int)$allocation['institution_id'], (float)$totalScore) : null;

                $this->db->prepare("
                    INSERT INTO result_scores (
                        result_sheet_id,
                        student_id,
                        course_id,
                        ca_score,
                        exam_score,
                        total_score,
                        letter_grade,
                        grade_point,
                        remark,
                        ca_submitted,
                        exam_submitted,
                        last_edited_by
                    ) VALUES (
                        :result_sheet_id,
                        :student_id,
                        :course_id,
                        :ca_score,
                        :exam_score,
                        :total_score,
                        :letter_grade,
                        :grade_point,
                        :remark,
                        :ca_submitted,
                        :exam_submitted,
                        :last_edited_by
                    )
                    ON DUPLICATE KEY UPDATE
                        ca_score = VALUES(ca_score),
                        exam_score = VALUES(exam_score),
                        total_score = VALUES(total_score),
                        letter_grade = VALUES(letter_grade),
                        grade_point = VALUES(grade_point),
                        remark = VALUES(remark),
                        ca_submitted = GREATEST(ca_submitted, VALUES(ca_submitted)),
                        exam_submitted = GREATEST(exam_submitted, VALUES(exam_submitted)),
                        last_edited_by = VALUES(last_edited_by),
                        updated_at = CURRENT_TIMESTAMP
                ")->execute([
                    'result_sheet_id' => $sheet['id'],
                    'student_id' => $studentId,
                    'course_id' => $allocation['course_id'],
                    'ca_score' => $caScore,
                    'exam_score' => $examScore,
                    'total_score' => $totalScore,
                    'letter_grade' => $grade['letter_grade'] ?? null,
                    'grade_point' => $grade['grade_point'] ?? null,
                    'remark' => $grade['remark'] ?? null,
                    'ca_submitted' => $submit && $component === 'ca' ? 1 : (int)($existing['ca_submitted'] ?? 0),
                    'exam_submitted' => $submit && $component === 'exam' ? 1 : (int)($existing['exam_submitted'] ?? 0),
                    'last_edited_by' => $_SESSION['admin_id'] ?? null
                ]);
            }

            $sheetUpdate = [];

            if ($submit) {
                $sheetUpdate[$statusColumn] = 'submitted';
                $sheetUpdate[$component . '_submitted_at'] = date('Y-m-d H:i:s');
                $sheetUpdate['submitted_by'] = $_SESSION['admin_id'] ?? null;

                $otherStatus = $component === 'ca' ? $sheet['exam_status'] : $sheet['ca_status'];
                if (in_array($otherStatus, ['submitted', 'approved'], true)) {
                    $sheetUpdate['moderation_status'] = 'submitted';
                }
            } elseif ($sheet[$statusColumn] === 'draft') {
                $sheetUpdate[$statusColumn] = 'draft';
            }

            if (!empty($sheetUpdate)) {
                $this->model->update('result_sheets', $sheetUpdate, ['id' => $sheet['id']]);
            }

            $this->rbac->logAudit(
                $submit ? strtoupper($component) . ' score submitted' : strtoupper($component) . ' score draft saved',
                'result_sheet',
                (string)$sheet['id'],
                null,
                [
                    'allocation_id' => $allocationId,
                    'component' => $component,
                    'submitted' => $submit,
                    'entries' => count($normalized)
                ]
            );

            $this->model->commit();
        } catch (Throwable $e) {
            $this->model->rollBack();
            throw $e;
        }

        return $this->scoresheetRows($allocationId);
    }
}
