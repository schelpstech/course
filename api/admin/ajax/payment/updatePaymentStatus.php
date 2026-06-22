<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');

// 🔐 Admin only access
$utility->requireAdmin();

try {

    /**
     * ============================================
     * VALIDATE INPUT
     * ============================================
     */
    $id     = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null;
    $note   = $_POST['note'] ?? '';

    if (!$id || !$status) {
        throw new Exception("Missing parameters");
    }

    $allowed = ['successful', 'failed'];

    if (!in_array($status, $allowed)) {
        throw new Exception("Invalid status value");
    }

    /**
     * ============================================
     * START TRANSACTION
     * ============================================
     */
    $model->beginTransaction();

    /**
     * ============================================
     * FETCH PAYMENT DETAILS
     * ============================================
     * Needed for validation + registration logic
     */
    $payment = $model->getById('payments', $id);

    if (!$payment) {
        throw new Exception("Payment not found");
    }

    /**
     * ============================================
     * PREVENT DOUBLE PROCESSING
     * ============================================
     */
    if ($payment['status'] === 'successful') {
        throw new Exception("Payment already approved");
    }

    /**
     * ============================================
     * GET SEMESTER DATA
     * ============================================
     */
    $semester = $model->getById('semesters', $payment['semester_id']);

    if (!$semester) {
        throw new Exception("Semester not found");
    }

    /**
     * ============================================
     * GET STUDENT STRUCTURE
     * ============================================
     */
    $student = $model->getRows('students', [
        "where" => [
            "student_id" => $payment['student_id']
        ],
        "return_type" => "single"
    ]);

    /**
     * ============================================
     * GET PAYMENT RULES (BURSAR SETTINGS)
     * ============================================
     */
    $feeSettings = $model->getRows('school_fee_settings', [
        "where" => [
            "level_id" => $student['level_id'],
            "department_id" => $student['department_id'],
            "semester_id" => $payment['semester_id']
        ],
        "return_type" => "single"
    ]);

    $institutionRules = $model->getRows('institution_payment_terms', [
        "where" => [
            "institution_id" => $student['institution_id']
        ],
        "return_type" => "single"
    ]);

    /**
     * ============================================
     * BUSINESS VALIDATION (CUMULATIVE PAYMENT)
     * ============================================
     */
    if (
        $payment['payment_type'] === 'school_fee'
        && $status === 'successful'
    ) {

        $expectedAmount  = (float)($feeSettings['amount'] ?? 0);
        $paidAmount      = (float)$payment['amount_paid'];
        $requiredPercent = (float)($institutionRules['min_percent'] ?? 100);

        if ($expectedAmount <= 0) {
            throw new Exception(
                "No fee configuration found for this student's level."
            );
        }

        /**
         * ============================================
         * REQUIRED THRESHOLD
         * ============================================
         */
        $requiredAmount = ($expectedAmount * $requiredPercent) / 100;

        /**
         * ============================================
         * TOTAL PREVIOUS SUCCESSFUL PAYMENTS
         * ============================================
         * Excludes current payment record.
         */
        $previousSuccessful = $model->getRows('payments', [
            "select" => "COALESCE(SUM(amount_paid),0) AS total_paid",
            "where" => [
                "student_id" => $payment['student_id'],
                "semester_id" => $payment['semester_id'],
                "status" => "successful",
                "payment_type" => "school_fee"
            ],
            "return_type" => "single"
        ]);

        $previousTotal = (float)($previousSuccessful['total_paid'] ?? 0);

        /**
         * ============================================
         * PROJECTED TOTAL AFTER APPROVAL
         * ============================================
         */
        $projectedTotal = $previousTotal + $paidAmount;

        /**
         * ============================================
         * VALIDATE AGAINST INSTITUTION RULE
         * ============================================
         */
        if ($projectedTotal < $requiredAmount) {

            $remaining = $requiredAmount - $projectedTotal;

            throw new Exception(
                "Cannot approve payment. " .
                    "Required threshold is ₦" . number_format($requiredAmount, 2) .
                    ". Student will have only ₦" . number_format($projectedTotal, 2) .
                    " after approval. Outstanding amount: ₦" .
                    number_format($remaining, 2)
            );
        }
    }



    /**
     * ============================================
     * UPDATE PAYMENT
     * ============================================
     */
    $paymentUpdate = $model->update('payments', [
        'status'       => $status,
        'admin_note'   => $note,
        'approved_by'  => $_SESSION['admin_id'],
        'approved_at'  => date('Y-m-d H:i:s')
    ], [
        'id' => $id
    ]);

    if (!$paymentUpdate) {
        throw new Exception("Failed to update payment");
    }

    /**
     * ============================================
     * UPDATE REGISTRATION ONLY IF SUCCESSFUL
     * ============================================
     */
    if ($status === 'successful') {

        /**
         * ============================================
         * CHECK SEMESTER REGISTRATION
         * ============================================
         */
        $existingReg = $model->getRows('semesterregistration', [
            "where" => [
                "student_id" => $payment['student_id'],
                "semester_id" => $payment['semester_id']
            ],
            "return_type" => "single"
        ]);

        /**
         * ============================================
         * IF ALREADY CONFIRMED, SKIP UPDATE
         * ============================================
         */
        if (
            $existingReg &&
            (int)$existingReg['payment_confirmed'] === 1
        ) {

            $utility->logActivity(
                "Payment approved for student ID {$payment['student_id']}. Registration was already confirmed."
            );
        }

        /**
         * ============================================
         * REGISTRATION EXISTS BUT NOT CONFIRMED
         * ============================================
         */
        elseif ($existingReg) {

            $regUpdate = $model->update('semesterregistration', [
                'payment_confirmed' => 1,
                'confirmed_at'      => date('Y-m-d H:i:s')
            ], [
                'student_id' => $payment['student_id'],
                'session_id' => $semester['session_id'],
                'semester_id' => $payment['semester_id']
            ]);

            if (!$regUpdate) {
                throw new Exception("Registration update failed");
            }

            $utility->logActivity(
                "Payment approved and registration confirmed for student ID {$payment['student_id']} by Admin ID {$_SESSION['admin_id']}"
            );
        }

        /**
         * ============================================
         * NO SEMESTER REGISTRATION RECORD
         * ============================================
         */
        else {

            $utility->logActivity(
                "Payment approved for student ID {$payment['student_id']} but no semester registration record was found."
            );
        }
    }
    /**
     * ============================================
     * LOG ACTION
     * ============================================
     */
    $utility->logActivity(
        "Payment ID {$id} marked as {$status} by Admin ID {$_SESSION['admin_id']}"
    );

    /**
     * ============================================
     * COMMIT TRANSACTION
     * ============================================
     */
    $model->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Payment updated successfully"
    ]);
} catch (Exception $e) {

    /**
     * ============================================
     * ROLLBACK ON ERROR
     * ============================================
     */
    try {
        $model->rollBack();
    } catch (Exception $ex) {
    }

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
