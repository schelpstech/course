<?php
require_once '../start.inc.php';
require_once '../api/adminQuery.php';

// ==========================
// DEFAULT PAGE
// ==========================
$pageId = 'adminlogin';

// ==========================
// DECODE PAGE
// ==========================
if (!empty($_GET['pageid'])) {
    $decoded = $utility->secureDecode($_GET['pageid']);

    if ($decoded) {
        if (is_array($decoded)) {
            $pageId = $decoded['page'] ?? 'adminlogin';
        } else {
            $pageId = $decoded;
        }
    }
}

// ==========================
// ADMIN ROUTES CONFIG
// ==========================
$navigationSettings = [

    // ======================
    // AUTH MODULE
    // ======================
    'adminlogin' => [
        'type' => 'public',
        'module' => 'Authentication',
        'title' => 'Admin Login',
        'description' => 'Access the admin control panel'
    ],

    // ======================
    // DASHBOARD MODULE
    // ======================
    'adminDashboard' => [
        'type' => 'private',
        'module' => 'Dashboard',
        'title' => 'Dashboard',
        'description' => 'Overview of system activities and statistics'
    ],

    // ======================
    // ACADEMIC STRUCTURE
    // ======================
    'institutions' => [
        'type' => 'private',
        'module' => 'Academic Structure',
        'title' => 'Institutions',
        'description' => 'Manage all institutions',
        'permission' => 'manage_institutions'
    ],

    'programs' => [
        'type' => 'private',
        'module' => 'Academic Structure',
        'title' => 'Programs',
        'description' => 'Manage academic programs',
        'permission' => 'manage_institutions'
    ],

    'departments' => [
        'type' => 'private',
        'module' => 'Academic Structure',
        'title' => 'Departments',
        'description' => 'Manage departments',
        'permission' => 'manage_departments'
    ],

    'manageLevels' => [
        'type' => 'private',
        'module' => 'Academic Structure',
        'title' => 'Levels',
        'description' => 'Manage academic levels',
        'permission' => 'manage_departments'
    ],

    'academicSessions' => [
        'type' => 'private',
        'module' => 'Academic Structure',
        'title' => 'Sessions',
        'description' => 'Manage academic sessions',
        'permission' => 'manage_institutions'
    ],

    'manageSemesters' => [
        'type' => 'private',
        'module' => 'Academic Structure',
        'title' => 'Semesters',
        'description' => 'Manage semesters',
        'permission' => 'manage_institutions'
    ],

    // ======================
    // STUDENT MANAGEMENT
    // ======================
    'students' => [
        'type' => 'private',
        'module' => 'Student Management',
        'title' => 'Students',
        'description' => 'Manage student records',
        'permission' => 'view_students'
    ],

    'studentView' => [
        'type' => 'private',
        'module' => 'Student Management',
        'title' => 'Student Profile',
        'description' => 'View student details'
    ],

    // ======================
    // COURSE MANAGEMENT
    // ======================
    'courses' => [
        'type' => 'private',
        'module' => 'Course Management',
        'title' => 'Courses',
        'description' => 'Manage course catalog',
        'permission' => 'manage_courses'
    ],


    'courseformMgr' => [
        'type' => 'private',
        'module' => 'Course Management',
        'title' => 'Course Forms',
        'description' => 'Manage student course registrations',
        'permission' => 'view_course_forms'
    ],

    // ======================
    // DEPARTMENT PORTAL
    // ======================
    'departmentDashboard' => [
        'type' => 'private',
        'module' => 'Department Portal',
        'title' => 'Department Dashboard',
        'description' => 'Department-level academic operations',
        'requires_department_scope' => true,
        'permissions' => ['view_department_students', 'view_course_forms', 'manage_dept_courses', 'allocate_dept_courses', 'moderate_results', 'approve_results']
    ],

    'departmentStudents' => [
        'type' => 'private',
        'module' => 'Department Portal',
        'title' => 'Department Students',
        'description' => 'View students in your assigned department',
        'requires_department_scope' => true,
        'permissions' => ['view_department_students', 'view_students']
    ],

    'departmentCourseForms' => [
        'type' => 'private',
        'module' => 'Department Portal',
        'title' => 'Department Course Forms',
        'description' => 'Review student course forms in your department',
        'requires_department_scope' => true,
        'permission' => 'view_course_forms'
    ],

    'departmentCourses' => [
        'type' => 'private',
        'module' => 'Department Portal',
        'title' => 'Department Courses',
        'description' => 'Create and maintain department courses',
        'requires_department_scope' => true,
        'permission' => 'manage_dept_courses'
    ],

    'departmentCourseAllocations' => [
        'type' => 'private',
        'module' => 'Department Portal',
        'title' => 'Department Course Allocation',
        'description' => 'Allocate department courses to lecturers',
        'requires_department_scope' => true,
        'permission' => 'allocate_dept_courses'
    ],

    'departmentModeration' => [
        'type' => 'private',
        'module' => 'Department Portal',
        'title' => 'Department Result Moderation',
        'description' => 'Review, return, reject and approve department result sheets',
        'requires_department_scope' => true,
        'permissions' => ['moderate_results', 'approve_results']
    ],

    // ======================
    // STAFF & ACCESS
    // ======================
    'staffUsers' => [
        'type' => 'private',
        'module' => 'Staff & Access',
        'title' => 'Staff Users',
        'description' => 'Create and manage staff/admin users',
        'permission' => 'manage_admin_users'
    ],

    'rolesPermissions' => [
        'type' => 'private',
        'module' => 'Staff & Access',
        'title' => 'Roles & Permissions',
        'description' => 'Configure access roles and permissions',
        'permission' => 'manage_roles'
    ],

    // ======================
    // RESULTS
    // ======================
    'courseAllocations' => [
        'type' => 'private',
        'module' => 'Results',
        'title' => 'Course Allocation',
        'description' => 'Allocate courses to lecturers',
        'permission' => 'allocate_courses'
    ],

    'resultConfig' => [
        'type' => 'private',
        'module' => 'Results',
        'title' => 'Result Configuration',
        'description' => 'Configure score entry and publication',
        'permission' => 'create_result_config'
    ],

    'gradingRules' => [
        'type' => 'private',
        'module' => 'Results',
        'title' => 'Grading Rules',
        'description' => 'Configure grading rules',
        'permission' => 'manage_grading_rules'
    ],

    'lecturerDashboard' => [
        'type' => 'private',
        'module' => 'Lecturer Portal',
        'title' => 'Lecturer Dashboard',
        'description' => 'Overview of allocated courses and submissions',
        'permissions' => ['view_results', 'enter_ca_scores', 'enter_exam_scores', 'submit_scores']
    ],

    'lecturerScoresheet' => [
        'type' => 'private',
        'module' => 'Lecturer Portal',
        'title' => 'Scoresheet',
        'description' => 'Class list, CA, exam and submitted score sheet',
        'permissions' => ['enter_ca_scores', 'enter_exam_scores', 'submit_scores']
    ],

    // ======================
    // PAYMENT MANAGEMENT
    // ======================
    'payment_assign' => [
        'type' => 'private',
        'module' => 'Payments',
        'title' => 'Assign Payments',
        'description' => 'Assign fees to students',
        'permission' => 'manage_payments'
    ],

    'payment_config' => [
        'type' => 'private',
        'module' => 'Payments',
        'title' => 'Payment Configuration',
        'description' => 'Configure payment settings',
        'permission' => 'manage_payments'
    ],

    'payment_remark' => [
        'type' => 'private',
        'module' => 'Payments',
        'title' => 'Payment Remarks',
        'description' => 'Manage payment remarks',
        'permission' => 'manage_payments'
    ],

    'internetPaymentReview' => [
        'type' => 'private',
        'module' => 'Payments',
        'title' => 'Payment Review',
        'description' => 'Review online payments',
        'permission' => 'manage_payments'
    ],

    // ======================
    // REGISTRATION
    // ======================
    'registrations' => [
        'type' => 'private',
        'module' => 'Registration',
        'title' => 'Registrations',
        'description' => 'Manage course registrations',
        'permission' => 'view_course_forms'
    ],

    'semregistrationStatus' => [
        'type' => 'private',
        'module' => 'Registration',
        'title' => 'Registration Status',
        'description' => 'Monitor registration status',
        'permission' => 'view_students'
    ],

    // ======================
    // SECURITY & AUDIT
    // ======================
    'change-password' => [
        'type' => 'private',
        'module' => 'Security',
        'title' => 'Change Password',
        'description' => 'Update admin password'
    ],

    'audit-trail' => [
        'type' => 'private',
        'module' => 'Security',
        'title' => 'Audit Trail',
        'description' => 'View system activity logs',
        'permission' => 'view_audit_logs'
    ],

    'student-trail' => [
        'type' => 'private',
        'module' => 'Security',
        'title' => 'Student Activity',
        'description' => 'Track student actions',
        'permission' => 'view_audit_logs'
    ],

    // ======================
    // SEMESTER CLEARANCE
    // ======================
    'manage_clearance' => [
        'type' => 'private',
        'module' => 'Semester Clearance',
        'title' => 'Manage Clearance',
        'description' => 'Manage semester clearance requirements',
        'permission' => 'manage_payments'
    ],

    'payment_clearance' => [
        'type' => 'private',
        'module' => 'Semester Clearance',
        'title' => 'Payment Clearance',
        'description' => 'Manage payment clearance requirements',
        'permission' => 'manage_payments'
    ],

    // ======================
    // ADMISSION
    // ======================
    'admissionDashboard' => [
        'type' => 'private',
        'module' => 'Admission',
        'title' => 'Admission Dashboard',
        'description' => 'Monitor admission activity',
        'permission' => 'manage_admission'
    ],

    'admissionSessions' => [
        'type' => 'private',
        'module' => 'Admission',
        'title' => 'Admission Sessions',
        'description' => 'Configure online admission sessions',
        'permission' => 'manage_admission'
    ],

    'admissionApplications' => [
        'type' => 'private',
        'module' => 'Admission',
        'title' => 'Admission Applications',
        'description' => 'Screen and process admission applications',
        'permission' => 'manage_admission'
    ],

    'admissionCriteria' => [
        'type' => 'private',
        'module' => 'Admission',
        'title' => 'Admission Criteria',
        'description' => 'Configure programme admission criteria',
        'permission' => 'manage_admission'
    ],
];

