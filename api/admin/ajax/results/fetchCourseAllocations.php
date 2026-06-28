<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requireAny(['allocate_courses', 'allocate_dept_courses']);

$data = [];

try {
    $departmentId = $rbac->requireDepartmentScope();
    $params = [];
    $where = '';

    if ($departmentId) {
        $where = 'WHERE ca.department_id = :department_id';
        $params['department_id'] = $departmentId;
    }

    $rows = $model->query("
        SELECT
            ca.id,
            ca.status,
            ca.allocated_at,
            s.name AS session_name,
            sem.name AS semester_name,
            c.course_code,
            c.course_title,
            d.name AS department_name,
            a.fullname AS lecturer_name,
            ld.name AS lecturer_department_name,
            li.name AS lecturer_institution_name
        FROM course_allocations ca
        JOIN academic_sessions s ON s.id = ca.academic_session_id
        JOIN semesters sem ON sem.id = ca.semester_id
        JOIN courses c ON c.id = ca.course_id
        JOIN department d ON d.id = ca.department_id
        JOIN lecturers l ON l.id = ca.lecturer_id
        JOIN admins a ON a.id = l.admin_id
        LEFT JOIN department ld ON ld.id = l.department_id
        LEFT JOIN programmes lp ON lp.id = ld.programme_id
        LEFT JOIN institutions li ON li.id = COALESCE(l.institution_id, lp.institution_id)
        {$where}
        ORDER BY ca.id DESC
    ", $params) ?: [];

    foreach ($rows as $row) {
        $isActive = $row['status'] === 'active';
        $status = $isActive
            ? '<span class="badge bg-success">Active</span>'
            : '<span class="badge bg-secondary">Inactive</span>';
        $actions = $isActive
            ? '
                <button type="button" class="btn btn-outline-danger btn-sm disableAllocation" data-id="' . (int)$row['id'] . '">
                    Disable
                </button>
            '
            : '
                <button type="button" class="btn btn-primary btn-sm editAllocation" data-id="' . (int)$row['id'] . '">
                    Reallocate
                </button>
            ';

        $lecturerLabel = $row['lecturer_name'];
        if (!empty($row['lecturer_department_name'])) {
            $lecturerLabel .= ' - ' . $row['lecturer_department_name'];
        }
        if (!empty($row['lecturer_institution_name'])) {
            $lecturerLabel .= ' (' . $row['lecturer_institution_name'] . ')';
        }

        $data[] = [
            'session' => htmlspecialchars($row['session_name']),
            'semester' => htmlspecialchars($row['semester_name']),
            'course' => htmlspecialchars($row['course_code'] . ' - ' . $row['course_title']),
            'department' => htmlspecialchars($row['department_name']),
            'lecturer' => htmlspecialchars($lecturerLabel),
            'status' => $status,
            'allocated_at' => date('d M Y, h:i A', strtotime($row['allocated_at'])),
            'actions' => $actions
        ];
    }
} catch (Throwable $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage(), 'data' => []]);
    exit;
}

echo json_encode(['status' => true, 'data' => $data]);
exit;
