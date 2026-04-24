<?php

// ==========================
// SESSION SAFETY CORE
// ==========================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Ensure user session exists
 * Returns user_id or null
 */
function requireUserSession()
{
    return $_SESSION['user_id'] ?? null;
}


// ==========================
// ROUTE HELPER
// ==========================

function route($page, $utility)
{
    return "../controller/router.php?pageid=" . $utility->secureEncode($page);
}


// ==========================
// USER HELPERS
// ==========================

function getUserByID($model)
{
    $userId = requireUserSession();

    if (!$userId) return null;

    return $model->getRows('users', [
        'return_type' => 'single',
        'where' => ['id' => $userId]
    ]) ?: null;
}


// ==========================
// ACADEMIC SESSION HELPERS
// ==========================

function getCurrentSession($model)
{
    return $model->getRows('academic_sessions', [
        'return_type' => 'single',
        'where' => ['is_active' => 1]
    ]) ?: null;
}


function getActiveSemester($model)
{
    $session = getCurrentSession($model);

    if (!$session || empty($session['id'])) {
        return null;
    }

    return $model->getRows('semesters', [
        'where' => [
            'session_id' => $session['id'],
            'is_active' => 1
        ],
        'return_type' => 'single'
    ]) ?: null;
}


// ==========================
// REGISTRATION HELPERS
// ==========================

function getSemesterRegistrationStatus($model)
{
    $userId = requireUserSession();
    $session = getCurrentSession($model);
    $semester = getActiveSemester($model);

    if (!$userId || !$session || !$semester) {
        return null;
    }

    return $model->getRows('semesterRegistration', [
        'where' => [
            'student_id' => $userId,
            'session_id' => $session['id'],
            'semester_id' => $semester['id']
        ],
        'return_type' => 'single'
    ]) ?: null;
}


function courseRegistrationStatus($model)
{
    $userId = requireUserSession();
    $session = getCurrentSession($model);
    $semester = getActiveSemester($model);

    if (!$userId || !$session || !$semester) {
        return null;
    }

    return $model->getRows('course_registered', [
        'where' => [
            'student_id' => $userId,
            'session' => $session['id'],
            'semester' => $semester['id']
        ],
        'return_type' => 'single'
    ]) ?: null;
}


// ==========================
// STUDENT PROFILE
// ==========================

function getStudentProfile($model)
{
    $userId = requireUserSession();

    if (!$userId) return null;

    return $model->getRows('students', [
        'return_type' => 'single',
        'where' => [
            'student_id' => $userId,
            'status' => "active",
            'updateProfile' => 1
        ]
    ]) ?: null;
}


// ==========================
// GLOBAL DATA (SAFE LOADING)
// ==========================

$institutions = $model->getRows('institutions') ?: [];
$levels       = $model->getRows('levels') ?: [];


// ==========================
// ACTIVE ACADEMIC DATA
// ==========================

$activeSession  = getCurrentSession($model);
$activeSemester = getActiveSemester($model);


// SAFE SEMESTER STRING
$CurrentSemester = '';

if ($activeSemester && $activeSession) {
    $CurrentSemester = $activeSemester['name'] . " Semester " . $activeSession['name'];
}


// ==========================
// STUDENT DATA
// ==========================

$userId = requireUserSession();

$student = $userId
    ? $model->getRows('students', [
        'where' => ['student_id' => $userId],
        'return_type' => 'single'
    ])
    : null;


// ==========================
// USER DATA
// ==========================

$userData = getUserByID($model) ?: [];

$user_email = $userData['email'] ?? '';
$user_role  = $userData['role'] ?? 'student';


// ==========================
// INSTITUTION DATA
// ==========================

$institution = null;

if (!empty($userData['institution_id'])) {
    $institution = $model->getRows('institutions', [
        'where' => ['id' => $userData['institution_id']],
        'return_type' => 'single'
    ]);
}


// ==========================
// REGISTRATION STATUS
// ==========================

$status  = getSemesterRegistrationStatus($model);
$profile = getStudentProfile($model);
$courseRegistrationStatus = courseRegistrationStatus($model);


// ==========================
// REDIRECT WITH TOAST
// ==========================

function redirectWithToast($type, $message, $page)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $utility = new Utility();

    $_SESSION['toast'] = [
        'type' => $type,
        'message' => $message
    ];

    $path1 = "../../controller/router.php";
    $path2 = "../controller/router.php";

    $redirectPath = file_exists($path1) ? $path1 : $path2;

    header("Location: {$redirectPath}?pageid=" . $utility->secureEncode($page));
    exit;
}