<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(["status" => false]);
    exit;
}

/**
 * ============================
 * FETCH BASIC PROFILE
 * ============================
 */
$student = $model->getRows("students", [
    "select" => "
        students.*,
        users.email,
        institutions.name AS institution_name,
        programmes.name AS programme_name,
        department.name AS department_name,
        levels.code AS level_name
        
    ",
    "join" => [
        "users" => "ON users.id = students.student_id",
        "institutions" => "ON institutions.id = users.institution_id",
        "programmes" => "ON programmes.id = students.programme_id",
        "department" => "ON department.id = students.department_id",
        "levels" => "ON levels.id = students.level_id"
    ],
    "where" => ["students.student_id" => $id],
    'return_type' => 'single'
]);

if (!$student) {
    echo json_encode(["status" => false]);
    exit;
}

/**
 * ============================
 * SEMESTER REGISTRATION
 * ============================
 */

//Select current Semester ID
$currentSemester = $model->getRows("semesters", [
    "where" => ["is_active" => 1],
    'return_type' => 'single'
]);

$registration = $model->getRows("semesterregistration", [
    "where" => [
        "student_id" => $id,
        "semester_id" => $currentSemester['id']
    ],
    'return_type' => 'single'
]);

$isRegistered = $registration ? true : false;

/**
 * ============================
 * Course Registration Table that shows course registration status for the current semester
 * ============================
 */
$course_reg = $model->getRows("course_registered", [
    "where" => [
        "student_id" => $id,
        "semester" => $currentSemester['id']
    ],
    'return_type' => 'single'
]);

$regID = $course_reg['course_regID'] ?? null;

/**
 * ============================
 * COURSES REgistered  + TOTAL UNITS
 * ============================
 */
$courses = [];
$totalUnits = 0;

if ($regID) {
    $courses = $model->query("
        SELECT c.course_code, c.course_title, c.unit
        FROM registered_course rc
        JOIN courses c ON c.id = rc.course_id
        WHERE rc.course_regID = '{$regID}'
    ");

    if (is_array($courses)) {
        foreach ($courses as $c) {
            $totalUnits += (int)$c['unit'];
        }
    }
}

/**
 * ============================
 * PAYMENT INFO
 * ============================
 */
$payments = $model->query("
    SELECT SUM(amount_paid) as total_paid
    FROM payments
    WHERE student_id = '{$id}' AND status = 'successful'
");

$totalPaid = $payments[0]['total_paid'] ?? 0;

/**
 * Optional: expected fee (if you have fee table)
 */

$fee = $model->getRows("school_fee_settings", [
    "where" => [
        "level_id" => $student['level_id'],
        "semester_id" => $currentSemester['id']
    ],
    'return_type' => 'single'
]);


$expectedFee = $fee['amount'] ?? 0; // you can replace with actual logic

$paymentStatus = "Not Paid";
if ($totalPaid > 0 && $totalPaid < $expectedFee) {
    $paymentStatus = "Partial";
} elseif ($totalPaid >= $expectedFee && $expectedFee > 0) {
    $paymentStatus = "Paid";
}

/**
 * ============================
 * RESPONSE
 * ============================
 */
echo json_encode([
    "status" => true,
    "data" => [
        "fullname" => $student['first_name'] . ' ' . $student['other_name'] . ' ' . $student['last_name'],
        "matric" => $student['matric_no'],
        "email" => $student['email'],
        "gender" => $student['gender'] == 1 ? 'Male' : 'Female',
        "dob" => $student['dateofbirth'],
        "institution" => $student['institution_name'],
        "programme" => $student['programme_name'],
        "department" => $student['department_name'],
        "level" => $student['level_name'],
        "passport" => $student['passport'] ?? null,

        "registration_status" => $isRegistered ? "Registered" : "Not Registered",

        "courses" => $courses,
        "total_courses" => is_array($courses) ? count($courses) : 0,
        "total_units" => $totalUnits,

        "total_paid" => $totalPaid,
        "payment_status" => $paymentStatus
    ]
]);
