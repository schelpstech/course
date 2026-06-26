<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('approve_course_forms');

try {
    $departmentId = $rbac->requireDepartmentScope();
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $allowed = ['pending', 'submitted', 'approved', 'rejected'];

    if (!$id || !in_array($status, $allowed, true)) {
        throw new Exception('Invalid course-form status.');
    }

    $params = ['id' => $id];
    $scopeSql = '';
    if ($departmentId) {
        $scopeSql = 'AND s.department_id = :department_id';
        $params['department_id'] = $departmentId;
    }

    $form = $model->queryOne("
        SELECT cr.course_regID
        FROM course_registered cr
        JOIN students s ON s.student_id = cr.student_id
        WHERE cr.course_regID = :id
        {$scopeSql}
        LIMIT 1
    ", $params);

    if (!$form) {
        throw new Exception('Course form not found in your department.');
    }

    $model->update('course_registered', [
        'approval_status' => $status,
        'approval_date' => date('Y-m-d H:i:s')
    ], ['course_regID' => $id]);

    $rbac->logAudit('Department course form status changed', 'course_registered', (string)$id, null, [
        'approval_status' => $status
    ]);

    echo json_encode(['status' => true, 'message' => 'Course form status updated.']);
} catch (Throwable $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
exit;
