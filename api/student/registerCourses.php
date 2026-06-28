<?php
require_once '../../start.inc.php';
require_once '../query.php';

$courseRegistrationPage = 'courseRegistration';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithToast('error', 'Invalid request method', 'studentDashboard');
    exit;
}

/* ===================== */
/* CSRF VALIDATION */
/* ===================== */

if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'courseformpayment')) {
    redirectWithToast('error', 'Unauthorized access', 'studentDashboard');
    exit;
}

$studentId = $_SESSION['user_id'] ?? null;
$semester  = (int) ($activeSemester['id'] ?? 0);
$session   = (int) ($activeSession['id'] ?? 0);

if (empty($studentId) || empty($semester) || empty($session)) {
    redirectWithToast('error', 'Invalid student, semester, or session information.', 'studentDashboard');
    exit;
}

/* ===================== */
/* FETCH STUDENT */
/* ===================== */

$student = $model->getRows('students', [
    'where' => [
        'student_id' => $studentId
    ],
    'return_type' => 'single'
]);

if (empty($student)) {
    redirectWithToast('error', 'Student record not found.', 'studentDashboard');
    exit;
}

$studentLevelId = (int) ($student['level_id'] ?? 0);

if (empty($studentLevelId)) {
    redirectWithToast('error', 'Student level not found on your profile. Please contact the Registry.', 'studentDashboard');
    exit;
}

/* ===================== */
/* FETCH SEMESTER REGISTRATION */
/* ===================== */

$semesterRegistration = $model->getRows('semesterregistration', [
    'where' => [
        'student_id'   => $studentId,
        'semester_id'  => $semester,
        'session_id'   => $session
    ],
    'return_type' => 'single'
]);

if (empty($semesterRegistration)) {
    redirectWithToast('error', 'Semester registration record not found.', 'studentDashboard');
    exit;
}

$semesterRegLevelId = (int) ($semesterRegistration['studentLevelId'] ?? 0);

if (empty($semesterRegLevelId)) {
    redirectWithToast(
        'error',
        'Your current semester level has not been set. Please confirm your current level before registering courses.',
        $courseRegistrationPage
    );
    exit;
}

/* ===================== */
/* ENFORCE LEVEL ALIGNMENT */
/* ===================== */

if ($studentLevelId !== $semesterRegLevelId) {
    redirectWithToast(
        'error',
        'Your profile level and semester registration level do not match. Please confirm your current level before registering courses.',
        $courseRegistrationPage
    );
    exit;
}

/*
    At this point, both tables agree.
    This is the confirmed level to use for course registration.
*/
$confirmedLevelId = $semesterRegLevelId;

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
    redirectWithToast('error', 'You have already registered courses for this semester.', 'studentDashboard');
    exit;
}

/* ===================== */
/* GET SELECTED COURSES FROM POST */
/* ===================== */

$postedCourses = $_POST['courses'] ?? [];

if (!is_array($postedCourses)) {
    redirectWithToast('error', 'Invalid course selection.', 'studentDashboard');
    exit;
}

$postedCourses = array_filter($postedCourses, function ($id) {
    return is_numeric($id);
});

$postedCourses = array_map('intval', $postedCourses);
$postedCourses = array_values(array_unique($postedCourses));

/* ===================== */
/* FETCH EXPECTED CORE COURSES */
/* ===================== */

$expectedCore = $model->getRows(
    "courses 
     WHERE level_id = {$confirmedLevelId}
     AND semester_id = {$semester}
     AND course_status = 1
     AND LOWER(TRIM(course_type)) = 'core'"
);

$expectedCoreIds = array_map('intval', array_column($expectedCore, 'id'));

/*
    Merge compulsory core courses into the submitted courses.

    This protects the backend even if:
    - disabled checkboxes are not submitted
    - hidden inputs are removed
    - the browser form is tampered with
*/
$courses = array_values(array_unique(array_merge($postedCourses, $expectedCoreIds)));

if (empty($courses)) {
    redirectWithToast('error', 'No courses selected.', 'studentDashboard');
    exit;
}

/* ===================== */
/* FETCH VALID SELECTED COURSES ONLY */
/* ===================== */

$ids = implode(',', array_map('intval', $courses));

