<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requireAny(['moderate_results', 'approve_results']);

try {
    $departmentId = $rbac->requireDepartmentScope();
    $params = [];
    $where = "WHERE 1=1";

    if ($departmentId) {
        $where .= " AND ca.department_id = :department_id";
        $params['department_id'] = $departmentId;
    }

    $sheets = $model->query("
        SELECT
            rs.id,
            rs.ca_status,
            rs.exam_status,
            rs.moderation_status,
            rs.ca_submitted_at,
            rs.exam_submitted_at,
            c.course_code,
            c.course_title,
            a.fullname AS lecturer_name,
            lv.name AS level_name,
            acs.name AS session_name,
            sem.name AS semester_name,
            COUNT(DISTINCT rsc.student_id) AS scored_students,
            SUM(CASE WHEN COALESCE(rsc.grade_point, 0) > 0 THEN 1 ELSE 0 END) AS passed_students
        FROM result_sheets rs
        JOIN course_allocations ca ON ca.id = rs.course_allocation_id
        JOIN courses c ON c.id = ca.course_id
        JOIN lecturers l ON l.id = ca.lecturer_id
        JOIN admins a ON a.id = l.admin_id
        JOIN levels lv ON lv.id = ca.level_id
        JOIN academic_sessions acs ON acs.id = ca.academic_session_id
        JOIN semesters sem ON sem.id = ca.semester_id
        LEFT JOIN result_scores rsc ON rsc.result_sheet_id = rs.id
        {$where}
        GROUP BY rs.id
        ORDER BY rs.updated_at DESC
    ", $params) ?: [];

    $data = [];

    foreach ($sheets as $sheet) {
        $scored = (int)$sheet['scored_students'];
        $passed = (int)$sheet['passed_students'];
        $passRate = $scored > 0 ? round(($passed / $scored) * 100, 1) : 0;
        $statusClass = match ($sheet['moderation_status']) {
            'approved' => 'success',
            'rejected' => 'danger',
            'returned' => 'warning',
            'submitted' => 'info',
            default => 'secondary'
        };

        $data[] = [
            'course' => htmlspecialchars($sheet['course_code'] . ' - ' . $sheet['course_title']),
            'lecturer' => htmlspecialchars($sheet['lecturer_name']),
            'level' => htmlspecialchars($sheet['level_name']),
            'session' => htmlspecialchars($sheet['session_name']),
            'semester' => htmlspecialchars($sheet['semester_name']),
            'students' => $scored,
            'submitted' => 'CA: ' . htmlspecialchars($sheet['ca_status']) . '<br>Exam: ' . htmlspecialchars($sheet['exam_status']),
            'pass_rate' => $passRate . '%',
            'status' => '<span class="badge bg-' . $statusClass . '">' . ucfirst($sheet['moderation_status']) . '</span>',
            'actions' => '<button class="btn btn-primary btn-sm reviewResultSheet" data-id="' . (int)$sheet['id'] . '">Review</button>'
        ];
    }

    echo json_encode(['status' => true, 'data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage(), 'data' => []]);
}
exit;
