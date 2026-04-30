<?php
require_once '../../start.inc.php';
require_once '../query.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithToast('error', 'Invalid request method', 'studentDashboard');
    exit;
}

// CSRF
if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'paymentform')) {
    redirectWithToast('error', 'Unauthorized access', 'studentDashboard');
    exit;
}

// Required fields
$required = ['semester_id', 'amount_paid', 'payment_mode', 'payment_date'];

foreach ($required as $field) {
    if (empty($_POST[$field])) {
        redirectWithToast('error', ucfirst(str_replace('_', ' ', $field)) . ' is required', 'uploadReceipt');
        exit;
    }
}

// FILE VALIDATION
if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
    redirectWithToast('error', 'Payment evidence is required', 'uploadReceipt');
    exit;
}

$file = $_FILES['payment_proof'];

// Type check
if ($file['type'] !== 'application/pdf') {
    redirectWithToast('error', 'Only PDF files are allowed', 'uploadReceipt');
    exit;
}

// Size check (100KB)
if ($file['size'] > 100 * 1024) {
    redirectWithToast('error', 'File must be less than 100KB', 'uploadReceipt');
    exit;
}

// Upload
$uploadDir = '../../uploads/payments/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$filename = 'payment_' . $_SESSION['user_id'] . '_' . time() . '.pdf';
$destination = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    redirectWithToast('error', 'Failed to upload file', 'uploadReceipt');
    exit;
}

$filePath = 'uploads/payments/' . $filename;
$paymentref = 'PAY-' . strtoupper(bin2hex(random_bytes(8)));
// SAVE DATA
$data = [
    'student_id' => $_SESSION['user_id'],
    'paymentReference' => $paymentref,
    'semester_id' => $activeSemester['id'],
    'amount_paid' => $_POST['amount_paid'],
    'payment_type' => "school_fee",
    'payment_mode' => $_POST['payment_mode'],
    'payment_date' => $_POST['payment_date'],
    'payment_proof' => $filePath,
    'created_at' => date('Y-m-d H:i:s')
];



$insert = $model->insert_data('payments', $data);

if ($insert) {

    $refetch = $model->getRows('payments', [
        'where' => ['student_id' => $_SESSION['user_id'], 'paymentReference' => $paymentref],
        'order_by' => 'created_at DESC',
        'return_type' => 'single'
    ]);
    $utility->logActivityUsers('Successfully uploaded payment receipt for student with user ID: ' . $_SESSION['user_id'], $_SESSION['user_email'] ?? 'Unknown');
    if (!$refetch) {
        redirectWithToast('error', 'Failed to retrieve payment details after upload', 'uploadReceipt');
        exit;
    }
    // SAVE DATA
    $Regdata = [
        'student_id' => $_SESSION['user_id'],
        'session_id' => $activeSemester['session_id'],
        'semester_id' => $activeSemester['id'],
        'sch_fees_paymentID' => $refetch['id'],
        'receipt_uploaded' => 1,
        'uploaded_at' => date('Y-m-d H:i:s')
    ];

    $registerforSemester =  $model->insert_data('semesterRegistration', $Regdata);

    if ($registerforSemester) {
        $utility->logActivityUsers('Successfully initiated semester registration for student with user ID: ' . $_SESSION['user_id'], $_SESSION['user_email'] ?? 'Unknown');
        redirectWithToast('success', 'You have successfully initiated for this semester registration. Please wait for admin approval.', 'studentDashboard');
        exit;
    } else {
        redirectWithToast('error', 'Failed to initiate semester registration', 'uploadReceipt');
    }
} else {
    redirectWithToast('error', 'Failed to save payment', 'uploadReceipt');
}
