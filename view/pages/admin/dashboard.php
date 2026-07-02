<?php

$admin_id = (int)($_SESSION['admin_id'] ?? 0);
$admin = $adminModel->getadminById($admin_id) ?: [];
$adminRoles = isset($rbac) ? $rbac->roleSlugs($admin_id) : [];
$isSuper = isset($rbac) && $rbac->hasRole('super', $admin_id);
$escape = static fn($value): string => htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
$money = static fn($amount): string => 'NGN ' . number_format((float)($amount ?? 0), 2);
$formatDate = static function ($value): string {
    if (empty($value)) {
        return '';
    }

    $timestamp = strtotime((string)$value);
    return $timestamp ? date('d M Y, h:i A', $timestamp) : (string)$value;
};
$percent = static function ($value, $total): int {
    $total = (int)$total;
    return $total > 0 ? (int)round(((float)$value / $total) * 100) : 0;
};
$tableExists = static fn(string $table): bool => isset($rbac) && $rbac->tableExists($table);

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
$isBursaryDashboard = !$isSuper && !$isDepartmentDashboard && !$isLecturerDashboard && isset($rbac) && $rbac->hasRole('bursary', $admin_id);
$isRegistryDashboard = !$isSuper && !$isDepartmentDashboard && !$isLecturerDashboard && !$isBursaryDashboard && isset($rbac) && $rbac->hasRole('registry', $admin_id);
$dashboardMode = $isDepartmentDashboard
    ? 'department'
    : ($isLecturerDashboard ? 'lecturer' : ($isBursaryDashboard ? 'bursary' : ($isRegistryDashboard ? 'registry' : 'admin')));

$dashboardCards = [];
$primaryListTitle = '';
$primaryListRows = [];
$secondaryListTitle = '';
$secondaryListRows = [];
$dashboardNotice = '';
$dashboardActions = [];
$dashboardLead = 'Here is a quick overview of your portal activity.';

