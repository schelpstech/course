<?php
// helper for generating links

function route($page, $utility)
{
    return "../controller/router.php?pageid=" . $utility->secureEncode($page);
}

function getUserByID($model)
{
    return $model->getRows('users', [
        'return_type' => 'single',
        'where' => ['id' => $_SESSION['user_id']]
    ]);
}

function getCurrentSession($model)
{
    return $model->getRows('academic_sessions', [
        'return_type' => 'single',
        'where' => ['is_active' => 1]
    ]);
}


function getActiveSemester($model)
{
    $session = getCurrentSession($model);
    return $model->getRows('semesters', [
        'where' => [
            'session_id' => $session['id'],
            'is_active' => 1
        ],
        'return_type' => 'single'
    ]);
}

function getSemesterRegistrationStatus($model)
{
    $session = getCurrentSession($model);
    $semester = getActiveSemester($model);

    return $model->getRows('semesterRegistration', [
        'where' => [
            'student_id' => $_SESSION['user_id'],
            'session_id' => $session['id'],
            'semester_id' => $semester['id']
        ],
        'return_type' => 'single'
    ]);
}

function courseRegistrationStatus($model)
{
    $session = getCurrentSession($model);
    $semester = getActiveSemester($model);

    return $model->getRows('course_registered', [
        'where' => [
            'student_id' => $_SESSION['user_id'],
            'session' => $session['id'],
            'semester' => $semester['id']
        ],
        'return_type' => 'single'
    ]);
}

function getStudentProfile($model)
{
    return $model->getRows('students', [
        'return_type' => 'single',
        'where' => [
            'student_id' => $_SESSION['user_id'],
            'status' => "active",
            'updateProfile' => 1
        ]
    ]);
}


$institutions = $model->getRows('institutions');

$levels = $model->getRows('levels');

$activeSession = getCurrentSession($model);
$activeSemester = getActiveSemester($model);
$CurrentSemester = $activeSemester['name'] . " Semester " . $activeSession['name'];


$student = $model->getRows('students', [
    'where' => ['student_id' => $_SESSION['user_id']],
    'return_type' => 'single'
]);

$userData = getUserByID($model);
$user_email = $userData['email'] ?? '';
$user_role = $userData['role'] ?? 'student';

//institutions

$institution = $model->getRows('institutions', [
    'where' => ['id' => $userData['institution_id']],
    'return_type' => 'single'
]);


function redirectWithToast($type, $message, $page)
{
    $utility = new Utility();
    $_SESSION['toast'] = ['type' => $type, 'message' => $message];

    // Primary path
    $path1 = "../../controller/router.php";
    // Fallback path
    $path2 = "../controller/router.php";

    // Choose the correct path based on file existence
    $redirectPath = file_exists($path1) ? $path1 : $path2;

    header("Location: {$redirectPath}?pageid=" . $utility->secureEncode($page));
    exit;
}



$status = getSemesterRegistrationStatus($model);
$profile = getStudentProfile($model);
$courseRegistrationStatus = courseRegistrationStatus($model);