$selectedCourses = $model->getRows(
    "courses 
     WHERE id IN ($ids)
     AND level_id = {$confirmedLevelId}
     AND semester_id = {$semester}
     AND course_status = 1"
);

if (empty($selectedCourses)) {
    redirectWithToast('error', 'Selected courses could not be found for your current level and semester.', 'studentDashboard');
    exit;
}

/* ===================== */
/* DETECT INVALID COURSE IDS */
/* ===================== */

$returnedCourseIds = array_map('intval', array_column($selectedCourses, 'id'));

$invalidCourseIds = array_diff($courses, $returnedCourseIds);

if (!empty($invalidCourseIds)) {
    redirectWithToast(
        'error',
        'Invalid course selection detected. Please refresh the page and try again.',
        'studentDashboard'
    );
    exit;
}

/* ===================== */
/* GROUP + CALCULATE UNITS */
/* ===================== */

$totalUnits = 0;
$coreCourses = [];
$electiveCourses = [];

foreach ($selectedCourses as $course) {

    $totalUnits += (int) ($course['unit'] ?? 0);

    $courseType = strtolower(trim($course['course_type'] ?? ''));

    if ($courseType === 'core') {
        $coreCourses[] = $course;
    } else {
        $electiveCourses[] = $course;
    }
}

/* ===================== */
/* MAX UNIT VALIDATION */
/* ===================== */

$maxUnits = 40;

if ($totalUnits > $maxUnits) {
    redirectWithToast(
        'error',
        "Total course units cannot exceed $maxUnits. You selected $totalUnits units.",
        'studentDashboard'
    );
    exit;
}

/* ===================== */
/* CORE COURSE ENFORCEMENT */
/* ===================== */

$selectedCoreIds = array_map('intval', array_column($coreCourses, 'id'));

$missingCore = array_diff($expectedCoreIds, $selectedCoreIds);

if (!empty($missingCore)) {
    redirectWithToast('error', 'All core courses must be registered.', 'studentDashboard');
    exit;
}

/* ===================== */
/* ELECTIVE LIMIT */
/* ===================== */

$maxElectives = 2;

if (count($electiveCourses) > $maxElectives) {
    redirectWithToast(
        'error',
        "You can only select a maximum of $maxElectives elective courses.",
        'studentDashboard'
    );
    exit;
}

/* ===================== */
/* SAVE DATA */
/* ===================== */

$model->beginTransaction();

try {

    /* ===================== */
    /* INSERT MAIN REGISTRATION */
    /* ===================== */

    $courseRegID = $model->insert_data('course_registered', [
        'student_id'                => $studentId,
        'semester_registration_id'  => $semesterRegistration['id'],
        'semester'                  => $semester,
        'session'                   => $session,
        'total_units'               => $totalUnits,
        'approval_status'           => 'submitted'
    ]);

    if (!$courseRegID) {
        throw new Exception('Failed to create registration record.');
    }

    /* ===================== */
    /* INSERT REGISTERED COURSES */
    /* ===================== */

    foreach ($selectedCourses as $course) {

        $saved = $model->insert_data('registered_course', [
            'course_regID' => $courseRegID,
            'course_id'    => $course['id']
        ]);

        if (!$saved) {
            throw new Exception('Failed to insert course: ' . $course['id']);
        }
    }

    /* ===================== */
    /* UPDATE SEMESTER REGISTRATION */
    /* ===================== */

    $model->update(
        'semesterregistration',
        [
            'courses_registered' => 1,
            'registered_at'      => date('Y-m-d H:i:s'),
            'studentLevelId'     => $confirmedLevelId
        ],
        [
            'student_id'   => $studentId,
            'session_id'   => $session,
            'semester_id'  => $semester
        ]
    );

    $model->commit();

    $utility->logActivityUsers(
        'Successfully registered courses for student with user ID: ' . $studentId,
        $_SESSION['user_email'] ?? 'Unknown'
    );

    redirectWithToast(
        'success',
        'Course Registration Form saved successfully. Click on Edit Course Form to submit finally.',
        'studentDashboard'
    );
    exit;
} catch (Exception $e) {

    $model->rollBack();

    redirectWithToast(
        'error',
        'Registration failed. Reason: ' . $e->getMessage(),
        'studentDashboard'
    );
    exit;
}
