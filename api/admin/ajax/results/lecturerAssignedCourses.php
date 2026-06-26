<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requireAny(['view_results', 'enter_ca_scores', 'enter_exam_scores', 'submit_scores']);

$adminId = (int)($_SESSION['admin_id'] ?? 0);
$isSuper = $rbac->hasRole('super', $adminId);

$params = [];
$where = "WHERE ca.status = 'active'";

if (!$isSuper) {
    $where .= " AND l.admin_id = :admin_id";
    $params['admin_id'] = $adminId;
}

$rows = $model->query("
    SELECT
        ca.id,
        ca.course_id,
        c.course_code,
        c.course_title,
        d.name AS department_name,
        lv.name AS level_name,
        s.name AS session_name,
        sem.name AS semester_name,
        COALESCE(rs.ca_status, 'draft') AS ca_status,
        COALESCE(rs.exam_status, 'draft') AS exam_status,
        COALESCE(rs.moderation_status, 'pending') AS moderation_status,
        (
            SELECT COUNT(DISTINCT st.id)
            FROM registered_course rc
            JOIN course_registered cr ON cr.course_regID = rc.course_regID
            JOIN students st ON st.student_id = cr.student_id
            WHERE rc.course_id = ca.course_id
            AND cr.session = ca.academic_session_id
            AND cr.semester = ca.semester_id
            AND cr.approval_status IN ('submitted', 'approved')
        ) AS registered_students
    FROM course_allocations ca
    JOIN courses c ON c.id = ca.course_id
    JOIN department d ON d.id = ca.department_id
    JOIN levels lv ON lv.id = ca.level_id
    JOIN academic_sessions s ON s.id = ca.academic_session_id
    JOIN semesters sem ON sem.id = ca.semester_id
    JOIN lecturers l ON l.id = ca.lecturer_id
    LEFT JOIN result_config rcfg
        ON rcfg.academic_session_id = ca.academic_session_id
        AND rcfg.semester_id = ca.semester_id
        AND rcfg.status = 'active'
    LEFT JOIN result_sheets rs
        ON rs.course_allocation_id = ca.id
        AND rs.result_config_id = rcfg.id
    {$where}
    ORDER BY s.id DESC, sem.id DESC, c.course_code ASC
", $params) ?: [];

$data = [];
$options = [];

foreach ($rows as $row) {
    $label = $row['course_code'] . ' - ' . $row['course_title'] . ' (' . $row['session_name'] . ', ' . $row['semester_name'] . ')';
    $options[] = [
        'id' => (int)$row['id'],
        'name' => $label
    ];

    $data[] = [
        'course' => htmlspecialchars($row['course_code'] . ' - ' . $row['course_title']),
        'department' => htmlspecialchars($row['department_name']),
        'level' => htmlspecialchars($row['level_name']),
        'session' => htmlspecialchars($row['session_name']),
        'semester' => htmlspecialchars($row['semester_name']),
        'students' => (int)$row['registered_students'],
        'ca_status' => '<span class="badge bg-secondary">' . ucfirst($row['ca_status']) . '</span>',
        'exam_status' => '<span class="badge bg-secondary">' . ucfirst($row['exam_status']) . '</span>',
        'moderation_status' => '<span class="badge bg-info">' . ucfirst($row['moderation_status']) . '</span>',
        'actions' => '<button type="button" class="btn btn-primary btn-sm openScoresheet" data-id="' . (int)$row['id'] . '">Open</button>'
    ];
}

echo json_encode([
    'status' => true,
    'data' => $data,
    'options' => $options
]);
exit;
