<?php
try {
    $departmentScopeId = $rbac->requireDepartmentScope();
} catch (Throwable $e) {
    $departmentScopeId = null;
    $departmentScopeError = $e->getMessage();
}

$scopeWhere = $departmentScopeId ? "WHERE d.id = :department_id" : "WHERE 1=1";
$scopeParams = $departmentScopeId ? ['department_id' => $departmentScopeId] : [];

$department = $departmentScopeId
    ? $model->queryOne("
        SELECT d.*, p.name AS programme_name, i.name AS institution_name
        FROM department d
        JOIN programmes p ON p.id = d.programme_id
        JOIN institutions i ON i.id = p.institution_id
        WHERE d.id = :department_id
        LIMIT 1
    ", ['department_id' => $departmentScopeId])
    : ['name' => 'All Departments', 'programme_name' => 'Global', 'institution_name' => 'Global'];

$stats = !empty($departmentScopeError) ? [] : ($model->queryOne("
    SELECT
        COUNT(DISTINCT s.id) AS total_students,
        COUNT(DISTINCT CASE WHEN sr.courses_registered = 1 THEN s.id END) AS registered_students,
        COUNT(DISTINCT CASE WHEN cr.approval_status IN ('pending', 'submitted') THEN cr.course_regID END) AS pending_course_forms,
        COUNT(DISTINCT CASE WHEN cr.approval_status = 'approved' THEN cr.course_regID END) AS approved_course_forms,
        COUNT(DISTINCT c.id) AS department_courses,
        COUNT(DISTINCT l.id) AS lecturers,
        COUNT(DISTINCT ca.id) AS allocated_courses,
        COUNT(DISTINCT CASE WHEN COALESCE(rs.moderation_status, 'pending') IN ('pending', 'submitted') THEN rs.id END) AS pending_result_sheets,
        COUNT(DISTINCT CASE WHEN rs.moderation_status = 'approved' THEN rs.id END) AS submitted_result_sheets
    FROM department d
    LEFT JOIN students s ON s.department_id = d.id
    LEFT JOIN semesterregistration sr ON sr.student_id = s.student_id
    LEFT JOIN course_registered cr ON cr.student_id = s.student_id
    LEFT JOIN levels lv ON lv.department_id = d.id
    LEFT JOIN courses c ON c.level_id = lv.id
    LEFT JOIN lecturers l ON l.department_id = d.id AND l.status = 1
    LEFT JOIN course_allocations ca ON ca.department_id = d.id AND ca.status = 'active'
    LEFT JOIN result_sheets rs ON rs.course_allocation_id = ca.id
    {$scopeWhere}
", $scopeParams) ?: []);

$cards = [
    ['label' => 'Total Students', 'value' => $stats['total_students'] ?? 0, 'class' => 'primary', 'icon' => 'ph ph-student'],
    ['label' => 'Registered Students', 'value' => $stats['registered_students'] ?? 0, 'class' => 'success', 'icon' => 'ph ph-check-circle'],
    ['label' => 'Pending Course Forms', 'value' => $stats['pending_course_forms'] ?? 0, 'class' => 'warning', 'icon' => 'ph ph-clock'],
    ['label' => 'Approved Course Forms', 'value' => $stats['approved_course_forms'] ?? 0, 'class' => 'success', 'icon' => 'ph ph-seal-check'],
    ['label' => 'Department Courses', 'value' => $stats['department_courses'] ?? 0, 'class' => 'info', 'icon' => 'ph ph-books'],
    ['label' => 'Lecturers', 'value' => $stats['lecturers'] ?? 0, 'class' => 'primary', 'icon' => 'ph ph-chalkboard-teacher'],
    ['label' => 'Allocated Courses', 'value' => $stats['allocated_courses'] ?? 0, 'class' => 'secondary', 'icon' => 'ph ph-arrows-split'],
    ['label' => 'Pending Result Sheets', 'value' => $stats['pending_result_sheets'] ?? 0, 'class' => 'warning', 'icon' => 'ph ph-file-text'],
    ['label' => 'Approved Result Sheets', 'value' => $stats['submitted_result_sheets'] ?? 0, 'class' => 'success', 'icon' => 'ph ph-clipboard-text']
];
?>

<?php if (!empty($departmentScopeError)): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($departmentScopeError); ?></div>
<?php else: ?>
    <div class="row mb-3">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-1"><?= htmlspecialchars($department['name'] ?? 'Department'); ?></h4>
                    <p class="text-muted mb-0">
                        <?= htmlspecialchars($department['programme_name'] ?? ''); ?> /
                        <?= htmlspecialchars($department['institution_name'] ?? ''); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

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
<?php endif; ?>
