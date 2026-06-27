<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_dept_courses');

$response = [
    'status' => false,
    'message' => 'Unable to save course.',
    'csrf_token' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request.');
    }

    if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'department_course_save')) {
        throw new Exception('Invalid or expired request.');
    }

    $departmentId = $rbac->requireDepartmentScope();
    $id = (int)($_POST['id'] ?? 0);
    $levelId = (int)($_POST['level_id'] ?? 0);
    $semesterId = (int)($_POST['semester_id'] ?? 0);
    $courseCode = strtoupper(trim($_POST['course_code'] ?? ''));
    $courseTitle = trim($_POST['course_title'] ?? '');
    $unit = (int)($_POST['unit'] ?? 0);
    $courseType = $_POST['course_type'] ?? 'core';

    if (!$levelId || !$semesterId || $courseCode === '' || $courseTitle === '' || $unit < 1) {
        throw new Exception('All course fields are required.');
    }

    if (!in_array($courseType, ['core', 'elective'], true)) {
        throw new Exception('Invalid course type.');
    }

    $params = ['level_id' => $levelId];
    $scopeSql = '';
    if ($departmentId) {
        $scopeSql = 'AND department_id = :department_id';
        $params['department_id'] = $departmentId;
    }

    $level = $model->queryOne("
        SELECT id, department_id
        FROM levels
        WHERE id = :level_id
        {$scopeSql}
        LIMIT 1
    ", $params);

    if (!$level) {
        throw new Exception('Selected level is not within your department.');
    }

    $duplicate = $model->queryOne("
        SELECT id
        FROM courses
        WHERE course_code = :course_code
        AND level_id = :level_id
        AND semester_id = :semester_id
        AND id <> :id
        LIMIT 1
    ", [
        'course_code' => $courseCode,
        'level_id' => $levelId,
        'semester_id' => $semesterId,
        'id' => $id
    ]);

    if ($duplicate) {
        throw new Exception('This course code already exists for the selected level and semester.');
    }

    $data = [
        'course_code' => $courseCode,
        'course_title' => $courseTitle,
        'course_type' => $courseType,
        'unit' => $unit,
        'level_id' => $levelId,
        'semester_id' => $semesterId
    ];

    $oldValue = null;

    if ($id > 0) {
        $oldValue = $model->queryOne("
            SELECT c.*
            FROM courses c
            JOIN levels lv ON lv.id = c.level_id
            WHERE c.id = :id
            " . ($departmentId ? "AND lv.department_id = :department_id" : "") . "
            LIMIT 1
        ", $departmentId ? ['id' => $id, 'department_id' => $departmentId] : ['id' => $id]);

        if (!$oldValue) {
            throw new Exception('Course not found in your department.');
        }

        $model->update('courses', $data, ['id' => $id]);
        $courseId = $id;
    } else {
        $courseId = (int)$model->insert_data('courses', $data);
    }

    $rbac->logAudit($id ? 'Department course updated' : 'Department course created', 'course', (string)$courseId, $oldValue, $data);

    $response['status'] = true;
    $response['message'] = $id ? 'Course updated successfully.' : 'Course created successfully.';
} catch (Throwable $e) {
    $response['message'] = $e->getMessage();
}

$response['csrf_token'] = $utility->generateCsrf('department_course_save');
echo json_encode($response);
exit;
