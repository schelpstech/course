<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('allocate_courses');

$response = [
    'status' => false,
    'message' => 'Unable to save allocation.',
    'csrf_token' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request.');
    }

    if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'course_allocation_save')) {
        throw new Exception('Invalid or expired request.');
    }

    $id = (int)($_POST['id'] ?? 0);
    $sessionId = (int)($_POST['academic_session_id'] ?? 0);
    $semesterId = (int)($_POST['semester_id'] ?? 0);
    $institutionId = (int)($_POST['institution_id'] ?? 0);
    $departmentId = (int)($_POST['department_id'] ?? 0);
    $levelId = (int)($_POST['level_id'] ?? 0);
    $courseId = (int)($_POST['course_id'] ?? 0);
    $lecturerId = (int)($_POST['lecturer_id'] ?? 0);
    $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

    if (!$sessionId || !$semesterId || !$institutionId || !$departmentId || !$courseId || !$lecturerId) {
        throw new Exception('All required allocation fields must be selected.');
    }

    $rbac->requireDepartmentScope($departmentId);

    $oldValue = $id ? $model->queryOne("
        SELECT *
        FROM course_allocations
        WHERE id = :id
        LIMIT 1
    ", ['id' => $id]) : null;

    if ($id > 0 && !$oldValue) {
        throw new Exception('Allocation record was not found.');
    }

    if ($oldValue) {
        $rbac->requireDepartmentScope((int)$oldValue['department_id']);

        if ($oldValue['status'] !== 'inactive') {
            throw new Exception('Disable this allocation before modifying it for reallocation.');
        }
    }

    $course = $model->queryOne("
        SELECT c.id, c.level_id, l.department_id, p.institution_id
        FROM courses c
        JOIN levels l ON l.id = c.level_id
        JOIN department d ON d.id = l.department_id
        JOIN programmes p ON p.id = d.programme_id
        WHERE c.id = :course_id
        LIMIT 1
    ", ['course_id' => $courseId]);

    if (!$course || (int)$course['department_id'] !== $departmentId || (int)$course['institution_id'] !== $institutionId) {
        throw new Exception('Selected course does not belong to the selected department.');
    }

    if ($levelId > 0 && (int)$course['level_id'] !== $levelId) {
        throw new Exception('Selected course does not belong to the selected level.');
    }

    $lecturer = $model->queryOne("
        SELECT id, admin_id
        FROM lecturers
        WHERE id = :lecturer_id
        AND status = 1
        AND department_id = :department_id
        LIMIT 1
    ", [
        'lecturer_id' => $lecturerId,
        'department_id' => $departmentId
    ]);

    if (!$lecturer) {
        throw new Exception('Selected lecturer is not active in the selected department.');
    }

    if ($status === 'active') {
        $duplicate = $model->queryOne("
            SELECT ca.id, a.fullname AS lecturer_name
            FROM course_allocations ca
            JOIN lecturers l ON l.id = ca.lecturer_id
            JOIN admins a ON a.id = l.admin_id
            WHERE ca.course_id = :course_id
            AND ca.academic_session_id = :session_id
            AND ca.semester_id = :semester_id
            AND ca.status = 'active'
            AND ca.id <> :id
            LIMIT 1
        ", [
            'course_id' => $courseId,
            'session_id' => $sessionId,
            'semester_id' => $semesterId,
            'id' => $id
        ]);

        if ($duplicate) {
            throw new Exception('This course is already allocated to ' . $duplicate['lecturer_name'] . ' for the selected session and semester. Disable that allocation before reallocating.');
        }
    }

    $model->beginTransaction();

    if ($id > 0) {
        $model->update('course_allocations', [
            'lecturer_id' => $lecturerId,
            'course_id' => $courseId,
            'institution_id' => $institutionId,
            'department_id' => $departmentId,
            'level_id' => $course['level_id'],
            'academic_session_id' => $sessionId,
            'semester_id' => $semesterId,
            'status' => $status,
            'allocated_by' => $_SESSION['admin_id'] ?? null,
            'allocated_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);

        $model->update('result_sheets', [
            'submitted_by' => $lecturer['admin_id']
        ], ['course_allocation_id' => $id]);

        $db->prepare("
            UPDATE result_scores rsc
            JOIN result_sheets rs ON rs.id = rsc.result_sheet_id
            SET rsc.last_edited_by = :admin_id
            WHERE rs.course_allocation_id = :allocation_id
        ")->execute([
            'admin_id' => $lecturer['admin_id'],
            'allocation_id' => $id
        ]);

        $allocationId = $id;
        $auditAction = 'Course reallocated';
        $message = 'Course reallocated successfully. Existing result sheets and scores remain attached to the new lecturer.';
    } else {
        $allocationId = (int)$model->insert_data('course_allocations', [
            'lecturer_id' => $lecturerId,
            'course_id' => $courseId,
            'institution_id' => $institutionId,
            'department_id' => $departmentId,
            'level_id' => $course['level_id'],
            'academic_session_id' => $sessionId,
            'semester_id' => $semesterId,
            'status' => $status,
            'allocated_by' => $_SESSION['admin_id'] ?? null,
            'allocated_at' => date('Y-m-d H:i:s')
        ]);
        $auditAction = 'Course allocated';
        $message = 'Course allocation saved successfully.';
    }

    $rbac->logAudit($auditAction, 'course_allocation', (string)$allocationId, $oldValue, [
        'lecturer_id' => $lecturerId,
        'course_id' => $courseId,
        'academic_session_id' => $sessionId,
        'semester_id' => $semesterId,
        'status' => $status
    ]);

    $model->commit();

    $response['status'] = true;
    $response['message'] = $message;
} catch (Throwable $e) {
    $model->rollBack();
    $response['message'] = $e->getMessage();
}

$response['csrf_token'] = $utility->generateCsrf('course_allocation_save');
echo json_encode($response);
exit;
