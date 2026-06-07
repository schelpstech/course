<?php
require_once '../start.inc.php';
require_once '../api/student/validatePaymentCore.php';

// ==============================
// LOG FILE SETUP
// ==============================
$logFile = __DIR__ . '/logs/payment_' . date('Y-m-d') . '.log';

function writeLog($message, $logFile)
{
    $time = date('Y-m-d H:i:s');
    file_put_contents(
        $logFile,
        "[$time] $message" . PHP_EOL,
        FILE_APPEND
    );
}

// ==============================
// GET ACTIVE SEMESTER
// ==============================
$currentSemester = $model->getRows('semesters', [
    'where' => ['is_active' => 1],
    'return_type' => 'single'
]);

$activeSession = $currentSemester['session_id'];
$activeSemester = $currentSemester['id'];

// ==============================
// GET PENDING PAYMENTS
// ==============================
$pendingPayments = $model->getRows('payments', [
    'where' => [
        'status' => 'pending',
        'payment_mode' => 'online',
        'payment_type' => 'course_reg'
    ]
]);

if (empty($pendingPayments)) {
    writeLog("No pending payments found", $logFile);
    exit;
}

writeLog("Processing " . count($pendingPayments) . " payments", $logFile);

// ==============================
// PROCESS PAYMENTS
// ==============================
foreach ($pendingPayments as $payment) {

    $reference = $payment['paymentReference'];

    writeLog("Processing reference: $reference", $logFile);

    try {

        $result = validatePayment(
            $reference,
            $model,
            $paystack,
            $utility,
            $activeSession,
            $activeSemester
        );

        writeLog("$reference => $result", $logFile);

    } catch (Exception $e) {

        writeLog("$reference => ERROR: " . $e->getMessage(), $logFile);
    }

    sleep(1);
}

writeLog("DONE processing batch", $logFile);