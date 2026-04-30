<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$response = ["status" => false];
$transactionStarted = false;

try {

    $id = $_POST['id'] ?? null;

    $matric_no  = trim($_POST['matric_no'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $other_name = trim($_POST['other_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $dob        = $_POST['dob'] ?? '';
    $gender     = $_POST['gender'] ?? '';
    $institution = $_POST['institution_id'] ?? '';
    $programme  = $_POST['programme_id'] ?? '';
    $department = $_POST['department_id'] ?? '';
    $level      = $_POST['level_id'] ?? '';

    // ==========================
    // VALIDATION
    // ==========================
    if (
        !$matric_no || !$email || !$first_name ||
        !$last_name || !$dob || !$gender ||
        !$institution || !$programme || !$department || !$level
    ) {
        throw new Exception("All required fields must be filled");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email address");
    }

    $fullname = trim("$first_name $other_name $last_name");


    // ==========================
    // START TRANSACTION
    // ==========================
    $model->beginTransaction();
    $transactionStarted = true;

    if ($id) {

        // ==========================
        // DUPLICATE CHECKS
        // ==========================
        // Check email
        $existingUser = $model->getRows("users", [
            "where" => ["email" => $email],
            "return_type" => "single"
        ]);

        if ($existingUser && $existingUser['id'] != $id) {
            throw new Exception("Email already exists for another user. Selected User - ".$id." existing: ".$existingUser['id']);
        }

        // Check matric
        $existingMatric = $model->getRows("students", [
            "where" => ["matric_no" => $matric_no],
            "return_type" => "single"
        ]);

        if ($existingMatric && $existingMatric['student_id'] != $id) {
            throw new Exception("Matric number already exists for another student".$id);
        }
        $user = $model->getById("users", $id);
        if (!$user) {
            throw new Exception("Student not found".$id);
        }
        // ==========================
        // UPDATE USER
        // ==========================
        $updatedUser = $model->update("users", [
            "name" => $fullname,
            "email" => $email,
            "institution_id" => $institution
        ], ["id" => $id]);

        if (!$updatedUser) {
            throw new Exception("Failed to update user");
        }

        // ==========================
        // UPDATE STUDENT
        // ==========================
        $updatedStudent = $model->update("students", [
            "matric_no" => $matric_no,
            "first_name" => $first_name,
            "other_name" => $other_name,
            "last_name" => $last_name,
            "dateofbirth" => $dob,
            "gender" => $gender,
            "institution_id" => $institution,
            "programme_id" => $programme,
            "department_id" => $department,
            "level_id" => $level
        ], ["student_id" => $id]);

        if (!$updatedStudent) {
            throw new Exception("Failed to update student profile");
        }
        $utility->logActivity('Updated Student profile with ID : ' . $id . ' and name : ' . $fullname);
        $msg = "Student profile updated successfully";
    } else {
        // ==========================
        // DUPLICATE CHECKS
        // ==========================
        if ($model->exists("users", ["email" => $email])) {
            throw new Exception("Email already exists".$id);
        }

        if ($model->exists("students", ["matric_no" => $matric_no])) {
            throw new Exception("Matric already exists".$id);
        }
        // ==========================
        // CREATE USER
        // ==========================
        $passwordPlain = "abcd1234";
        $password = password_hash($passwordPlain, PASSWORD_DEFAULT);

        $userId = $model->insert_data("users", [
            "name" => $fullname,
            "email" => $email,
            "role" => "student",
            "password" => $password,
            "institution_id" => $institution,
            "is_active" => 1
        ]);

        if (!$userId) {
            throw new Exception("Failed to create user");
        }

        // ==========================
        // CREATE STUDENT PROFILE
        // ==========================
        $student = $model->insert_data("students", [
            "student_id" => $userId,
            "matric_no" => $matric_no,
            "first_name" => $first_name,
            "other_name" => $other_name,
            "last_name" => $last_name,
            "dateofbirth" => $dob,
            "gender" => $gender,
            "institution_id" => $institution,
            "programme_id" => $programme,
            "department_id" => $department,
            "level_id" => $level,
            "status" => "active"
        ]);

        if (!$student) {
            throw new Exception("Failed to create student profile");
        }
        $utility->logActivity('Created new Student profile with ID : ' . $id . ' and name : ' . $fullname);
        $msg = "Student created successfully. Default password: $passwordPlain";
    }

    // ==========================
    // COMMIT
    // ==========================
    $model->commit();

    $response["status"] = true;
    $response["message"] = $msg;
} catch (Exception $e) {

    if ($transactionStarted) {
        $model->rollBack(); // ✅ SAFE
    }

    $response["message"] = $e->getMessage();
}

echo json_encode($response);
exit;
