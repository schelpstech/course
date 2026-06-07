<?php
function validatePayment($reference, $model, $paystack, $utility, $activeSession, $activeSemester)
{
    try {

        $verification = $paystack->verifyTransaction($reference);



        // ==============================
        // HANDLE API FAILURE FIRST
        // ==============================
        if (!$verification || !isset($verification['status']) || $verification['status'] !== true) {
            return "PAYSTACK_API_ERROR";
        }

        $data = $verification['data'] ?? null;

        if (!$data) {
            return "INVALID_RESPONSE";
        }

        $status = $data['status'];

        $studentPayment = $model->getRows('payments', [
            'where' => ['paymentReference' => $reference],
            'return_type' => 'single'
        ]);

        if (!$studentPayment) {
            return "LOCAL_PAYMENT_NOT_FOUND";
        }

        if ($verification['message'] === 'Transaction reference not found') {

            $model->update(
                "payments",
                ["status" => "failed"],
                ["paymentReference" => $reference]
            );

            $utility->logActivity(
                "SystemChecks :: Marked as orphan (not found on Paystack): " . $reference,
                "admin@schelps.com"
            );

            return "NOT_FOUND_ORPHAN";
        }

        // ==============================
        // SUCCESS CASE
        // ==============================
        if ($status === 'success') {

            if ($studentPayment['status'] === 'successful') {
                return "ALREADY_PROCESSED";
            }

            $model->update(
                "payments",
                ["status" => "successful"],
                ["paymentReference" => $reference]
            );

            $model->update(
                "semesterregistration",
                [
                    "course_fee_paid" => 1,
                    "course_fee_paid_at" => date('Y-m-d H:i:s')
                ],
                [
                    "student_id" => $studentPayment['student_id'],
                    "session_id" => $activeSession,
                    "semester_id" => $activeSemester
                ]
            );

            $utility->logActivityUsers(
                'Validated payment: ' . $reference,
                $studentPayment['student_id']
            );

            $utility->logActivity(
                'SystemChecks :: Validated payment: ' . $reference,
                "admin@schelps.com"
            );

            return "SUCCESS";
        }

        // ==============================
        // FAILED / ABANDONED
        // ==============================
        if (in_array($status, ['failed', 'abandoned'])) {

            $model->update(
                "payments",
                ["status" => "failed"],
                ["paymentReference" => $reference]
            );

            $utility->logActivity(
                'SystemChecks :: Failed payment updated: ' . $reference,
                "admin@schelps.com"
            );

            return "FAILED";
        }

        // ==============================
        // PENDING CASE
        // ==============================
        if ($status === 'pending') {
            return "PENDING";
        }

        // ==============================
        // UNKNOWN STATUS (SAFE HANDLING)
        // ==============================
        return "UNKNOWN_STATUS: " . $status;
    } catch (Exception $e) {
        return "ERROR: " . $e->getMessage();
    }
}
