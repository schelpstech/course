<?php
set_time_limit(0);
ini_set('memory_limit', '512M');

require_once '../start.inc.php';
require_once '../api/student/validatePaymentCore.php';

// ==============================
// LOG FILE SETUP
// ==============================
$logDir = __DIR__ . '/logs';

if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

$logFile = $logDir . '/payment_' . date('Y-m-d') . '.log';

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
// START OUTPUT
// ==============================
echo "====================================\n";
echo " PAYMENT VALIDATION BATCH STARTED \n";
echo " TIME: " . date('Y-m-d H:i:s') . "\n";
echo "====================================\n\n";

writeLog("Batch started", $logFile);

// ==============================
// GET ACTIVE SEMESTER
// ==============================
$currentSemester = $model->getRows('semesters', [
    'where' => ['is_active' => 1],
    'return_type' => 'single'
]);

if (!$currentSemester) {
    writeLog("ERROR: No active semester found", $logFile);
    echo "ERROR: No active semester found\n";
    exit;
}

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
    ],
    'limit' => 50
]);

if (empty($pendingPayments)) {
    writeLog("No pending payments found", $logFile);
    echo "No pending payments found\n";
    exit;
}

writeLog("Processing " . count($pendingPayments) . " payments", $logFile);

// ==============================
// PROCESS PAYMENTS
// ==============================
foreach ($pendingPayments as $payment) {

    $reference = $payment['paymentReference'];

    echo "Processing: $reference\n";
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

        echo "$reference => $result\n";
        writeLog("$reference => $result", $logFile);

    } catch (Exception $e) {

        echo "$reference => ERROR\n";
        writeLog("$reference => ERROR: " . $e->getMessage(), $logFile);
    }

    sleep(1);
}

// ==============================
// END OUTPUT
// ==============================
echo "\n====================================\n";
echo " PAYMENT BATCH COMPLETED \n";
echo " TIME: " . date('Y-m-d H:i:s') . "\n";
echo "====================================\n";

writeLog("Batch completed", $logFile);