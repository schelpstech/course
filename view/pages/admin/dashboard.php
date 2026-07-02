<?php

$admin_id = (int)($_SESSION['admin_id'] ?? 0);
$admin = $adminModel->getadminById($admin_id) ?: [];
$adminRoles = isset($rbac) ? $rbac->roleSlugs($admin_id) : [];
$isSuper = isset($rbac) && $rbac->hasRole('super', $admin_id);
$escape = static fn($value): string => htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
$formatDate = static function ($value): string {
    if (empty($value)) {
        return '';
    }

    $timestamp = strtotime((string)$value);
    return $timestamp ? date('d M Y, h:i A', $timestamp) : (string)$value;
};

$departmentPermissions = [
    'view_department_students',
    'view_course_forms',
    'manage_dept_courses',
    'allocate_dept_courses',
    'moderate_results',
    'approve_results'
];
$lecturerPermissions = ['view_results', 'enter_ca_scores', 'enter_exam_scores', 'submit_scores'];

$departmentScopeId = isset($rbac) ? $rbac->departmentScopeId($admin_id) : null;
$isDepartmentDashboard = !$isSuper && $departmentScopeId && isset($rbac) && $rbac->canAny($departmentPermissions, $admin_id);
$isLecturerDashboard = !$isSuper && !$isDepartmentDashboard && isset($rbac) && $rbac->canAny($lecturerPermissions, $admin_id);
$dashboardMode = $isDepartmentDashboard ? 'department' : ($isLecturerDashboard ? 'lecturer' : 'admin');
$dashboardCards = [];
$primaryListTitle = '';
$primaryListRows = [];
$secondaryListTitle = '';
$secondaryListRows = [];
$dashboardNotice = '';
$dashboardActions = [];
$dashboardLead = 'Here is a quick overview of your portal activity.';

