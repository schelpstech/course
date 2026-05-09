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

        // ==========================
        // FETCH CURRENT DATA
        // ==========================
        $user = $model->getById("users", $id);
        if (!$user) {
            throw new Exception("User not found");
        }

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
        $existingUser = $model->getRows("users", [
            "where" => ["email" => $email],
            "return_type" => "single"
        ]);

        if ($existingUser && $existingUser['id'] != $id) {
            throw new Exception("Email already exists for another user");
        }

        $existingMatric = $model->getRows("students", [
            "where" => ["matric_no" => $matric_no],
            "return_type" => "single"
        ]);

        if ($existingMatric && $existingMatric['student_id'] != $id) {
            throw new Exception("Matric number already exists");
        }

        // ==========================
        // PREPARE USER UPDATE (ONLY CHANGED FIELDS)
        // ==========================
        $userUpdateData = [];

        if ($user['name'] !== $fullname) {
            $userUpdateData['name'] = $fullname;
        }

        if ($user['email'] !== $email) {
            $userUpdateData['email'] = $email;
        }

        if ($user['institution_id'] != $institution) {
            $userUpdateData['institution_id'] = $institution;
        }

        // ==========================
        // PREPARE STUDENT UPDATE
        // ==========================
        $studentUpdateData = [];

        if ($student['matric_no'] !== $matric_no) {
            $studentUpdateData['matric_no'] = $matric_no;
        }

        if ($student['first_name'] !== $first_name) {
            $studentUpdateData['first_name'] = $first_name;
        }

        if ($student['other_name'] !== $other_name) {
            $studentUpdateData['other_name'] = $other_name;
        }

        if ($student['last_name'] !== $last_name) {
            $studentUpdateData['last_name'] = $last_name;
        }

        if ($student['dateofbirth'] !== $dob) {
            $studentUpdateData['dateofbirth'] = $dob;
        }

        if ($student['gender'] !== $gender) {
            $studentUpdateData['gender'] = $gender;
        }

        if ($student['institution_id'] != $institution) {
            $studentUpdateData['institution_id'] = $institution;
        }

        if ($student['programme_id'] != $programme) {
            $studentUpdateData['programme_id'] = $programme;
        }

        if ($student['department_id'] != $department) {
            $studentUpdateData['department_id'] = $department;
        }

        if ($student['level_id'] != $level) {
            $studentUpdateData['level_id'] = $level;
        }

        // ==========================
        // EXECUTE UPDATES (ONLY IF NEEDED)
        // ==========================
        if (!empty($userUpdateData)) {

            $updatedUser = $model->Newupdate("users", $userUpdateData, ["id" => $id]);

            if ($updatedUser === false) {
                throw new Exception("Failed to update user");
            }
        }

        if (!empty($studentUpdateData)) {

            $updatedStudent = $model->Newupdate("students", $studentUpdateData, ["student_id" => $id]);

            if ($updatedStudent === false) {
                throw new Exception("Failed to update student profile");
            }
        }

        // ==========================
        // LOGGING
        // ==========================
        if (!empty($userUpdateData) || !empty($studentUpdateData)) {
            $utility->logActivity("Updated student (User ID: $id, Name: $fullname)");
            $msg = "Student profile updated successfully";
        } else {
            $msg = "No changes detected";
        }
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

        // CREATE STUDENT
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