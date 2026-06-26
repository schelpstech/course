<?php
require_once '../../../../start.inc.php';

$utility->requireAdmin();
$rbac->requirePermission('view_course_forms');

try {
    $departmentId = $rbac->requireDepartmentScope();
    $id = (int)($_GET['id'] ?? 0);
    $params = ['id' => $id];
    $scopeSql = '';

    if ($departmentId) {
        $scopeSql = 'AND s.department_id = :department_id';
        $params['department_id'] = $departmentId;
    }

    $student = $model->queryOne("
        SELECT
            cr.course_regID,
            cr.approval_status,
            cr.total_units,
            s.first_name,
            s.last_name,
            s.other_name,
            s.matric_no,
            s.passport,
            d.name AS department,
            lv.name AS level,
            p.name AS programme,
            i.name AS institution
        FROM course_registered cr
        JOIN students s ON s.student_id = cr.student_id
        JOIN department d ON d.id = s.department_id
        JOIN levels lv ON lv.id = s.level_id
        JOIN programmes p ON p.id = s.programme_id
        JOIN institutions i ON i.id = s.institution_id
        WHERE cr.course_regID = :id
        {$scopeSql}
        LIMIT 1
    ", $params);

    if (!$student) {
        throw new Exception('Course form not found in your department.');
    }

    $courses = $model->query("
        SELECT c.course_code, c.course_title, c.unit, c.course_type
        FROM registered_course rc
        JOIN courses c ON c.id = rc.course_id
        WHERE rc.course_regID = :id
        ORDER BY c.course_code ASC
    ", ['id' => $id]) ?: [];
} catch (Throwable $e) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}
?>

<div class="mb-3">
    <h5><?= htmlspecialchars(trim($student['first_name'] . ' ' . $student['other_name'] . ' ' . $student['last_name'])); ?></h5>
    <p class="mb-1"><strong>Matric:</strong> <?= htmlspecialchars($student['matric_no']); ?></p>
    <p class="mb-1"><strong>Department:</strong> <?= htmlspecialchars($student['department']); ?> / <strong>Level:</strong> <?= htmlspecialchars($student['level']); ?></p>
    <p class="mb-0"><strong>Status:</strong> <?= htmlspecialchars(strtoupper($student['approval_status'])); ?></p>
</div>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Code</th>
            <th>Title</th>
            <th>Units</th>
            <th>Type</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($courses as $index => $course): ?>
            <tr>
                <td><?= $index + 1; ?></td>
                <td><?= htmlspecialchars($course['course_code']); ?></td>
                <td><?= htmlspecialchars($course['course_title']); ?></td>
                <td><?= (int)$course['unit']; ?></td>
                <td><?= htmlspecialchars(ucfirst($course['course_type'])); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