if ($dashboardMode === 'department') {
    $department = $model->queryOne("
        SELECT d.*, p.name AS programme_name, i.name AS institution_name
        FROM department d
        JOIN programmes p ON p.id = d.programme_id
        JOIN institutions i ON i.id = p.institution_id
        WHERE d.id = :department_id
        LIMIT 1
    ", ['department_id' => $departmentScopeId]) ?: [];

    $stats = $model->queryOne("
        SELECT
            COUNT(DISTINCT s.id) AS total_students,
            COUNT(DISTINCT CASE WHEN sr.courses_registered = 1 THEN s.id END) AS registered_students,
            COUNT(DISTINCT CASE WHEN cr.approval_status IN ('pending', 'submitted') THEN cr.course_regID END) AS pending_course_forms,
            COUNT(DISTINCT CASE WHEN cr.approval_status = 'approved' THEN cr.course_regID END) AS approved_course_forms,
            COUNT(DISTINCT c.id) AS department_courses,
            COUNT(DISTINCT l.id) AS lecturers,
            COUNT(DISTINCT ca.id) AS allocated_courses,
            COUNT(DISTINCT CASE WHEN COALESCE(rs.moderation_status, 'pending') IN ('pending', 'submitted') THEN rs.id END) AS pending_result_sheets,
            COUNT(DISTINCT CASE WHEN rs.moderation_status = 'approved' THEN rs.id END) AS approved_result_sheets
        FROM department d
        LEFT JOIN students s ON s.department_id = d.id
        LEFT JOIN semesterregistration sr ON sr.student_id = s.student_id
        LEFT JOIN course_registered cr ON cr.student_id = s.student_id
        LEFT JOIN levels lv ON lv.department_id = d.id
        LEFT JOIN courses c ON c.level_id = lv.id
        LEFT JOIN lecturers l ON l.department_id = d.id AND l.status = 1
        LEFT JOIN course_allocations ca ON ca.department_id = d.id AND ca.status = 'active'
        LEFT JOIN result_sheets rs ON rs.course_allocation_id = ca.id
        WHERE d.id = :department_id
    ", ['department_id' => $departmentScopeId]) ?: [];

    $dashboardLead = trim(($department['name'] ?? 'Department') . ' / ' . ($department['programme_name'] ?? '') . ' / ' . ($department['institution_name'] ?? ''));
    $dashboardCards = [
        ['label' => 'Department Students', 'value' => $stats['total_students'] ?? 0, 'class' => 'primary', 'icon' => 'ph ph-student'],
        ['label' => 'Registered Students', 'value' => $stats['registered_students'] ?? 0, 'class' => 'success', 'icon' => 'ph ph-check-circle'],
        ['label' => 'Pending Course Forms', 'value' => $stats['pending_course_forms'] ?? 0, 'class' => 'warning', 'icon' => 'ph ph-clock'],
        ['label' => 'Approved Course Forms', 'value' => $stats['approved_course_forms'] ?? 0, 'class' => 'success', 'icon' => 'ph ph-seal-check'],
        ['label' => 'Department Courses', 'value' => $stats['department_courses'] ?? 0, 'class' => 'info', 'icon' => 'ph ph-books'],
        ['label' => 'Active Lecturers', 'value' => $stats['lecturers'] ?? 0, 'class' => 'primary', 'icon' => 'ph ph-chalkboard-teacher'],
        ['label' => 'Allocated Courses', 'value' => $stats['allocated_courses'] ?? 0, 'class' => 'secondary', 'icon' => 'ph ph-arrows-split'],
        ['label' => 'Pending Result Sheets', 'value' => $stats['pending_result_sheets'] ?? 0, 'class' => 'warning', 'icon' => 'ph ph-file-text'],
        ['label' => 'Approved Result Sheets', 'value' => $stats['approved_result_sheets'] ?? 0, 'class' => 'success', 'icon' => 'ph ph-clipboard-text']
    ];

    $primaryListTitle = 'Recent Course Forms';
    $primaryListRows = $model->query("
        SELECT cr.course_regID, cr.approval_status, cr.total_units, cr.created_at,
            s.first_name, s.other_name, s.last_name, s.matric_no,
            lv.name AS level_name
        FROM course_registered cr
        JOIN students s ON s.student_id = cr.student_id
        JOIN levels lv ON lv.id = s.level_id
        WHERE s.department_id = :department_id
        ORDER BY cr.created_at DESC
        LIMIT 5
    ", ['department_id' => $departmentScopeId]) ?: [];

    $secondaryListTitle = 'Recent Result Sheets';
    $secondaryListRows = $model->query("
        SELECT rs.id, rs.ca_status, rs.exam_status, rs.moderation_status, rs.updated_at,
            c.course_code, c.course_title, a.fullname AS lecturer_name
        FROM result_sheets rs
        JOIN course_allocations ca ON ca.id = rs.course_allocation_id
        JOIN courses c ON c.id = ca.course_id
        JOIN lecturers l ON l.id = ca.lecturer_id
        JOIN admins a ON a.id = l.admin_id
        WHERE ca.department_id = :department_id
        ORDER BY rs.updated_at DESC, rs.id DESC
        LIMIT 5
    ", ['department_id' => $departmentScopeId]) ?: [];

    $dashboardActions[] = ['label' => 'Course Forms', 'page' => 'departmentCourseForms', 'icon' => 'ph ph-list-checks'];
    $dashboardActions[] = ['label' => 'Moderation', 'page' => 'departmentModeration', 'icon' => 'ph ph-clipboard-text'];
} elseif ($dashboardMode === 'lecturer') {
    $lecturer = $resultService->currentLecturer($admin_id);
    $params = ['admin_id' => $admin_id];

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
        WHERE ca.status = 'active'
        AND l.admin_id = :admin_id
    ", $params) ?: [];

    if (!$lecturer) {
        $dashboardNotice = 'No active lecturer profile is linked to this staff account. Ask the Super Admin to assign the Lecturer profile and course allocations.';
    }

    $dashboardLead = 'Your assigned courses, score-entry status, and submitted sheets.';
    $dashboardCards = [
        ['label' => 'Assigned Courses', 'value' => $summary['assigned_courses'] ?? 0, 'class' => 'primary', 'icon' => 'ph ph-books'],
        ['label' => 'Registered Students', 'value' => $summary['total_students'] ?? 0, 'class' => 'success', 'icon' => 'ph ph-student'],
        ['label' => 'Pending CA', 'value' => $summary['pending_ca'] ?? 0, 'class' => 'warning', 'icon' => 'ph ph-pencil-simple'],
        ['label' => 'Pending Exam', 'value' => $summary['pending_exam'] ?? 0, 'class' => 'warning', 'icon' => 'ph ph-note-pencil'],
        ['label' => 'Submitted Courses', 'value' => $summary['submitted_courses'] ?? 0, 'class' => 'info', 'icon' => 'ph ph-check-circle'],
        ['label' => 'Returned Sheets', 'value' => $summary['returned_sheets'] ?? 0, 'class' => 'danger', 'icon' => 'ph ph-arrow-u-down-left']
    ];

    $primaryListTitle = 'Recent Allocations';
    $primaryListRows = $model->query("
        SELECT ca.id, c.course_code, c.course_title, d.name AS department_name,
            lv.name AS level_name, s.name AS session_name, sem.name AS semester_name,
            COALESCE(rs.ca_status, 'draft') AS ca_status,
            COALESCE(rs.exam_status, 'draft') AS exam_status,
            COALESCE(rs.moderation_status, 'pending') AS moderation_status
        FROM course_allocations ca
        JOIN courses c ON c.id = ca.course_id
        JOIN department d ON d.id = ca.department_id
        JOIN levels lv ON lv.id = ca.level_id
        JOIN academic_sessions s ON s.id = ca.academic_session_id
        JOIN semesters sem ON sem.id = ca.semester_id
        JOIN lecturers l ON l.id = ca.lecturer_id
        LEFT JOIN result_config rcfg
            ON rcfg.academic_session_id = ca.academic_session_id
            AND rcfg.semester_id = ca.semester_id
            AND rcfg.status = 'active'
        LEFT JOIN result_sheets rs
            ON rs.course_allocation_id = ca.id
            AND rs.result_config_id = rcfg.id
        WHERE ca.status = 'active'
        AND l.admin_id = :admin_id
        ORDER BY s.id DESC, sem.id DESC, c.course_code ASC
        LIMIT 6
    ", $params) ?: [];

    $secondaryListTitle = 'Submission Status';
    $secondaryListRows = $primaryListRows;
    $dashboardActions[] = ['label' => 'Open Scoresheet', 'page' => 'lecturerScoresheet', 'icon' => 'ph ph-table'];
    $dashboardActions[] = ['label' => 'Lecturer Dashboard', 'page' => 'lecturerDashboard', 'icon' => 'ph ph-chart-bar'];
} else {
    $students = $adminModel->getStudents() ?: [];
    $payments = $adminModel->getPayments() ?: [];
    $totalStudents = (int)$adminModel->countStudents();
    $totalCourses = (int)$adminModel->countCourses();
    $totalPayments = (int)$adminModel->countPayments();
    $institutionStats = $adminModel->countStudentsPerInstitution() ?: [];
    $currentSession = getCurrentSession($model);
    $currentSemester = getActiveSemester($model);
    $semesterStats = ($currentSession && $currentSemester)
        ? ($adminModel->getSemesterRegistrationStats($currentSession['id'], $currentSemester['id']) ?: [])
        : [];

    $step1 = $totalStudents > 0;
    $step2 = $totalCourses > 0;
    $step3 = $totalPayments > 0;
    $currentStep = 1;
    if ($step1) {
        $currentStep = 2;
    }
    if ($step2) {
        $currentStep = 3;
    }
    if ($step3) {
        $currentStep = 4;
    }
    $progress = (($currentStep - 1) / 3) * 100;

    $dashboardLead = 'Here is a quick overview of system setup, students, courses, and payments.';
    $dashboardCards = [
        ['label' => 'Total Students', 'value' => $totalStudents, 'class' => 'primary', 'icon' => 'ti ti-users'],
        ['label' => 'Total Courses', 'value' => $totalCourses, 'class' => 'success', 'icon' => 'ti ti-book'],
        ['label' => 'Successful Payments', 'value' => $totalPayments, 'class' => 'warning', 'icon' => 'ti ti-credit-card'],
        ['label' => 'Receipts Uploaded', 'value' => $semesterStats['receipt_uploaded'] ?? 0, 'class' => 'warning', 'icon' => 'ti ti-upload'],
        ['label' => 'Payments Confirmed', 'value' => $semesterStats['payment_confirmed'] ?? 0, 'class' => 'info', 'icon' => 'ti ti-check'],
        ['label' => 'Internet Fee Paid', 'value' => $semesterStats['course_fee_paid'] ?? 0, 'class' => 'primary', 'icon' => 'ti ti-currency-naira'],
        ['label' => 'Completed Registration', 'value' => $semesterStats['courses_registered'] ?? 0, 'class' => 'success', 'icon' => 'ti ti-checklist']
    ];

    foreach ($institutionStats as $inst) {
        $dashboardCards[] = [
            'label' => $inst['institution_name'] ?? 'Institution',
            'value' => $inst['total_students'] ?? 0,
            'class' => 'primary',
            'icon' => 'ti ti-building'
        ];
    }

    if ($totalStudents === 0) {
        $dashboardNotice = 'No students registered yet.';
    } elseif ($totalCourses === 0) {
        $dashboardNotice = 'No courses created yet.';
    } elseif ($totalPayments === 0) {
        $dashboardNotice = 'No successful payments recorded yet.';
    }

    $primaryListTitle = 'Recent Students';
    $primaryListRows = array_slice($students, 0, 5);
    $secondaryListTitle = 'Recent Payments';
    $secondaryListRows = array_slice($payments, 0, 5);
}
?>

<div class="row g-3">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <h4 class="mb-2">Welcome back, <?= $escape($admin['fullname'] ?? 'Admin'); ?></h4>
                    <p class="mb-0 text-muted"><?= $escape($dashboardLead); ?></p>
                    <?php if (!empty($adminRoles)): ?>
                        <small class="text-muted">Role: <?= $escape(implode(', ', array_map('ucfirst', $adminRoles))); ?></small>
                    <?php endif; ?>
                </div>
                <?php if (!empty($dashboardActions)): ?>
                    <div class="d-flex flex-wrap align-items-start gap-2">
                        <?php foreach ($dashboardActions as $action): ?>
                            <a class="btn btn-primary" href="<?= route($action['page'], $utility); ?>">
                                <i class="<?= $escape($action['icon']); ?>"></i> <?= $escape($action['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($dashboardNotice !== ''): ?>
        <div class="col-sm-12">
            <div class="alert alert-info mb-0">
                <i class="ti ti-info-circle me-1"></i> <?= $escape($dashboardNotice); ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($dashboardMode === 'admin'): ?>
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-4 fw-bold">System Setup Progress</h6>
                    <div class="stepper-wrapper">
                        <div class="stepper-line">
                            <div class="stepper-progress" style="width: <?= $progress ?>%"></div>
                        </div>
                        <div class="stepper-item <?= $step1 ? 'completed' : ($currentStep == 1 ? 'active' : '') ?>">
                            <div class="step-counter"><?= $step1 ? '<i class="feather icon-check"></i>' : '1' ?></div>
                            <div class="step-name">Students</div>
                        </div>
                        <div class="stepper-item <?= $step2 ? 'completed' : ($currentStep == 2 ? 'active' : '') ?>">
                            <div class="step-counter"><?= $step2 ? '<i class="feather icon-check"></i>' : '2' ?></div>
                            <div class="step-name">Courses</div>
                        </div>
                        <div class="stepper-item <?= $step3 ? 'completed' : ($currentStep == 3 ? 'active' : '') ?>">
                            <div class="step-counter"><?= $step3 ? '<i class="feather icon-check"></i>' : '3' ?></div>
                            <div class="step-name">Payments</div>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <small class="text-muted"><?= round($progress) ?>% system setup completion</small>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php foreach ($dashboardCards as $card): ?>
        <div class="col-lg-4 col-md-6">
            <div class="card mb-0">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar bg-light-<?= $escape($card['class']); ?>">
                            <i class="<?= $escape($card['icon']); ?> f-24"></i>
                        </div>
                        <div class="ms-3">
                            <p class="mb-1"><?= $escape($card['label']); ?></p>
                            <h4 class="mb-0"><?= number_format((int)($card['value'] ?? 0)); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><?= $escape($primaryListTitle); ?></h5>
            </div>
            <div class="card-body">
                <?php if (!empty($primaryListRows)): ?>
                    <?php foreach ($primaryListRows as $row): ?>
                        <?php if ($dashboardMode === 'department'): ?>
                            <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                                <span>
                                    <?= $escape(trim(($row['first_name'] ?? '') . ' ' . ($row['other_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))); ?>
                                    <br><small class="text-muted"><?= $escape($row['matric_no'] ?? ''); ?> / <?= $escape($row['level_name'] ?? ''); ?></small>
                                </span>
                                <span class="text-end">
                                    <span class="badge bg-info"><?= $escape(ucfirst($row['approval_status'] ?? 'pending')); ?></span>
                                    <br><small class="text-muted"><?= $escape($formatDate($row['created_at'] ?? '')); ?></small>
                                </span>
                            </div>
                        <?php elseif ($dashboardMode === 'lecturer'): ?>
                            <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                                <span>
                                    <?= $escape(($row['course_code'] ?? '') . ' - ' . ($row['course_title'] ?? '')); ?>
                                    <br><small class="text-muted"><?= $escape(($row['department_name'] ?? '') . ' / ' . ($row['level_name'] ?? '')); ?></small>
                                </span>
                                <span class="text-end">
                                    <small class="text-muted"><?= $escape(($row['session_name'] ?? '') . ', ' . ($row['semester_name'] ?? '')); ?></small>
                                    <br><span class="badge bg-secondary">CA: <?= $escape($row['ca_status'] ?? 'draft'); ?></span>
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                                <span><?= $escape(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))); ?></span>
                                <small class="text-muted"><?= $escape($row['matric_no'] ?? 'N/A'); ?></small>
                                <span><?= $escape($formatDate($row['created_at'] ?? '')); ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">No records found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><?= $escape($secondaryListTitle ?: 'Activity'); ?></h5>
            </div>
            <div class="card-body">
                <?php if (!empty($secondaryListRows)): ?>
                    <?php foreach ($secondaryListRows as $row): ?>
                        <?php if ($dashboardMode === 'department'): ?>
                            <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                                <span>
                                    <?= $escape(($row['course_code'] ?? '') . ' - ' . ($row['course_title'] ?? '')); ?>
                                    <br><small class="text-muted"><?= $escape($row['lecturer_name'] ?? ''); ?></small>
                                </span>
                                <span class="text-end">
                                    <span class="badge bg-info"><?= $escape(ucfirst($row['moderation_status'] ?? 'pending')); ?></span>
                                    <br><small class="text-muted">CA: <?= $escape($row['ca_status'] ?? 'draft'); ?> / Exam: <?= $escape($row['exam_status'] ?? 'draft'); ?></small>
                                </span>
                            </div>
                        <?php elseif ($dashboardMode === 'lecturer'): ?>
                            <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                                <span>
                                    <?= $escape(($row['course_code'] ?? '') . ' - ' . ($row['course_title'] ?? '')); ?>
                                    <br><small class="text-muted">Moderation: <?= $escape($row['moderation_status'] ?? 'pending'); ?></small>
                                </span>
                                <span class="text-end">
                                    <span class="badge bg-secondary">CA: <?= $escape($row['ca_status'] ?? 'draft'); ?></span>
                                    <br><span class="badge bg-secondary mt-1">Exam: <?= $escape($row['exam_status'] ?? 'draft'); ?></span>
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                                <span><?= $escape($row['paymentReference'] ?? 'TXN'); ?></span>
                                <strong>NGN <?= number_format((float)($row['amount_paid'] ?? 0)); ?></strong>
                                <span><?= $escape($formatDate($row['created_at'] ?? '')); ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">No records found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
