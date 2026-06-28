<?php
require_once '../../start.inc.php';
require_once '../query.php';

$editCoursePage = 'editCourseRegistration';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithToast('error', 'Invalid request method', 'studentDashboard');
    exit;
}

/* ===================== */
/* CSRF VALIDATION */
/* ===================== */

if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'editcourses')) {
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
        'student_id'  => $studentId,
        'semester_id' => $semester,
        'session_id'  => $session
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
        'Your current semester level has not been set. Please confirm your current level before editing your course form.',
        $editCoursePage
    );
    exit;
}

/* ===================== */
/* ENFORCE LEVEL ALIGNMENT */
/* ===================== */

if ($studentLevelId !== $semesterRegLevelId) {
    redirectWithToast(
        'error',
        'Your profile level and semester registration level do not match. Please confirm your current level before editing your course form.',
        $editCoursePage
    );
    exit;
}

/*
    At this point, both tables agree.
    This is the only level allowed for this update.
*/
$confirmedLevelId = $semesterRegLevelId;

/* ===================== */
/* FETCH EXISTING COURSE REGISTRATION */
/* ===================== */

$reg = $model->getRows('course_registered', [
    'where' => [
        'student_id' => $studentId,
        'semester'   => $semester,
        'session'    => $session
    ],
    'return_type' => 'single'
]);

if (empty($reg)) {
    redirectWithToast(
        'error',
        'No course registration found for this semester. Please register your courses first.',
        'courseRegistration'
    );
    exit;
}

$approvalStatus = strtolower(trim($reg['approval_status'] ?? ''));

if (!in_array($approvalStatus, ['submitted', 'rejected'], true)) {
    redirectWithToast('error', 'Edit not allowed for this course form.', 'studentDashboard');
    exit;
}

$courseRegID = (int) ($reg['course_regID'] ?? 0);

if (empty($courseRegID)) {
    redirectWithToast('error', 'Invalid course registration record.', 'studentDashboard');
    exit;
}

/* ===================== */
/* GET POSTED COURSES */
/* ===================== */

$postedCourses = $_POST['courses'] ?? [];

if (!is_array($postedCourses)) {
    redirectWithToast('error', 'Invalid course selection.', $editCoursePage);
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
    Force core courses into the final course selection.

    This protects against:
    - disabled checkboxes not being submitted
    - hidden inputs being removed
    - students tampering with the frontend
*/
$courses = array_values(array_unique(array_merge($postedCourses, $expectedCoreIds)));

if (empty($courses)) {
    redirectWithToast('error', 'No courses selected.', $editCoursePage);
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
    redirectWithToast(
        'error',
        'Selected courses could not be found for your confirmed level and semester.',
        $editCoursePage
    );
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
        $editCoursePage
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
        $editCoursePage
    );
    exit;
}

/* ===================== */
/* CORE COURSE ENFORCEMENT */
/* ===================== */

$selectedCoreIds = array_map('intval', array_column($coreCourses, 'id'));

$missingCore = array_diff($expectedCoreIds, $selectedCoreIds);

if (!empty($missingCore)) {
    redirectWithToast('error', 'All core courses must be registered.', $editCoursePage);
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
        $editCoursePage
    );
    exit;
}

/* ===================== */
/* UPDATE COURSE FORM */
/* ===================== */

$model->beginTransaction();

try {

    /* ===================== */
    /* DELETE OLD REGISTERED COURSES */
    /* ===================== */

    $model->delete('registered_course', [
        'course_regID' => $courseRegID
    ]);

    /* ===================== */
    /* INSERT NEW REGISTERED COURSES */
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
    /* UPDATE PARENT REGISTRATION */
    /* ===================== */

    $updated = $model->update(
        'course_registered',
        [
            'total_units'              => $totalUnits,
            'semester_registration_id' => $semesterRegistration['id'],
            'approval_status'          => 'pending'
        ],
        [
            'course_regID' => $courseRegID
        ]
    );

    /*
        Depending on your model class, update() may return false, 0, or affected rows.
        So we do not strictly throw error here unless your model definitely returns false on failure.
    */

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
            'student_id'  => $studentId,
            'semester_id' => $semester,
            'session_id'  => $session
        ]
    );

    $model->commit();

    $utility->logActivityUsers(
        'Successfully updated course form for student with user ID: ' . $studentId,
        $_SESSION['user_email'] ?? 'Unknown'
    );

    redirectWithToast(
        'success',
        'Courses updated successfully. Your course form is now pending review.',
        'studentDashboard'
    );
    exit;

} catch (Exception $e) {

    $model->rollBack();

    redirectWithToast(
        'error',
        'Update failed. Reason: ' . $e->getMessage(),
        $editCoursePage
    );
    exit;
}