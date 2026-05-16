<?php

//route function for secure page redirection
function route($page, $utility)
{
    return "../controller/admnrouter.php?pageid=" . $utility->secureEncode($page);
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



// Function to redirect with toast message
function redirectWithToast($type, $message, $page)
{
    global $utility;

    $_SESSION['toast'] = ['type' => $type, 'message' => $message];

    $path1 = "../../controller/admnrouter.php";
    $path2 = "../controller/admnrouter.php";

    $redirectPath = file_exists($path1) ? $path1 : $path2;

    header("Location: {$redirectPath}?pageid=" . $utility->secureEncode($page));
    exit;
}
class Admin extends model
{


    // =========================
    // DASHBOARD COUNTS
    // =========================

    public function countStudents()
    {
        return $this->countRows("students");
    }

    public function countPayments()
    {
        return $this->countRows("payments");
    }

    public function countCourses()
    {
        return $this->countRows("courses");
    }

    //student count by institution
    public function countStudentsByInstitution($institution_id)
    {
        return $this->countRows("students", [
            "where" => ["institution_id" => $institution_id]
        ]);
    }

    public function countStudentsPerInstitution()
    {
        $sql = "
        SELECT 
            i.id AS institution_id,
            i.name AS institution_name,
            COUNT(s.student_id) AS total_students
        FROM institutions i
        LEFT JOIN students s 
            ON s.institution_id = i.id
        GROUP BY i.id
        ORDER BY i.name ASC
    ";

        return $this->query($sql);
    }


    // SEMESTER REGISTRATION COUNTS

    public function getSemesterRegistrationStats($curr_session_id, $curr_semester_id)
    {


// ==========================
// ACTIVE ACADEMIC DATA
// ==========================

        $sql = "
        SELECT 
            COUNT(*) AS total_students,

            SUM(CASE WHEN receipt_uploaded = 1 THEN 1 ELSE 0 END) AS receipt_uploaded,

            SUM(CASE WHEN payment_confirmed = 1 THEN 1 ELSE 0 END) AS payment_confirmed,

            SUM(CASE WHEN course_fee_paid = 1 THEN 1 ELSE 0 END) AS course_fee_paid,

            SUM(CASE WHEN courses_registered = 1 THEN 1 ELSE 0 END) AS courses_registered

        FROM semesterregistration
        WHERE session_id = :session_id
        AND semester_id = :semester_id
    ";

        return $this->query($sql, [
            'session_id' => $curr_session_id,
            'semester_id' => $curr_semester_id
        ])[0] ?? [];
    }


    // =========================
    // STUDENTS
    // =========================

    public function getStudents()
    {
        return $this->getRows("students", [
            'order_by' => "id DESC"
        ]);
    }

    // =========================
    // ADMIN ACCOUNT
    // =========================

    public function getadminById($id)
    {
        return $this->getRows("admins", [
            'where' => ['id' => $id],
            'return_type' => 'single'
        ]);
    }

    public function getByEmail($email)
    {
        return $this->getRows("admins", [
            'where' => ['email' => $email],
            'return_type' => 'single'
        ]);
    }

    // =========================
    // PAYMENTS
    // =========================

    public function getPayments()
    {
        return $this->getRows("payments", [
            'order_by' => "id DESC"
        ]);
    }


    // =========================
    // INSTITUTIONS
    // =========================

    public function getInstitutions()
    {
        return $this->getRows("institutions", [
            'order_by' => "id DESC"
        ]);
    }

    public function getProgrammes()
    {
        return $this->getRows("programmes", [
            'order_by' => "id DESC"
        ]);
    }
}
$adminModel = new Admin($db);