$adminOverview = [];
$adminFinancials = [];
$adminProgress = [];
$adminAcademic = [];
$adminAttention = [];
$adminQueues = [];
$adminQuickActions = [];
$institutionRows = [];
$paymentTypeRows = [];
$recentResultRows = [];
$recentAdmissionRows = [];

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
} elseif ($dashboardMode === 'registry') {
    $students = $adminModel->getStudents() ?: [];
    $currentSession = getCurrentSession($model);
    $currentSemester = getActiveSemester($model);
    $totalStudents = (int)$adminModel->countStudents();
    $totalInstitutions = (int)$model->countRows('institutions');
    $totalProgrammes = (int)$model->countRows('programmes');
    $totalDepartments = (int)$model->countRows('department');
    $totalLevels = (int)$model->countRows('levels');

    $semesterStats = ($currentSession && $currentSemester)
        ? ($adminModel->getSemesterRegistrationStats($currentSession['id'], $currentSemester['id']) ?: [])
        : [];

    $courseFormStats = $model->queryOne("
        SELECT
            COUNT(*) AS total_forms,
            COUNT(CASE WHEN approval_status IN ('pending', 'submitted') THEN 1 END) AS pending_forms,
            COUNT(CASE WHEN approval_status = 'approved' THEN 1 END) AS approved_forms,
            COUNT(CASE WHEN approval_status = 'rejected' THEN 1 END) AS rejected_forms
        FROM course_registered
    ") ?: [];

    $admissionStats = [];
    if ($tableExists('admission_applications')) {
        $admissionStats = $model->queryOne("
            SELECT
                COUNT(*) AS total_applications,
                COUNT(CASE WHEN form_status IN ('Submitted','Pending Review') THEN 1 END) AS pending_screening,
                COUNT(CASE WHEN form_status IN ('Offered Admission','Accepted') THEN 1 END) AS admitted_candidates,
                COUNT(CASE WHEN form_status = 'Accepted' THEN 1 END) AS accepted_candidates
            FROM admission_applications
        ") ?: [];
    }

    $dashboardLead = 'Student records, course forms, admission tracking, and semester registration progress.';
    $dashboardCards = [
        ['label' => 'Total Students', 'value' => $totalStudents, 'class' => 'primary', 'icon' => 'ti ti-users'],
        ['label' => 'Semester Records', 'value' => $semesterStats['total_students'] ?? 0, 'class' => 'info', 'icon' => 'ti ti-list-details'],
        ['label' => 'Completed Registration', 'value' => $semesterStats['courses_registered'] ?? 0, 'class' => 'success', 'icon' => 'ti ti-checklist'],
        ['label' => 'Pending Course Forms', 'value' => $courseFormStats['pending_forms'] ?? 0, 'class' => 'warning', 'icon' => 'ti ti-clipboard-list'],
        ['label' => 'Approved Course Forms', 'value' => $courseFormStats['approved_forms'] ?? 0, 'class' => 'success', 'icon' => 'ti ti-circle-check'],
        ['label' => 'Admission Screening', 'value' => $admissionStats['pending_screening'] ?? 0, 'class' => 'info', 'icon' => 'ti ti-id-badge'],
        ['label' => 'Institutions', 'value' => $totalInstitutions, 'class' => 'primary', 'icon' => 'ti ti-building'],
        ['label' => 'Programmes', 'value' => $totalProgrammes, 'class' => 'secondary', 'icon' => 'ti ti-school'],
        ['label' => 'Departments', 'value' => $totalDepartments, 'class' => 'secondary', 'icon' => 'ti ti-hierarchy'],
        ['label' => 'Levels', 'value' => $totalLevels, 'class' => 'secondary', 'icon' => 'ti ti-layers-linked']
    ];

    $primaryListTitle = 'Recent Students';
    $primaryListRows = array_slice($students, 0, 6);
    $secondaryListTitle = 'Recent Course Forms';
    $secondaryListRows = $model->query("
        SELECT cr.course_regID, cr.approval_status, cr.created_at,
            s.first_name, s.other_name, s.last_name, s.matric_no,
            d.name AS department_name, lv.name AS level_name
        FROM course_registered cr
        JOIN students s ON s.student_id = cr.student_id
        JOIN department d ON d.id = s.department_id
        JOIN levels lv ON lv.id = s.level_id
        ORDER BY cr.created_at DESC
        LIMIT 6
    ") ?: [];

    $dashboardActions[] = ['label' => 'Manage Students', 'page' => 'students', 'icon' => 'ti ti-users'];
    $dashboardActions[] = ['label' => 'Course Forms', 'page' => 'courseformMgr', 'icon' => 'ti ti-clipboard-list'];
    if ($tableExists('admission_applications')) {
        $dashboardActions[] = ['label' => 'Admissions', 'page' => 'admissionApplications', 'icon' => 'ti ti-id-badge'];
    }
    $dashboardActions[] = ['label' => 'Registration Status', 'page' => 'semregistrationStatus', 'icon' => 'ti ti-list-check'];
} elseif ($dashboardMode === 'bursary') {
    $currentSession = getCurrentSession($model);
    $currentSemester = getActiveSemester($model);
    $semesterStats = ($currentSession && $currentSemester)
        ? ($adminModel->getSemesterRegistrationStats($currentSession['id'], $currentSemester['id']) ?: [])
        : [];

    $paymentStats = $model->queryOne("
        SELECT
            COUNT(*) AS total_records,
            COUNT(CASE WHEN status = 'successful' THEN 1 END) AS successful_records,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) AS pending_records,
            COUNT(CASE WHEN status = 'failed' THEN 1 END) AS failed_records,
            COALESCE(SUM(CASE WHEN status = 'successful' THEN amount_paid ELSE 0 END), 0) AS successful_amount,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN amount_paid ELSE 0 END), 0) AS pending_amount,
            COALESCE(SUM(CASE WHEN status = 'successful' AND DATE(created_at) = CURDATE() THEN amount_paid ELSE 0 END), 0) AS today_amount,
            COALESCE(SUM(CASE WHEN status = 'successful' AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN amount_paid ELSE 0 END), 0) AS month_amount
        FROM payments
    ") ?: [];

    $typeStats = $model->query("
        SELECT payment_type, status, COUNT(*) AS total_records, COALESCE(SUM(amount_paid), 0) AS total_amount
        FROM payments
        GROUP BY payment_type, status
        ORDER BY payment_type ASC, status ASC
    ") ?: [];

    $courseRegPending = 0;
    $courseRegSuccessful = 0;
    foreach ($typeStats as $row) {
        if (($row['payment_type'] ?? '') === 'course_reg' && ($row['status'] ?? '') === 'pending') {
            $courseRegPending = (int)$row['total_records'];
        }
        if (($row['payment_type'] ?? '') === 'course_reg' && ($row['status'] ?? '') === 'successful') {
            $courseRegSuccessful = (int)$row['total_records'];
        }
    }

    $dashboardLead = 'Payment reviews, internet fee confirmations, clearance work, and collection monitoring.';
    $dashboardCards = [
        ['label' => 'Successful Collection', 'value' => $money($paymentStats['successful_amount'] ?? 0), 'class' => 'success', 'icon' => 'ti ti-currency-naira'],
        ['label' => 'Today Collection', 'value' => $money($paymentStats['today_amount'] ?? 0), 'class' => 'primary', 'icon' => 'ti ti-calendar-dollar'],
        ['label' => 'This Month', 'value' => $money($paymentStats['month_amount'] ?? 0), 'class' => 'info', 'icon' => 'ti ti-chart-bar'],
        ['label' => 'Pending Amount', 'value' => $money($paymentStats['pending_amount'] ?? 0), 'class' => 'warning', 'icon' => 'ti ti-clock-dollar'],
        ['label' => 'Pending Reviews', 'value' => $paymentStats['pending_records'] ?? 0, 'class' => 'warning', 'icon' => 'ti ti-alert-circle'],
        ['label' => 'Failed Records', 'value' => $paymentStats['failed_records'] ?? 0, 'class' => 'danger', 'icon' => 'ti ti-circle-x'],
        ['label' => 'Receipts Uploaded', 'value' => $semesterStats['receipt_uploaded'] ?? 0, 'class' => 'info', 'icon' => 'ti ti-upload'],
        ['label' => 'Payments Confirmed', 'value' => $semesterStats['payment_confirmed'] ?? 0, 'class' => 'success', 'icon' => 'ti ti-check'],
        ['label' => 'Internet Pending', 'value' => $courseRegPending, 'class' => 'warning', 'icon' => 'ti ti-wifi'],
        ['label' => 'Internet Approved', 'value' => $courseRegSuccessful, 'class' => 'success', 'icon' => 'ti ti-wifi']
    ];

    $primaryListTitle = 'Pending Payment Reviews';
    $primaryListRows = $model->query("
        SELECT p.id, p.paymentReference, p.payment_type, p.amount_paid, p.status, p.created_at,
            s.first_name, s.other_name, s.last_name, s.matric_no
        FROM payments p
        LEFT JOIN students s ON s.student_id = p.student_id
        WHERE p.status = 'pending'
        ORDER BY p.created_at DESC
        LIMIT 6
    ") ?: [];

    $secondaryListTitle = 'Recent Payments';
    $secondaryListRows = $model->query("
        SELECT p.id, p.paymentReference, p.payment_type, p.amount_paid, p.status, p.created_at,
            s.first_name, s.other_name, s.last_name, s.matric_no
        FROM payments p
        LEFT JOIN students s ON s.student_id = p.student_id
        ORDER BY p.created_at DESC
        LIMIT 6
    ") ?: [];

    $dashboardActions[] = ['label' => 'Review Payments', 'page' => 'payment_remark', 'icon' => 'ti ti-credit-card'];
    $dashboardActions[] = ['label' => 'Internet Payments', 'page' => 'internetPaymentReview', 'icon' => 'ti ti-wifi'];
    $dashboardActions[] = ['label' => 'Payment Clearance', 'page' => 'payment_clearance', 'icon' => 'ti ti-clipboard-check'];
    $dashboardActions[] = ['label' => 'Clearance Manager', 'page' => 'manage_clearance', 'icon' => 'ti ti-settings-check'];
} else {
    $students = $adminModel->getStudents() ?: [];
    $payments = $adminModel->getPayments() ?: [];
    $currentSession = getCurrentSession($model);
    $currentSemester = getActiveSemester($model);

    $totalStudents = (int)$adminModel->countStudents();
    $totalCourses = (int)$adminModel->countCourses();
    $totalPayments = (int)$adminModel->countPayments();
    $totalInstitutions = (int)$model->countRows('institutions');
    $totalProgrammes = (int)$model->countRows('programmes');
    $totalDepartments = (int)$model->countRows('department');
    $totalLevels = (int)$model->countRows('levels');
    $activeLecturers = (int)$model->countRows('lecturers', ['where' => ['status' => 1]]);
    $activeAllocations = $tableExists('course_allocations')
        ? (int)$model->countRows('course_allocations', ['where' => ['status' => 'active']])
        : 0;

    $semesterStats = ($currentSession && $currentSemester)
        ? ($adminModel->getSemesterRegistrationStats($currentSession['id'], $currentSemester['id']) ?: [])
        : [];
    $semesterTotal = (int)($semesterStats['total_students'] ?? 0);
    $receiptUploaded = (int)($semesterStats['receipt_uploaded'] ?? 0);
    $paymentConfirmed = (int)($semesterStats['payment_confirmed'] ?? 0);
    $courseFeePaid = (int)($semesterStats['course_fee_paid'] ?? 0);
    $coursesRegistered = (int)($semesterStats['courses_registered'] ?? 0);

    $paymentStats = $model->queryOne("
        SELECT
            COUNT(*) AS total_records,
            COUNT(CASE WHEN status = 'successful' THEN 1 END) AS successful_records,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) AS pending_records,
            COUNT(CASE WHEN status = 'failed' THEN 1 END) AS failed_records,
            COALESCE(SUM(CASE WHEN status = 'successful' THEN amount_paid ELSE 0 END), 0) AS successful_amount,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN amount_paid ELSE 0 END), 0) AS pending_amount,
            COALESCE(SUM(CASE WHEN status = 'successful' AND DATE(created_at) = CURDATE() THEN amount_paid ELSE 0 END), 0) AS today_amount,
            COALESCE(SUM(CASE WHEN status = 'successful' AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN amount_paid ELSE 0 END), 0) AS month_amount
        FROM payments
    ") ?: [];

    $courseFormStats = $model->queryOne("
        SELECT
            COUNT(*) AS total_forms,
            COUNT(CASE WHEN approval_status IN ('pending', 'submitted') THEN 1 END) AS pending_forms,
            COUNT(CASE WHEN approval_status = 'approved' THEN 1 END) AS approved_forms,
            COUNT(CASE WHEN approval_status = 'rejected' THEN 1 END) AS rejected_forms
        FROM course_registered
    ") ?: [];

    $resultStats = $tableExists('result_sheets')
        ? ($model->queryOne("
            SELECT
                COUNT(*) AS total_sheets,
                COUNT(CASE WHEN moderation_status IN ('pending', 'submitted') THEN 1 END) AS pending_sheets,
                COUNT(CASE WHEN moderation_status = 'approved' THEN 1 END) AS approved_sheets,
                COUNT(CASE WHEN moderation_status = 'returned' THEN 1 END) AS returned_sheets,
                COUNT(CASE WHEN ca_status = 'submitted' THEN 1 END) AS ca_submitted,
                COUNT(CASE WHEN exam_status = 'submitted' THEN 1 END) AS exam_submitted
            FROM result_sheets
        ") ?: [])
        : [];

    $staffStats = [
        'total' => (int)$model->countRows('admins'),
        'active' => 0,
        'inactive' => 0
    ];
    if (isset($rbac) && $rbac->columnExists('admins', 'ix_active')) {
        $staffStats['active'] = (int)$model->countRows('admins', ['where' => ['ix_active' => 1]]);
        $staffStats['inactive'] = (int)$model->countRows('admins', ['where' => ['ix_active' => 0]]);
    }

    $admissionStats = [];
    if ($tableExists('admission_applications')) {
        $admissionStats = $model->queryOne("
            SELECT
                COUNT(*) AS total_applications,
                COUNT(CASE WHEN form_status IN ('Submitted','Pending Review') THEN 1 END) AS pending_screening,
                COUNT(CASE WHEN form_status IN ('Offered Admission','Accepted') THEN 1 END) AS admitted_candidates,
                COUNT(CASE WHEN form_status = 'Accepted' THEN 1 END) AS accepted_candidates
            FROM admission_applications
        ") ?: [];

        $recentAdmissionRows = $model->query("
            SELECT application_no, registration_no, form_status, submitted_at, created_at
            FROM admission_applications
            ORDER BY COALESCE(submitted_at, created_at) DESC, id DESC
            LIMIT 5
        ") ?: [];
    }

    $semesterJoin = '';
    $institutionParams = [];
    if (!empty($currentSession['id']) && !empty($currentSemester['id'])) {
        $semesterJoin = 'AND sr.session_id = :session_id AND sr.semester_id = :semester_id';
        $institutionParams = [
            'session_id' => $currentSession['id'],
            'semester_id' => $currentSemester['id']
        ];
    }

    $institutionRows = $model->query("
        SELECT
            i.id,
            i.name,
            COUNT(DISTINCT s.student_id) AS total_students,
            COUNT(DISTINCT CASE WHEN sr.payment_confirmed = 1 THEN s.student_id END) AS payment_confirmed,
            COUNT(DISTINCT CASE WHEN sr.courses_registered = 1 THEN s.student_id END) AS courses_registered
        FROM institutions i
        LEFT JOIN students s ON s.institution_id = i.id
        LEFT JOIN semesterregistration sr ON sr.student_id = s.student_id {$semesterJoin}
        GROUP BY i.id, i.name
        ORDER BY total_students DESC, i.name ASC
    ", $institutionParams) ?: [];

    $paymentTypeRows = $model->query("
        SELECT payment_type, COUNT(*) AS total_records, COALESCE(SUM(amount_paid), 0) AS total_amount
        FROM payments
        WHERE status = 'successful'
        GROUP BY payment_type
        ORDER BY total_amount DESC
        LIMIT 5
    ") ?: [];

    $recentResultRows = $tableExists('result_sheets')
        ? ($model->query("
            SELECT rs.ca_status, rs.exam_status, rs.moderation_status, rs.updated_at,
                c.course_code, c.course_title, a.fullname AS lecturer_name
            FROM result_sheets rs
            JOIN course_allocations ca ON ca.id = rs.course_allocation_id
            JOIN courses c ON c.id = ca.course_id
            JOIN lecturers l ON l.id = ca.lecturer_id
            JOIN admins a ON a.id = l.admin_id
            ORDER BY rs.updated_at DESC, rs.id DESC
            LIMIT 5
        ") ?: [])
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

    $dashboardLead = 'Control room for admissions, payments, registration, results, staff access, and academic setup.';
    $adminOverview = [
        ['label' => 'Total Students', 'value' => $totalStudents, 'hint' => 'Registered student profiles', 'icon' => 'ti ti-users', 'tone' => 'blue'],
        ['label' => 'Successful Collection', 'value' => $money($paymentStats['successful_amount'] ?? 0), 'hint' => number_format((int)($paymentStats['successful_records'] ?? 0)) . ' successful records', 'icon' => 'ti ti-currency-naira', 'tone' => 'green'],
        ['label' => 'Active Courses', 'value' => $totalCourses, 'hint' => $activeAllocations . ' active allocations', 'icon' => 'ti ti-book', 'tone' => 'teal'],
        ['label' => 'Open Reviews', 'value' => (int)($paymentStats['pending_records'] ?? 0) + (int)($courseFormStats['pending_forms'] ?? 0) + (int)($resultStats['pending_sheets'] ?? 0), 'hint' => 'Payments, course forms and result sheets', 'icon' => 'ti ti-alert-circle', 'tone' => 'amber']
    ];

    $adminFinancials = [
        ['label' => 'Today', 'value' => $money($paymentStats['today_amount'] ?? 0), 'hint' => 'Successful payments today'],
        ['label' => 'This Month', 'value' => $money($paymentStats['month_amount'] ?? 0), 'hint' => 'Successful payments this month'],
        ['label' => 'Pending Amount', 'value' => $money($paymentStats['pending_amount'] ?? 0), 'hint' => (int)($paymentStats['pending_records'] ?? 0) . ' pending records'],
        ['label' => 'Failed Records', 'value' => number_format((int)($paymentStats['failed_records'] ?? 0)), 'hint' => 'Payments requiring follow-up']
    ];

    $adminProgress = [
        ['label' => 'Receipt Upload', 'value' => $receiptUploaded, 'total' => $semesterTotal, 'percent' => $percent($receiptUploaded, $semesterTotal), 'tone' => 'amber'],
        ['label' => 'Payment Confirmation', 'value' => $paymentConfirmed, 'total' => $semesterTotal, 'percent' => $percent($paymentConfirmed, $semesterTotal), 'tone' => 'blue'],
        ['label' => 'Internet Fee Paid', 'value' => $courseFeePaid, 'total' => $semesterTotal, 'percent' => $percent($courseFeePaid, $semesterTotal), 'tone' => 'teal'],
        ['label' => 'Course Registration', 'value' => $coursesRegistered, 'total' => $semesterTotal, 'percent' => $percent($coursesRegistered, $semesterTotal), 'tone' => 'green']
    ];

    $adminAcademic = [
        ['label' => 'Institutions', 'value' => $totalInstitutions],
        ['label' => 'Programmes', 'value' => $totalProgrammes],
        ['label' => 'Departments', 'value' => $totalDepartments],
        ['label' => 'Levels', 'value' => $totalLevels],
        ['label' => 'Active Lecturers', 'value' => $activeLecturers],
        ['label' => 'Staff Users', 'value' => $staffStats['total']]
    ];

    $adminQueues = [
        ['label' => 'Course Forms Pending', 'value' => $courseFormStats['pending_forms'] ?? 0, 'page' => 'courseformMgr', 'icon' => 'ti ti-clipboard-list'],
        ['label' => 'Result Sheets Pending', 'value' => $resultStats['pending_sheets'] ?? 0, 'page' => 'courseAllocations', 'icon' => 'ti ti-file-text'],
        ['label' => 'Pending Payments', 'value' => $paymentStats['pending_records'] ?? 0, 'page' => 'payment_remark', 'icon' => 'ti ti-credit-card'],
        ['label' => 'Admission Screening', 'value' => $admissionStats['pending_screening'] ?? 0, 'page' => 'admissionApplications', 'icon' => 'ti ti-id-badge']
    ];

    $adminAttention = [
        ['label' => 'Payment reviews pending', 'value' => $paymentStats['pending_records'] ?? 0, 'page' => 'payment_remark', 'tone' => 'amber'],
        ['label' => 'Course forms awaiting action', 'value' => $courseFormStats['pending_forms'] ?? 0, 'page' => 'courseformMgr', 'tone' => 'blue'],
        ['label' => 'Result sheets awaiting moderation', 'value' => $resultStats['pending_sheets'] ?? 0, 'page' => 'courseAllocations', 'tone' => 'teal'],
        ['label' => 'Failed payment records', 'value' => $paymentStats['failed_records'] ?? 0, 'page' => 'payment_remark', 'tone' => 'red']
    ];
    if ($tableExists('admission_applications')) {
        $adminAttention[] = ['label' => 'Admission applications to screen', 'value' => $admissionStats['pending_screening'] ?? 0, 'page' => 'admissionApplications', 'tone' => 'green'];
    }

    $adminQuickActions = [
        ['label' => 'Review Payments', 'page' => 'payment_remark', 'icon' => 'ti ti-credit-card'],
        ['label' => 'Internet Payments', 'page' => 'internetPaymentReview', 'icon' => 'ti ti-wifi'],
        ['label' => 'Course Forms', 'page' => 'courseformMgr', 'icon' => 'ti ti-clipboard-list'],
        ['label' => 'Students', 'page' => 'students', 'icon' => 'ti ti-users'],
        ['label' => 'Course Allocation', 'page' => 'courseAllocations', 'icon' => 'ti ti-arrows-split'],
        ['label' => 'Announcements', 'page' => 'announcements', 'icon' => 'ti ti-speakerphone']
    ];
    if ($tableExists('admission_applications')) {
        $adminQuickActions[] = ['label' => 'Admissions', 'page' => 'admissionApplications', 'icon' => 'ti ti-id-badge'];
    }
    $adminQuickActions[] = ['label' => 'Staff Access', 'page' => 'staffUsers', 'icon' => 'ti ti-shield-lock'];

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

<?php if ($dashboardMode === 'admin'): ?>
    <div class="admin-dashboard">
        <div class="admin-dashboard-hero">
            <div>
                <span class="admin-dashboard-eyebrow">Super Admin Overview</span>
                <h3>Welcome back, <?= $escape($admin['fullname'] ?? 'Admin'); ?></h3>
                <p><?= $escape($dashboardLead); ?></p>
                <div class="admin-dashboard-pills">
                    <span><i class="ti ti-calendar"></i> <?= $escape($currentSession['name'] ?? 'No active session'); ?></span>
                    <span><i class="ti ti-clock"></i> <?= $escape($currentSemester['name'] ?? 'No active semester'); ?></span>
                    <span><i class="ti ti-shield-check"></i> <?= $escape(implode(', ', array_map('ucfirst', $adminRoles)) ?: 'Administrator'); ?></span>
                </div>
            </div>
            <div class="admin-dashboard-actions">
                <?php foreach (array_slice($adminQuickActions, 0, 4) as $action): ?>
                    <a href="<?= route($action['page'], $utility); ?>">
                        <i class="<?= $escape($action['icon']); ?>"></i>
                        <span><?= $escape($action['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($dashboardNotice !== ''): ?>
            <div class="alert alert-info mb-0">
                <i class="ti ti-info-circle me-1"></i> <?= $escape($dashboardNotice); ?>
            </div>
        <?php endif; ?>

        <div class="admin-kpi-grid">
            <?php foreach ($adminOverview as $card): ?>
                <div class="admin-kpi-card tone-<?= $escape($card['tone']); ?>">
                    <div class="admin-kpi-icon"><i class="<?= $escape($card['icon']); ?>"></i></div>
                    <div>
                        <p><?= $escape($card['label']); ?></p>
                        <strong><?= is_numeric($card['value']) ? number_format((float)$card['value']) : $escape($card['value']); ?></strong>
                        <span><?= $escape($card['hint']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card h-100 admin-dashboard-card">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h5 class="mb-0">Academic Period Health</h5>
                            <small class="text-muted"><?= $escape($currentSession['name'] ?? 'Session'); ?> / <?= $escape($currentSemester['name'] ?? 'Semester'); ?></small>
                        </div>
                        <span class="admin-dashboard-badge"><?= number_format($semesterTotal); ?> semester records</span>
                    </div>
                    <div class="card-body">
                        <div class="admin-progress-stack">
                            <?php foreach ($adminProgress as $item): ?>
                                <div class="admin-progress-row">
                                    <div class="d-flex justify-content-between gap-3">
                                        <span><?= $escape($item['label']); ?></span>
                                        <strong><?= number_format((int)$item['value']); ?> / <?= number_format((int)$item['total']); ?></strong>
                                    </div>
                                    <div class="admin-progress-track">
                                        <span class="tone-<?= $escape($item['tone']); ?>" style="width: <?= (int)$item['percent']; ?>%"></span>
                                    </div>
                                    <small><?= (int)$item['percent']; ?>% complete</small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card h-100 admin-dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">Needs Attention</h5>
                    </div>
                    <div class="card-body">
                        <div class="admin-attention-list">
                            <?php foreach ($adminAttention as $item): ?>
                                <a href="<?= route($item['page'], $utility); ?>" class="tone-<?= $escape($item['tone']); ?>">
                                    <span><?= $escape($item['label']); ?></span>
                                    <strong><?= number_format((int)$item['value']); ?></strong>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-4 col-lg-6">
                <div class="card h-100 admin-dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">Collections</h5>
                    </div>
                    <div class="card-body">
                        <div class="admin-finance-grid">
                            <?php foreach ($adminFinancials as $item): ?>
                                <div>
                                    <span><?= $escape($item['label']); ?></span>
                                    <strong><?= $escape($item['value']); ?></strong>
                                    <small><?= $escape($item['hint']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="admin-mini-list mt-3">
                            <?php foreach ($paymentTypeRows as $row): ?>
                                <div>
                                    <span><?= $escape(ucwords(str_replace('_', ' ', $row['payment_type'] ?? 'Payment'))); ?></span>
                                    <strong><?= $money($row['total_amount'] ?? 0); ?></strong>
                                    <small><?= number_format((int)($row['total_records'] ?? 0)); ?> records</small>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($paymentTypeRows)): ?>
                                <p class="text-muted mb-0">No successful payment breakdown yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-6">
                <div class="card h-100 admin-dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">Academic Structure</h5>
                    </div>
                    <div class="card-body">
                        <div class="admin-structure-grid">
                            <?php foreach ($adminAcademic as $item): ?>
                                <div>
                                    <strong><?= number_format((int)$item['value']); ?></strong>
                                    <span><?= $escape($item['label']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="admin-staff-strip">
                            <span>Active staff: <strong><?= number_format((int)$staffStats['active']); ?></strong></span>
                            <span>Inactive staff: <strong><?= number_format((int)$staffStats['inactive']); ?></strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card h-100 admin-dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">Work Queues</h5>
                    </div>
                    <div class="card-body">
                        <div class="admin-queue-list">
                            <?php foreach ($adminQueues as $item): ?>
                                <a href="<?= route($item['page'], $utility); ?>">
                                    <i class="<?= $escape($item['icon']); ?>"></i>
                                    <span><?= $escape($item['label']); ?></span>
                                    <strong><?= number_format((int)$item['value']); ?></strong>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-7">
                <div class="card h-100 admin-dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">Institution Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive admin-dashboard-table">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Institution</th>
                                        <th>Students</th>
                                        <th>Payment</th>
                                        <th>Registration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($institutionRows as $row): ?>
                                        <?php
                                        $studentsCount = (int)($row['total_students'] ?? 0);
                                        $paymentPct = $percent($row['payment_confirmed'] ?? 0, $studentsCount);
                                        $registrationPct = $percent($row['courses_registered'] ?? 0, $studentsCount);
                                        ?>
                                        <tr>
                                            <td><?= $escape($row['name'] ?? 'Institution'); ?></td>
                                            <td><?= number_format($studentsCount); ?></td>
                                            <td>
                                                <div class="admin-inline-progress">
                                                    <span style="width: <?= $paymentPct; ?>%"></span>
                                                </div>
                                                <small><?= $paymentPct; ?>%</small>
                                            </td>
                                            <td>
                                                <div class="admin-inline-progress tone-green">
                                                    <span style="width: <?= $registrationPct; ?>%"></span>
                                                </div>
                                                <small><?= $registrationPct; ?>%</small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($institutionRows)): ?>
                                        <tr><td colspan="4" class="text-center text-muted">No institution data found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card h-100 admin-dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="admin-action-grid">
                            <?php foreach ($adminQuickActions as $action): ?>
                                <a href="<?= route($action['page'], $utility); ?>">
                                    <i class="<?= $escape($action['icon']); ?>"></i>
                                    <span><?= $escape($action['label']); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-4 col-md-6">
                <div class="card h-100 admin-dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Students</h5>
                    </div>
                    <div class="card-body">
                        <div class="admin-feed-list">
                            <?php foreach ($primaryListRows as $row): ?>
                                <div>
                                    <span><?= $escape(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))); ?></span>
                                    <small><?= $escape($row['matric_no'] ?? 'N/A'); ?></small>
                                    <strong><?= $escape($formatDate($row['created_at'] ?? '')); ?></strong>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($primaryListRows)): ?>
                                <p class="text-muted mb-0">No recent students found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6">
                <div class="card h-100 admin-dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Payments</h5>
                    </div>
                    <div class="card-body">
                        <div class="admin-feed-list">
                            <?php foreach ($secondaryListRows as $row): ?>
                                <div>
                                    <span><?= $escape($row['paymentReference'] ?? 'TXN'); ?></span>
                                    <small><?= $escape(ucwords(str_replace('_', ' ', $row['payment_type'] ?? 'Payment'))); ?></small>
                                    <strong><?= $money($row['amount_paid'] ?? 0); ?></strong>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($secondaryListRows)): ?>
                                <p class="text-muted mb-0">No recent payments found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card h-100 admin-dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Result Sheets</h5>
                    </div>
                    <div class="card-body">
                        <div class="admin-feed-list">
                            <?php foreach ($recentResultRows as $row): ?>
                                <div>
                                    <span><?= $escape(($row['course_code'] ?? '') . ' - ' . ($row['course_title'] ?? '')); ?></span>
                                    <small><?= $escape($row['lecturer_name'] ?? ''); ?></small>
                                    <strong><?= $escape(ucfirst($row['moderation_status'] ?? 'pending')); ?></strong>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($recentResultRows)): ?>
                                <p class="text-muted mb-0">No result sheets found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($tableExists('admission_applications')): ?>
            <div class="row g-3">
                <div class="col-xl-4">
                    <div class="card h-100 admin-dashboard-card">
                        <div class="card-header">
                            <h5 class="mb-0">Admissions</h5>
                        </div>
                        <div class="card-body">
                            <div class="admin-structure-grid admin-structure-grid-compact">
                                <div><strong><?= number_format((int)($admissionStats['total_applications'] ?? 0)); ?></strong><span>Total</span></div>
                                <div><strong><?= number_format((int)($admissionStats['pending_screening'] ?? 0)); ?></strong><span>Pending</span></div>
                                <div><strong><?= number_format((int)($admissionStats['admitted_candidates'] ?? 0)); ?></strong><span>Admitted</span></div>
                                <div><strong><?= number_format((int)($admissionStats['accepted_candidates'] ?? 0)); ?></strong><span>Accepted</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-8">
                    <div class="card h-100 admin-dashboard-card">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Admission Applications</h5>
                        </div>
                        <div class="card-body">
                            <div class="admin-feed-list admin-feed-list-two-col">
                                <?php foreach ($recentAdmissionRows as $row): ?>
                                    <div>
                                        <span><?= $escape($row['application_no'] ?? $row['registration_no'] ?? 'Application'); ?></span>
                                        <small><?= $escape(ucfirst($row['form_status'] ?? 'Draft')); ?></small>
                                        <strong><?= $escape($formatDate($row['submitted_at'] ?: ($row['created_at'] ?? ''))); ?></strong>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (empty($recentAdmissionRows)): ?>
                                    <p class="text-muted mb-0">No recent admission applications found.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
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
                                <h4 class="mb-0"><?= is_numeric($card['value'] ?? 0) ? number_format((float)($card['value'] ?? 0)) : $escape($card['value'] ?? 0); ?></h4>
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
                            <?php elseif ($dashboardMode === 'registry'): ?>
                                <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                                    <span>
                                        <?= $escape(trim(($row['first_name'] ?? '') . ' ' . ($row['other_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))); ?>
                                        <br><small class="text-muted"><?= $escape($row['matric_no'] ?? 'N/A'); ?></small>
                                    </span>
                                    <span class="text-end">
                                        <small class="text-muted"><?= $escape($formatDate($row['created_at'] ?? '')); ?></small>
                                    </span>
                                </div>
                            <?php elseif ($dashboardMode === 'bursary'): ?>
                                <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                                    <span>
                                        <?= $escape($row['paymentReference'] ?? 'TXN'); ?>
                                        <br><small class="text-muted"><?= $escape(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: ($row['matric_no'] ?? 'Student')); ?></small>
                                    </span>
                                    <span class="text-end">
                                        <strong><?= $money($row['amount_paid'] ?? 0); ?></strong>
                                        <br><small class="text-muted"><?= $escape(ucwords(str_replace('_', ' ', $row['payment_type'] ?? 'Payment'))); ?></small>
                                    </span>
                                </div>
                            <?php else: ?>
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
                            <?php elseif ($dashboardMode === 'registry'): ?>
                                <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                                    <span>
                                        <?= $escape(trim(($row['first_name'] ?? '') . ' ' . ($row['other_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))); ?>
                                        <br><small class="text-muted"><?= $escape(($row['matric_no'] ?? '') . ' / ' . ($row['department_name'] ?? '') . ' / ' . ($row['level_name'] ?? '')); ?></small>
                                    </span>
                                    <span class="text-end">
                                        <span class="badge bg-info"><?= $escape(ucfirst($row['approval_status'] ?? 'pending')); ?></span>
                                        <br><small class="text-muted"><?= $escape($formatDate($row['created_at'] ?? '')); ?></small>
                                    </span>
                                </div>
                            <?php elseif ($dashboardMode === 'bursary'): ?>
                                <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                                    <span>
                                        <?= $escape($row['paymentReference'] ?? 'TXN'); ?>
                                        <br><small class="text-muted"><?= $escape(ucwords(str_replace('_', ' ', $row['payment_type'] ?? 'Payment'))); ?></small>
                                    </span>
                                    <span class="text-end">
                                        <strong><?= $money($row['amount_paid'] ?? 0); ?></strong>
                                        <br><span class="badge bg-<?= ($row['status'] ?? '') === 'successful' ? 'success' : (($row['status'] ?? '') === 'failed' ? 'danger' : 'warning'); ?>"><?= $escape(ucfirst($row['status'] ?? 'pending')); ?></span>
                                    </span>
                                </div>
                            <?php else: ?>
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
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">No records found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
