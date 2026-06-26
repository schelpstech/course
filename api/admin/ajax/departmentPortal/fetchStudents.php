<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requireAny(['view_department_students', 'view_students']);

try {
    $departmentId = $rbac->requireDepartmentScope();
    $where = $departmentId ? 'WHERE s.department_id = :department_id' : 'WHERE 1=1';
    $params = $departmentId ? ['department_id' => $departmentId] : [];

    $students = $model->query("
        SELECT
            s.*,
            u.email,
            u.is_active,
            p.name AS programme_name,
            d.name AS department_name,
            lv.name AS level_name
        FROM students s
        JOIN users u ON u.id = s.student_id
        JOIN programmes p ON p.id = s.programme_id
        JOIN department d ON d.id = s.department_id
        JOIN levels lv ON lv.id = s.level_id
        {$where}
        ORDER BY s.id DESC
    ", $params) ?: [];

    $data = [];

    foreach ($students as $student) {
        $data[] = [
            'name' => htmlspecialchars(trim($student['first_name'] . ' ' . $student['other_name'] . ' ' . $student['last_name'])),
            'matric' => htmlspecialchars(strtoupper($student['matric_no'])) . '<br><small>' . htmlspecialchars($student['email']) . '</small>',
            'programme' => htmlspecialchars($student['programme_name']),
            'level' => htmlspecialchars($student['level_name']),
            'status' => (int)$student['is_active'] === 1
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-danger">Disabled</span>',
            'actions' => '<button class="btn btn-info btn-sm viewDepartmentStudent" data-id="' . (int)$student['student_id'] . '">View</button>'
        ];
    }

    echo json_encode(['status' => true, 'data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage(), 'data' => []]);
}
exit;
