<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();

$response = ["status" => false];
$transactionStarted = false;

try {

    // ==========================
    // INPUTS
    // ==========================
    $id = $_POST['id'] ?? null; // user_id

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

    // =====================================================
    // UPDATE FLOW
    // =====================================================
    if ($id) {

        // 🔍 GET USER
        $user = $model->getById("users", $id);
        if (!$user) {
            throw new Exception("User not found");
        }

        // 🔍 GET STUDENT PROFILE USING RELATION
        $student = $model->getRows("students", [
            "where" => ["student_id" => $id],
            "return_type" => "single"
        ]);

        if (!$student) {
            throw new Exception("Student profile not found");
        }

        // ==========================
        // DUPLICATE CHECKS
        // ==========================

        // EMAIL CHECK
        $existingUser = $model->getRows("users", [
            "where" => ["email" => $email],
            "return_type" => "single"
        ]);

        if ($existingUser && $existingUser['id'] != $id) {
            throw new Exception("Email already exists for another user");
        }

        // MATRIC CHECK
        $existingMatric = $model->getRows("students", [
            "where" => ["matric_no" => $matric_no],
            "return_type" => "single"
        ]);

        if ($existingMatric && $existingMatric['student_id'] != $id) {
            throw new Exception("Matric number already exists");
        }

        // ==========================
        // UPDATE USERS TABLE
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
        // UPDATE STUDENT TABLE
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

        $utility->logActivity("Updated student (User ID: $id, Name: $fullname)");

        $msg = "Student profile updated successfully";
    }

    // =====================================================
    // CREATE FLOW
    // =====================================================
    else {

        if ($model->exists("users", ["email" => $email])) {
            throw new Exception("Email already exists");
        }

        if ($model->exists("students", ["matric_no" => $matric_no])) {
            throw new Exception("Matric number already exists");
        }

        $passwordPlain = "abcd1234";
        $password = password_hash($passwordPlain, PASSWORD_DEFAULT);

        // CREATE USER
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

        // CREATE STUDENT PROFILE
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

        $utility->logActivity("Created student (User ID: $userId, Name: $fullname)");

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
        $model->rollBack();
    }

    $response["message"] = $e->getMessage();
}

echo json_encode($response);
exit;