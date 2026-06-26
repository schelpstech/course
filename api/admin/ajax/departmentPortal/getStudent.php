<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requireAny(['view_department_students', 'view_students']);

try {
    $departmentId = $rbac->requireDepartmentScope();
    $studentUserId = (int)($_GET['id'] ?? 0);

    $whereScope = $departmentId ? 'AND s.department_id = :department_id' : '';
    $params = ['student_id' => $studentUserId];
    if ($departmentId) {
        $params['department_id'] = $departmentId;
    }

    $student = $model->queryOne("
        SELECT
            s.*,
            u.email,
            p.name AS programme_name,
            d.name AS department_name,
            lv.name AS level_name,
            i.name AS institution_name
        FROM students s
        JOIN users u ON u.id = s.student_id
        JOIN programmes p ON p.id = s.programme_id
        JOIN department d ON d.id = s.department_id
        JOIN levels lv ON lv.id = s.level_id
        JOIN institutions i ON i.id = s.institution_id
        WHERE s.student_id = :student_id
        {$whereScope}
        LIMIT 1
    ", $params);

    if (!$student) {
        throw new Exception('Student not found in your department.');
    }

    $forms = $model->query("
        SELECT
            cr.course_regID,
            cr.approval_status,
            cr.total_units,
            cr.created_at,
            acs.name AS session_name,
            sem.name AS semester_name,
            COUNT(rc.id) AS courses_count
        FROM course_registered cr
        LEFT JOIN academic_sessions acs ON acs.id = cr.session
        LEFT JOIN semesters sem ON sem.id = cr.semester
        LEFT JOIN registered_course rc ON rc.course_regID = cr.course_regID
        WHERE cr.student_id = :student_id
        GROUP BY cr.course_regID
        ORDER BY cr.created_at DESC
    ", ['student_id' => $studentUserId]) ?: [];

    echo json_encode([
        'status' => true,
        'student' => $student,
        'forms' => $forms
    ]);
} catch (Throwable $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
exit;
