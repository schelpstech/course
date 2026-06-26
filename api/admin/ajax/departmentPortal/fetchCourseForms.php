<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('view_course_forms');

try {
    $departmentId = $rbac->requireDepartmentScope();
    $sessionId = (int)($_GET['session_id'] ?? 0);
    $semesterId = (int)($_GET['semester_id'] ?? 0);
    $params = [];
    $where = "WHERE 1=1";

    if ($departmentId) {
        $where .= " AND s.department_id = :department_id";
        $params['department_id'] = $departmentId;
    }

    if ($sessionId) {
        $where .= " AND cr.session = :session_id";
        $params['session_id'] = $sessionId;
    }

    if ($semesterId) {
        $where .= " AND cr.semester = :semester_id";
        $params['semester_id'] = $semesterId;
    }

    $forms = $model->query("
        SELECT
            cr.course_regID,
            cr.approval_status,
            cr.total_units,
            cr.created_at,
            s.student_id,
            s.first_name,
            s.other_name,
            s.last_name,
            s.matric_no,
            lv.name AS level_name,
            d.name AS department_name,
            COUNT(rc.id) AS courses_count
        FROM course_registered cr
        JOIN students s ON s.student_id = cr.student_id
        JOIN department d ON d.id = s.department_id
        JOIN levels lv ON lv.id = s.level_id
        LEFT JOIN registered_course rc ON rc.course_regID = cr.course_regID
        {$where}
        GROUP BY cr.course_regID
        ORDER BY cr.created_at DESC
    ", $params) ?: [];

    $data = [];
    $canApprove = $rbac->can('approve_course_forms');

    foreach ($forms as $form) {
        $actions = '<button class="btn btn-info btn-sm viewDepartmentCourseForm" data-id="' . (int)$form['course_regID'] . '">' . (int)$form['courses_count'] . ' Courses</button>';

        if ($canApprove) {
            $actions .= '
                <select class="form-control form-control-sm mt-2 departmentCourseFormStatus" data-id="' . (int)$form['course_regID'] . '">
                    <option value="">Change Status</option>
                    <option value="pending">Pending</option>
                    <option value="submitted">Submitted</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            ';
        }

        $data[] = [
            'student' => htmlspecialchars(trim($form['first_name'] . ' ' . $form['other_name'] . ' ' . $form['last_name'])) . '<br><small>' . htmlspecialchars($form['matric_no']) . '</small>',
            'level' => htmlspecialchars($form['department_name']) . '<br><small>' . htmlspecialchars($form['level_name']) . '</small>',
            'courses' => (int)$form['courses_count'],
            'status' => '<span class="badge bg-' . ($form['approval_status'] === 'approved' ? 'success' : ($form['approval_status'] === 'rejected' ? 'danger' : 'info')) . '">' . ucfirst($form['approval_status']) . '</span>',
            'created_at' => date('d M Y, h:i A', strtotime($form['created_at'])),
            'actions' => $actions
        ];
    }

    echo json_encode(['status' => true, 'data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage(), 'data' => []]);
}
exit;