// ==========================
// FALLBACK
// ==========================
if (!isset($navigationSettings[$pageId])) {
    $pageId = 'adminlogin';
}

$route = $navigationSettings[$pageId];

// ==========================
// AUTH CHECK
// ==========================
if ($route['type'] === 'private' && !isset($_SESSION['admin_id'])) {
    $pageId = 'adminlogin';
    $route = $navigationSettings[$pageId];
}

if (
    $route['type'] === 'private' &&
    isset($_SESSION['admin_id']) &&
    isset($rbac) &&
    (
        (!empty($route['permission']) && !$rbac->can($route['permission'])) ||
        (!empty($route['permissions']) && !$rbac->canAny($route['permissions'])) ||
        (!empty($route['requires_department_scope']) && !$rbac->departmentScopeId())
    )
) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'You do not have permission to access that page.'
    ];

    $pageId = 'adminDashboard';
    $route = $navigationSettings[$pageId];
}



// ==========================
// PREVENT LOGIN LOOP
// ==========================
if ($pageId === 'adminlogin' && isset($_SESSION['admin_id'])) {
    $pageId = 'adminDashboard';
    $route = $navigationSettings[$pageId];
}

// ==========================
// PREPARE PAGE DATA
// ==========================
$pageTitle = $route['title'] ?? 'Admin Panel';
$pageDescription = $route['description'] ?? '';
$pageModule = $route['module'] ?? 'General';

// ==========================
// ROUTING
// ==========================

// LOGIN PAGE
if ($pageId === 'adminlogin') {
    header("Location: ../console.php");
    exit;
}

// STORE CURRENT PAGE
$_SESSION['admin_pageid'] = $pageId;

// ==========================
// PASS PAYLOAD TO VIEWER
// ==========================
$payload = [
    'page' => $pageId,
    'title' => $pageTitle,
    'description' => $pageDescription,
    'module' => $pageModule
];

header("Location: ../view/adminviewer.php?pageid=" . $utility->secureEncode($payload));
exit;
