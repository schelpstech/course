<?php
require_once '../../start.inc.php';
require_once '../query.php';

// 🔐 Verify Paystack Signature
$paystackSecret = "sk_test_5cfd6d4ebaaa28e178ca697148bbee69e9d86e65";

$input = @file_get_contents("php://input");
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

if (!$signature || !hash_equals(hash_hmac('sha512', $input, $paystackSecret), $signature)) {
    http_response_code(400);
    exit("Invalid signature");
}

// Decode payload
$event = json_decode($input, true);

if (!$event) {
    http_response_code(400);
    exit("Invalid payload");
}

// ✅ Handle only successful charge
if ($event['event'] === 'charge.success') {

    $data = $event['data'];

    $reference = $data['reference'];
    $amount = $data['amount'] / 100; // convert from kobo
    $status = $data['status'];

    try {

        // 🔍 Check if already processed (VERY IMPORTANT)
        $existing = $model->getRows('payments', [
            'where' => ['paymentReference' => $reference],
            'return_type' => 'single'
        ]);

        if (!$existing) {
            http_response_code(200);
            exit("Payment not found in system");
        }

        if ($existing['status'] === 'successful') {
            // Already processed → avoid duplicate
            http_response_code(200);
            exit("Already processed");
        }

        // ✅ Update payment
        $model->update(
            "payments",
            [
                "status" => "successful",
                "amount" => $amount
            ],
            ["paymentReference" => $reference]
        );

        // 🔥 Extract metadata (VERY IMPORTANT)
        $metadata = $data['metadata'] ?? [];

        $studentID = $metadata['student_id'] ?? null;
        $sessionID = $metadata['session_id'] ?? null;
        $semesterID = $metadata['semester_id'] ?? null;

        if ($studentID && $sessionID && $semesterID) {

            $model->update(
                "semesterregistration",
                [
                    "course_fee_paid" => 1,
                    "course_fee_paid_at" => date('Y-m-d H:i:s')
                ],
                [
                    "student_id" => $studentID,
                    "session_id" => $sessionID,
                    "semester_id" => $semesterID
                ]
            );
        }

        http_response_code(200);
        echo "Webhook processed";

    } catch (Exception $e) {
        http_response_code(500);
        echo "Error: " . $e->getMessage();
    }
}