<?php
require_once '../../start.inc.php';
require_once '../query.php';


// Get reference from Paystack
if (isset($_GET['reference'])) {
    $reference = $_GET['reference'];

    try {
        $verification = $paystack->verifyTransaction($reference);
        $status = $verification['data']['status']; // success | failed | abandoned

        if ($status === 'success') {
            $model->update(
                "payments",
                ["status" => "successful"],
                ["paymentReference" => $reference]
            );
            $utility->logActivityUsers(' Successfully validated payment for student with payment reference: ' . $reference, $_SESSION['user_email'] ?? 'Unknown');

            // When a new applicant is created, initialize progress
            $model->update(
                "semesterregistration",
                [
                    "course_fee_paid" => 1,
                    "course_fee_paid_at" => date('Y-m-d H:i:s')
                ],
                ["student_id" => $_SESSION['user_id'], "session_id" => $activeSession['id'], "semester_id" => $activeSemester['id']]
            );
            $utility->logActivityUsers(' Successfully updated semester registration payment for student with user ID: ' . $_SESSION['user_id'] . ' and payment reference: ' . $reference, $_SESSION['user_email'] ?? 'Unknown');

            $_SESSION['toast'] = [
                'type' => 'success',
                'message' => 'Payment Successful. Proceed to Course Registration'
            ];
            header("Location: ../../controller/router.php?pageid=" . $utility->secureEncode('studentDashboard'));
        } else {
            $model->update(
                "payments",
                ["status" => "failed"],
                ["paymentReference" => $reference]
            );
            $utility->logActivityUsers('Failed to validate payment for student with payment reference: ' . $reference, $_SESSION['user_email'] ?? 'Unknown');

            $_SESSION['toast'] = [
                'type' => 'error',
                'message' => 'Payment Verification failed. Please try again.'
            ];
            header("Location: ../../controller/router.php?pageid=" . $utility->secureEncode('studentDashboard'));
        }
    } catch (Exception $e) {
        $_SESSION['toast'] = [
            'type' => 'error',
            'message' => "Error verifying transaction: " . $e->getMessage()
        ];
        header("Location: ../../controller/router.php?pageid=" . $utility->secureEncode('studentDashboard'));
    }
} else {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Unauthorized access'
    ];
    header("Location: ../../controller/router.php?pageid=" . $utility->secureEncode('studentDashboard'));
}
