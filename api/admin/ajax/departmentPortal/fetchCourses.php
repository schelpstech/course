<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_dept_courses');

try {
    $departmentId = $rbac->requireDepartmentScope();
    $params = [];
    $where = "WHERE 1=1";

    if ($departmentId) {
        $where .= " AND lv.department_id = :department_id";
        $params['department_id'] = $departmentId;
    }

    $courses = $model->query("
        SELECT
            c.*,
            lv.name AS level_name,
            lv.code AS level_code,
            sem.name AS semester_name,
            acs.name AS session_name
        FROM courses c
        JOIN levels lv ON lv.id = c.level_id
        JOIN semesters sem ON sem.id = c.semester_id
        JOIN academic_sessions acs ON acs.id = sem.session_id
        {$where}
        ORDER BY c.id DESC
    ", $params) ?: [];

    $data = [];

    foreach ($courses as $course) {
        $isActive = (int)$course['course_status'] === 1;
        $data[] = [
            'code' => htmlspecialchars($course['course_code']),
            'title' => htmlspecialchars($course['course_title']),
            'unit' => (int)$course['unit'],
            'level' => htmlspecialchars($course['level_name']),
            'semester' => htmlspecialchars($course['semester_name'] . ' - ' . $course['session_name']),
            'type' => htmlspecialchars(ucfirst($course['course_type'])),
            'status' => $isActive ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Disabled</span>',
            'actions' => '
                <button class="btn btn-primary btn-sm editDepartmentCourse"
                    data-id="' . (int)$course['id'] . '"
                    data-code="' . htmlspecialchars($course['course_code'], ENT_QUOTES) . '"
                    data-title="' . htmlspecialchars($course['course_title'], ENT_QUOTES) . '"
                    data-unit="' . (int)$course['unit'] . '"
                    data-type="' . htmlspecialchars($course['course_type'], ENT_QUOTES) . '"
                    data-level="' . (int)$course['level_id'] . '"
                    data-semester="' . (int)$course['semester_id'] . '">
                    Edit
                </button>
                <button class="btn btn-sm ' . ($isActive ? 'btn-outline-danger' : 'btn-outline-success') . ' toggleDepartmentCourse ms-1" data-id="' . (int)$course['id'] . '">
                    ' . ($isActive ? 'Disable' : 'Enable') . '
                </button>
            '
        ];
    }

    echo json_encode(['status' => true, 'data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage(), 'data' => []]);
}
exit;
