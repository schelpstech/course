<?php

function validatePayment($reference, $model, $paystack, $utility, $activeSession, $activeSemester)
{
    try {

        $verification = $paystack->verifyTransaction($reference);

        if (!$verification || !$verification['status']) {
            return "Verification failed";
        }

        $status = $verification['data']['status'];

        $studentPayment = $model->getRows('payments', [
            'where' => ['paymentReference' => $reference],
            'return_type' => 'single'
        ]);

        if (!$studentPayment) {
            return "Payment not found";
        }

        if ($status === 'success') {

            // avoid duplicate processing
            if ($studentPayment['status'] === 'successful') {
                return "Already processed";
            }

            // ✅ Update payment
            $model->update(
                "payments",
                ["status" => "successful"],
                ["paymentReference" => $reference]
            );

            // ✅ Update semester registration
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
                'SystemChecks Validated payment: ' . $reference,
                "admin@schelps.com"
            );

            return "SUCCESS";

        } elseif (in_array($status, ['failed', 'abandoned'])) {

            $model->update(
                "payments",
                ["status" => "failed"],
                ["paymentReference" => $reference]
            );

            return "FAILED";
        }

        return "PENDING";

    } catch (Exception $e) {
        return "ERROR: " . $e->getMessage();
    }
}