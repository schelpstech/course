<?php
function validatePayment($reference, $model, $paystack, $utility, $activeSession, $activeSemester)
{
    try {

        $verification = $paystack->verifyTransaction($reference);

        // ==============================
        // HANDLE API FAILURE FIRST
        // ==============================
        if (!$verification || !isset($verification['status'])) {
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

        // ==============================
        // SUCCESS
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
                'SystemChecks :: Failed payment: ' . $reference,
                "admin@schelps.com"
            );

            return "FAILED";
        }

        // ==============================
        // PENDING
        // ==============================
        if ($status === 'pending') {
            return "PENDING";
        }

        return "UNKNOWN_STATUS: " . $status;
    } catch (Exception $e) {
        // 🔥 HANDLE "NOT FOUND" PROPERLY
        if (
            stripos($e->getMessage(), 'Paystack Verification Failed') !== false
        ) {
            $model->update(
                "payments",
                ["status" => "failed"],
                ["paymentReference" => $reference]
            );
            $utility->logActivity(
                "SystemChecks :: Orphan payment (not found on Paystack): " . $reference,
                "admin@schelps.com"
            );
            return "PAYMENT_REFERENCE_NOT_FOUND_ORPHAN";
        } else {
            return "ERROR: " . $e->getMessage();
        }
    }
}
