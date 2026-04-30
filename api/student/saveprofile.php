<?php
require_once '../../start.inc.php';
require_once '../query.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithToast('error', 'Invalid request method', 'login');
    exit;
}

if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'studentform')) {
    redirectWithToast('error', 'Unauthorized access denied', 'login');
    exit;
}

/**
 * ==========================
 * REQUIRED FIELD VALIDATION
 * ==========================
 */
$required = ['phone'];

foreach ($required as $field) {
    if (empty($_POST[$field])) {
        redirectWithToast(
            'error',
            ucfirst(str_replace('_', ' ', $field)) . ' is required.',
            'updateStudentProfile'
        );
        exit;
    }
}

/**
 * ==========================
 * PHONE VALIDATION (STRICT)
 * ==========================
 */
$phone = trim($_POST['phone']);

// Must contain digits only
if (!preg_match('/^\d+$/', $phone)) {
    redirectWithToast('error', 'Phone number must contain only digits.', 'updateStudentProfile');
    exit;
}

// Must be exactly 11 digits
if (strlen($phone) !== 11) {
    redirectWithToast('error', 'Phone number must be exactly 11 digits.', 'updateStudentProfile');
    exit;
}

/**
 * ==========================
 * PASSPORT UPLOAD
 * ==========================
 */
$passportPath = null;

if (isset($_FILES['passport']) && $_FILES['passport']['error'] === UPLOAD_ERR_OK) {

    $file = $_FILES['passport'];

    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];

    if (!in_array($file['type'], $allowedTypes, true)) {
        redirectWithToast('error', 'Only JPG and PNG images are allowed.', 'updateStudentProfile');
        exit;
    }

    if ($file['size'] > 100 * 1024) {
        redirectWithToast('error', 'Image must be less than 100KB.', 'updateStudentProfile');
        exit;
    }

    $uploadDir = '../../uploads/passports/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);

    $filename = 'passport_' . ($_SESSION['user_id'] ?? 'student') . '_' . time() . '.' . $ext;

    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        redirectWithToast('error', 'Failed to upload image.', 'updateStudentProfile');
        exit;
    }

    $passportPath = 'uploads/passports/' . $filename;

} else {
    redirectWithToast('error', 'Passport image is required.', 'updateStudentProfile');
    exit;
}

/**
 * ==========================
 * FETCH STUDENT
 * ==========================
 */
$studentId = $_SESSION['user_id'] ?? null;

if (!$studentId) {
    redirectWithToast('error', 'Session expired. Please login again.', 'login');
    exit;
}

$existingStudent = $model->getRows('students', [
    'where' => ['student_id' => $studentId],
    'return_type' => 'single'
]);

if (!$existingStudent) {
    redirectWithToast('error', 'Unable to find student profile.', 'updateStudentProfile');
    exit;
}

/**
 * ==========================
 * UPDATE DATA
 * ==========================
 */
$data = [
    'phone' => $phone,
    'updateProfile' => 1
];

if ($passportPath) {
    $data['passport'] = $passportPath;
}

/**
 * ==========================
 * UPDATE DB
 * ==========================
 */
$updated = $model->update('students', $data, ['student_id' => $studentId]);

if ($updated) {
    $utility->logActivityUsers('Successfully updated profile for student with user ID: ' . $studentId, $_SESSION['user_email'] ?? 'Unknown');
    redirectWithToast('success', 'Profile Updated Successfully.', 'studentDashboard');
    exit;
}

redirectWithToast('error', 'Failed to update profile.', 'updateStudentProfile');
exit;