<?php
$adminId = (int)($_SESSION['admin_id'] ?? 0);
$isSuper = isset($rbac) && $rbac->hasRole('super', $adminId);
$lecturer = $resultService->currentLecturer($adminId);

$params = [];
$where = "WHERE ca.status = 'active'";

if (!$isSuper) {
    $where .= " AND l.admin_id = :admin_id";
    $params['admin_id'] = $adminId;
}

$summary = $model->queryOne("
    SELECT
        COUNT(DISTINCT ca.id) AS assigned_courses,
        COUNT(DISTINCT st.id) AS total_students,
        SUM(CASE WHEN COALESCE(rs.ca_status, 'draft') <> 'submitted' THEN 1 ELSE 0 END) AS pending_ca,
        SUM(CASE WHEN COALESCE(rs.exam_status, 'draft') <> 'submitted' THEN 1 ELSE 0 END) AS pending_exam,
        SUM(CASE WHEN COALESCE(rs.ca_status, 'draft') = 'submitted' AND COALESCE(rs.exam_status, 'draft') = 'submitted' THEN 1 ELSE 0 END) AS submitted_courses,
        SUM(CASE WHEN COALESCE(rs.moderation_status, 'pending') = 'returned' THEN 1 ELSE 0 END) AS returned_sheets
    FROM course_allocations ca
    JOIN lecturers l ON l.id = ca.lecturer_id
    LEFT JOIN result_config rcfg
        ON rcfg.academic_session_id = ca.academic_session_id
        AND rcfg.semester_id = ca.semester_id
        AND rcfg.status = 'active'
    LEFT JOIN result_sheets rs
        ON rs.course_allocation_id = ca.id
        AND rs.result_config_id = rcfg.id
    LEFT JOIN registered_course rc
        ON rc.course_id = ca.course_id
    LEFT JOIN course_registered cr
        ON cr.course_regID = rc.course_regID
        AND cr.session = ca.academic_session_id
        AND cr.semester = ca.semester_id
        AND cr.approval_status <> 'rejected'
    LEFT JOIN students st ON st.student_id = cr.student_id
    {$where}
", $params) ?: [];

$cards = [
    ['label' => 'Assigned Courses', 'value' => $summary['assigned_courses'] ?? 0, 'class' => 'primary', 'icon' => 'ph ph-books'],
    ['label' => 'Total Students', 'value' => $summary['total_students'] ?? 0, 'class' => 'success', 'icon' => 'ph ph-student'],
    ['label' => 'Pending CA', 'value' => $summary['pending_ca'] ?? 0, 'class' => 'warning', 'icon' => 'ph ph-pencil-simple'],
    ['label' => 'Pending Exam', 'value' => $summary['pending_exam'] ?? 0, 'class' => 'warning', 'icon' => 'ph ph-note-pencil'],
    ['label' => 'Submitted Courses', 'value' => $summary['submitted_courses'] ?? 0, 'class' => 'info', 'icon' => 'ph ph-check-circle'],
    ['label' => 'Returned Sheets', 'value' => $summary['returned_sheets'] ?? 0, 'class' => 'danger', 'icon' => 'ph ph-arrow-u-down-left']
];
?>

<?php if (!$isSuper && !$lecturer): ?>
    <div class="alert alert-warning">
        No active lecturer profile is linked to this staff account. Ask the Super Admin to assign the Lecturer role and department scope.
    </div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($cards as $card): ?>
        <div class="col-lg-4 col-md-6">
            <div class="card mb-0">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar bg-light-<?= $card['class']; ?>">
                            <i class="<?= $card['icon']; ?> f-24"></i>
                        </div>
                        <div class="ms-3">
                            <p class="mb-1"><?= htmlspecialchars($card['label']); ?></p>
                            <h4 class="mb-0"><?= (int)$card['value']; ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    window.lecturerDashboardConfig = {
        scoresheetUrl: <?= json_encode(route('lecturerScoresheet', $utility)); ?>
    };
</script>

<div class="row mt-3">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Assigned Courses</h5>
                <a href="<?= route('lecturerScoresheet', $utility); ?>" class="btn btn-primary">
                    <i class="ph ph-table"></i> Open Scoresheet
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="lecturerDashboardCoursesTable" class="table table-striped table-bordered dataTable w-100">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Department</th>
                                <th>Level</th>
                                <th>Session</th>
                                <th>Semester</th>
                                <th>Students</th>
                                <th>CA</th>
                                <th>Exam</th>
                                <th>Moderation</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
