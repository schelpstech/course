<?php
require_once '../../start.inc.php';
require_once '../adminQuery.php';

// ==========================
// METHOD CHECK
// ==========================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithToast('error', 'Bad request.', 'adminlogin');
}

// ==========================
// CSRF VALIDATION
// ==========================
if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'add-student-form')) {
    redirectWithToast('error', 'Invalid or expired request.', 'students');
}

// ==========================
// VERIFY ADMIN SESSION
// ==========================
if (!isset($_SESSION['admin_id'])) {
    redirectWithToast('error', 'Admin login required', 'adminlogin');
}

// ==========================
// SANITIZE INPUTS
// ==========================
$matric_no     = trim($_POST['matric_no'] ?? '');
$email         = trim($_POST['email'] ?? '');
$first_name    = trim($_POST['first_name'] ?? '');
$other_name    = trim($_POST['other_name'] ?? '');
$last_name     = trim($_POST['last_name'] ?? '');
$dob           = $_POST['dob'] ?? '';
$gender        = $_POST['gender'] ?? '';
$institution   = $_POST['institution_id'] ?? '';
$programme     = $_POST['programme_id'] ?? '';
$department    = $_POST['department_id'] ?? '';
$level         = $_POST['level_id'] ?? '';

// ==========================
// VALIDATION
// ==========================
if (
    empty($matric_no) || empty($email) || empty($first_name) ||
    empty($last_name) || empty($dob) || empty($gender) ||
    empty($institution) || empty($programme) ||
    empty($department) || empty($level)
) {
    redirectWithToast('error', 'All required fields must be filled.', 'students');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectWithToast('error', 'Invalid email address.', 'students');
}

// ==========================
// DUPLICATE CHECK
// ==========================
if ($model->exists('users', ['email' => $email])) {
    redirectWithToast('error', 'Email already exists.', 'students');
}

if ($model->exists('students', ['matric_no' => $matric_no])) {
    redirectWithToast('error', 'Matric number already exists.', 'students');
}

// ==========================
// PASSWORD
// ==========================
$defaultPassword = substr($matric_no, -4) . '@Edu';
$hashedPassword  = password_hash($defaultPassword, PASSWORD_DEFAULT);

// ==========================
// FULL NAME
// ==========================
$fullname = trim($first_name . ' ' . $other_name . ' ' . $last_name);

// ==========================
// INSERT DATA
// ==========================
try {

    $model->beginTransaction();

    // ==========================
    // INSERT INTO USERS TABLE
    // ==========================
    $userId = $model->insert_data('users', [
        'name'        => $fullname,
        'email'       => $email,
        'role'        => 'student',
        'password'    => $hashedPassword,
        'institution_id' => $institution,
        'is_default_password' => 1,
        'is_active'   => 1,
        'created_at'  => date('Y-m-d H:i:s')
    ]);

    if (!$userId) {
        throw new Exception("User creation failed");
    }

    // ==========================
    // INSERT INTO STUDENTS TABLE
    // ==========================
    $studentProfile = $model->insert_data('students', [
        'student_id'    => $userId, // FK
        'matric_no'     => $matric_no,
        'first_name'    => $first_name,
        'other_name'    => $other_name,
        'last_name'     => $last_name,
        'dateofbirth'   => $dob,
        'gender'        => $gender,
        'institution_id'=> $institution,
        'programme_id'  => $programme,
        'department_id' => $department,
        'level_id'      => $level,
        'status'        => 'active',
        'updateProfile' => 0,
        'created_at'    => date('Y-m-d H:i:s')
    ]);

    if (!$studentProfile) {
        throw new Exception("Student profile creation failed");
    }

    // ==========================
    // COMMIT
    // ==========================
    $model->commit();

    // ==========================
    // LOG ACTIVITY
    // ==========================
    $utility->logActivity("Student created: {$matric_no}", $_SESSION['admin_id']);

    // ==========================
    // SUCCESS
    // ==========================
    $_SESSION['toast'] = [
        'type' => 'success',
        'message' => "Student created. Default password: {$defaultPassword}"
    ];

    redirectWithToast('success', 'Student created successfully.', 'students');

} catch (Exception $e) {

    $model->rollBack();

    $utility->logActivity('Student creation failed: ' . $e->getMessage(), $_SESSION['admin_id']);

    redirectWithToast('error', 'Something went wrong. Try again.', 'students');
}