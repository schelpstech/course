<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_dept_courses');

$response = [
    'status' => false,
    'message' => 'Unable to update course status.',
    'csrf_token' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request.');
    }

    if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'department_course_toggle')) {
        throw new Exception('Invalid or expired request.');
    }

    $departmentId = $rbac->requireDepartmentScope();
    $id = (int)($_POST['id'] ?? 0);

    $params = ['id' => $id];
    $scopeSql = '';
    if ($departmentId) {
        $scopeSql = 'AND lv.department_id = :department_id';
        $params['department_id'] = $departmentId;
    }

    $course = $model->queryOne("
        SELECT c.*
        FROM courses c
        JOIN levels lv ON lv.id = c.level_id
        WHERE c.id = :id
        {$scopeSql}
        LIMIT 1
    ", $params);

    if (!$course) {
        throw new Exception('Course not found in your department.');
    }

    $newStatus = (int)$course['course_status'] === 1 ? 0 : 1;
    $model->update('courses', ['course_status' => $newStatus], ['id' => $id]);
    $rbac->logAudit('Department course status changed', 'course', (string)$id, [
        'course_status' => $course['course_status']
    ], [
        'course_status' => $newStatus
    ]);

    $response['status'] = true;
    $response['message'] = $newStatus ? 'Course enabled.' : 'Course disabled.';
} catch (Throwable $e) {
    $response['message'] = $e->getMessage();
}

$response['csrf_token'] = $utility->generateCsrf('department_course_toggle');
echo json_encode($response);
exit;
