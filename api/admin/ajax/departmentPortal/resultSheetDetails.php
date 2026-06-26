<?php
require_once '../../../../start.inc.php';

$utility->requireAdmin();
$rbac->requireAny(['moderate_results', 'approve_results']);

try {
    $departmentId = $rbac->requireDepartmentScope();
    $id = (int)($_GET['id'] ?? 0);
    $params = ['id' => $id];
    $scopeSql = '';

    if ($departmentId) {
        $scopeSql = 'AND ca.department_id = :department_id';
        $params['department_id'] = $departmentId;
    }

    $sheet = $model->queryOne("
        SELECT
            rs.*,
            ca.course_id,
            ca.institution_id,
            c.course_code,
            c.course_title,
            a.fullname AS lecturer_name,
            d.name AS department_name,
            lv.name AS level_name,
            acs.name AS session_name,
            sem.name AS semester_name
        FROM result_sheets rs
        JOIN course_allocations ca ON ca.id = rs.course_allocation_id
        JOIN courses c ON c.id = ca.course_id
        JOIN lecturers l ON l.id = ca.lecturer_id
        JOIN admins a ON a.id = l.admin_id
        JOIN department d ON d.id = ca.department_id
        JOIN levels lv ON lv.id = ca.level_id
        JOIN academic_sessions acs ON acs.id = ca.academic_session_id
        JOIN semesters sem ON sem.id = ca.semester_id
        WHERE rs.id = :id
        {$scopeSql}
        LIMIT 1
    ", $params);

    if (!$sheet) {
        throw new Exception('Result sheet not found in your department.');
    }

    $scores = $model->query("
        SELECT
            rsc.*,
            s.matric_no,
            s.first_name,
            s.other_name,
            s.last_name
        FROM result_scores rsc
        JOIN students s ON s.id = rsc.student_id
        WHERE rsc.result_sheet_id = :id
        ORDER BY s.matric_no ASC
    ", ['id' => $id]) ?: [];

    $distribution = [];
    $failed = [];
    $missing = [];

    foreach ($scores as $score) {
        $grade = $score['letter_grade'] ?: 'N/A';
        $distribution[$grade] = ($distribution[$grade] ?? 0) + 1;

        if ((float)($score['grade_point'] ?? 0) <= 0) {
            $failed[] = $score;
        }

        if ($score['ca_score'] === null || $score['exam_score'] === null) {
            $missing[] = $score;
        }
    }
} catch (Throwable $e) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}
?>

<input type="hidden" id="activeDepartmentResultSheetId" value="<?= (int)$sheet['id']; ?>">

<div class="row g-3 mb-3">
    <div class="col-md-4"><strong>Course</strong><br><?= htmlspecialchars($sheet['course_code'] . ' - ' . $sheet['course_title']); ?></div>
    <div class="col-md-4"><strong>Lecturer</strong><br><?= htmlspecialchars($sheet['lecturer_name']); ?></div>
    <div class="col-md-4"><strong>Status</strong><br><?= htmlspecialchars(ucfirst($sheet['moderation_status'])); ?></div>
    <div class="col-md-4"><strong>Department</strong><br><?= htmlspecialchars($sheet['department_name']); ?></div>
    <div class="col-md-4"><strong>Level</strong><br><?= htmlspecialchars($sheet['level_name']); ?></div>
    <div class="col-md-4"><strong>Period</strong><br><?= htmlspecialchars($sheet['session_name'] . ' / ' . $sheet['semester_name']); ?></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="alert alert-info mb-0">Scores: <strong><?= count($scores); ?></strong></div>
    </div>
    <div class="col-md-4">
        <div class="alert alert-warning mb-0">Missing Scores: <strong><?= count($missing); ?></strong></div>
    </div>
    <div class="col-md-4">
        <div class="alert alert-danger mb-0">Failed Students: <strong><?= count($failed); ?></strong></div>
    </div>
</div>

<h6>Grade Distribution</h6>
<div class="mb-3">
    <?php foreach ($distribution as $grade => $count): ?>
        <span class="badge bg-secondary me-1"><?= htmlspecialchars($grade); ?>: <?= (int)$count; ?></span>
    <?php endforeach; ?>
</div>

<div class="table-responsive">
    <table class="table table-striped table-bordered table-sm">
        <thead>
            <tr>
                <th>#</th>
                <th>Matric No</th>
                <th>Student</th>
                <th>CA</th>
                <th>Exam</th>
                <th>Total</th>
                <th>Grade</th>
                <th>Point</th>
                <th>Remark</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($scores as $index => $score): ?>
                <tr>
                    <td><?= $index + 1; ?></td>
                    <td><?= htmlspecialchars($score['matric_no']); ?></td>
                    <td><?= htmlspecialchars(trim($score['first_name'] . ' ' . $score['other_name'] . ' ' . $score['last_name'])); ?></td>
                    <td><?= htmlspecialchars((string)$score['ca_score']); ?></td>
                    <td><?= htmlspecialchars((string)$score['exam_score']); ?></td>
                    <td><?= htmlspecialchars((string)$score['total_score']); ?></td>
                    <td><?= htmlspecialchars((string)$score['letter_grade']); ?></td>
                    <td><?= htmlspecialchars((string)$score['grade_point']); ?></td>
                    <td><?= htmlspecialchars((string)$score['remark']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
