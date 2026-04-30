<?php
require_once '../../start.inc.php';
require_once '../query.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithToast('error', 'Invalid request method', 'studentDashboard');
    exit;
}

// CSRF
if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'courseformpayment')) {
    redirectWithToast('error', 'Unauthorized access', 'studentDashboard');
    exit;
}

$studentId = $_SESSION['user_id'];
$courses   = $_POST['courses'] ?? [];
$semester  = $activeSemester['id'];
$session   = $activeSession['id'];

/* ===================== */
/* VALIDATION */
/* ===================== */

if (empty($courses)) {
    redirectWithToast('error', 'No courses selected', 'studentDashboard');
    exit;
}

/* ===================== */
/* SANITIZE INPUT */
/* ===================== */

// remove duplicates
$courses = array_unique($courses);

// ensure numeric
$courses = array_filter($courses, function($id){
    return is_numeric($id);
});

if (empty($courses)) {
    redirectWithToast('error', 'Invalid course selection', 'studentDashboard');
    exit;
}

/* ===================== */
/* PREVENT DUPLICATE REGISTRATION */
/* ===================== */

$exists = $model->getRows('course_registered', [
    'where' => [
        'student_id' => $studentId,
        'semester'   => $semester,
        'session'    => $session
    ],
    'return_type' => 'count'
]);

if ($exists > 0) {
    redirectWithToast('error', 'You have already registered for this semester.', 'studentDashboard');
    exit;
}

/* ===================== */
/* FETCH COURSES (OPTIMIZED) */
/* ===================== */

$ids = implode(',', array_map('intval', $courses));

$selectedCourses = $model->getRows("courses WHERE id IN ($ids)");

if (empty($selectedCourses)) {
    redirectWithToast('error', 'Courses not found', 'studentDashboard');
    exit;
}

/* ===================== */
/* GROUP + CALCULATE */
/* ===================== */

$totalUnits = 0;
$coreCourses = [];
$electiveCourses = [];

foreach ($selectedCourses as $course) {

    $totalUnits += (int)$course['unit'];

    if ($course['course_type'] === 'core') {
        $coreCourses[] = $course;
    } else {
        $electiveCourses[] = $course;
    }
}

/* ===================== */
/* MAX UNIT VALIDATION */
/* ===================== */

$maxUnits = 30;

if ($totalUnits > $maxUnits) {
    redirectWithToast('error', "Total course units cannot exceed $maxUnits. You selected $totalUnits units.", 'studentDashboard');
    exit;
}

/* ===================== */
/* CORE COURSE ENFORCEMENT */
/* ===================== */

$user = $model->getRows('students', [
    'where' => ['student_id' => $studentId],
    'return_type' => 'single'
]);

$expectedCore = $model->getRows('courses', [
    'where' => [
        'level_id'      => $user['level_id'],
        'semester_id'      => $semester,
        'course_type'   => 'core',
        'course_status' => 1
    ]
]);

$expectedCoreIds = array_column($expectedCore, 'id');
$selectedCoreIds = array_column($coreCourses, 'id');

$missingCore = array_diff($expectedCoreIds, $selectedCoreIds);

if (!empty($missingCore)) {
    redirectWithToast('error', 'All core courses must be registered.', 'studentDashboard');
    exit;
}

/* ===================== */
/* ELECTIVE LIMIT */
/* ===================== */

$maxElectives = 2; // 🔧 adjust as needed

if (count($electiveCourses) > $maxElectives) {
    redirectWithToast('error', "You can only select a maximum of $maxElectives elective courses.", 'studentDashboard');
    exit;
}

/* ===================== */
/* SAVE DATA */
/* ===================== */

$model->beginTransaction();

try {

    // ✅ 1. INSERT MAIN REGISTRATION
    $courseRegID = $model->insert_data('course_registered', [
        'student_id'  => $studentId,
        'semester'    => $semester,
        'session'     => $session,
        'total_units' => $totalUnits,
        'approval_status' => 'submitted'
    ]);

    if (!$courseRegID) {
        throw new Exception("Failed to create registration record");
    }

    // ✅ 2. INSERT COURSES
    foreach ($selectedCourses as $course) {

        $saved = $model->insert_data('registered_course', [
            'course_regID' => $courseRegID,
            'course_id'    => $course['id']
        ]);

        if (!$saved) {
            throw new Exception("Failed to insert course: ".$course['id']);
        }
    }

    /* ===================== */
    /* UPDATE SEMESTER REGISTRATION */
    /* ===================== */
    $model->update(
        "semesterregistration",
        [
            "courses_registered" => 1,
            "registered_at" => date('Y-m-d H:i:s')
        ],
        [
            "student_id" => $studentId,
            "session_id" => $session,
            "semester_id" => $semester
        ]
    );

    // ✅ COMMIT
    $model->commit();
    $utility->logActivityUsers('Successfully registered courses for student with user ID: ' . $studentId, $_SESSION['user_email'] ?? 'Unknown');
    redirectWithToast('success', 'Course Registration Form Saved successfully, Click on Edit Course Form to Submit Finally', 'studentDashboard');

} catch (Exception $e) {

    $model->rollBack();

    redirectWithToast('error', 'Registration failed. Reason: '.$e->getMessage(), 'studentDashboard');
}