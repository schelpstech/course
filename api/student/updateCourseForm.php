<?php
require_once '../../start.inc.php';
require_once '../query.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithToast('error', 'Invalid request method', 'studentDashboard');
    exit;
}

// CSRF
if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'editcourses')) {
    redirectWithToast('error', 'Unauthorized access', 'studentDashboard');
    exit;
}
$studentId = $_SESSION['user_id'];
$courses   = $_POST['courses'] ?? [];

/* ===================== */
/* FETCH REG */
/* ===================== */
$reg = $model->getRows('course_registered', [
    'where' => [
        'student_id' => $studentId,
        'semester'   => $activeSemester['id'],
        'session'    => $activeSession['id']
    ],
    'return_type' => 'single'
]);

if (!$reg || !in_array($reg['approval_status'], ['submitted', 'rejected'])) {
    redirectWithToast('error', 'Edit not allowed', 'studentDashboard');
    exit;
}

/* ===================== */
/* REMOVE DUPLICATES */
/* ===================== */
$courses = array_unique($courses);

/* ===================== */
/* CALCULATE UNITS */
/* ===================== */
$totalUnits = 0;

foreach ($courses as $id) {
    $course = $model->getRows('courses', [
        'where' => ['id' => $id]
    ]);

    if (!empty($course)) {
        $totalUnits += (int)$course[0]['unit'];
    }
}

/* ===================== */
/* VALIDATE MAX */
/* ===================== */
if ($totalUnits > 30) {
    redirectWithToast('error', 'Max units exceeded', 'studentDashboard');
    exit;
}

/* ===================== */
/* UPDATE */
/* ===================== */
$model->beginTransaction();

try {

    // DELETE OLD
    $model->delete('registered_course', [
        'course_regID' => $reg['course_regID']
    ]);

    // INSERT NEW
    foreach ($courses as $courseId) {
        $model->insert_data('registered_course', [
            'course_regID' => $reg['course_regID'],
            'course_id'    => $courseId
        ]);
    }

    // UPDATE PARENT
    $model->update('course_registered', [
        'total_units' => $totalUnits,
        'approval_status' => 'pending'
    ], [
        'course_regID' => $reg['course_regID']
    ]);

    $model->commit();
    $utility->logActivityUsers('Successfully updated course form for student with user ID: ' . $studentId, $_SESSION['user_email'] ?? 'Unknown');
    redirectWithToast('success', 'Courses updated successfully', 'studentDashboard');
} catch (Exception $e) {

    $model->rollBack();

    redirectWithToast('error', 'Update failed', 'studentDashboard');
}
