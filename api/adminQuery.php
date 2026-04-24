<?php

//route function for secure page redirection
function route($page, $utility)
{
    return "../controller/admnrouter.php?pageid=" . $utility->secureEncode($page);
}

// Function to redirect with toast message
function redirectWithToast($type, $message, $page)
{
    $utility = new Utility();
    $_SESSION['toast'] = ['type' => $type, 'message' => $message];

    // Primary path
    $path1 = "../../controller/admnrouter.php";
    // Fallback path
    $path2 = "../controller/admnrouter.php";

    // Choose the correct path based on file existence
    $redirectPath = file_exists($path1) ? $path1 : $path2;

    header("Location: {$redirectPath}?pageid=" . $utility->secureEncode($page));
    exit;
}
class Admin extends Model
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


