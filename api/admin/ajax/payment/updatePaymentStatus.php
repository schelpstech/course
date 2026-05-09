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
    $student = $model->getById('students', $payment['student_id']);

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
     * BUSINESS VALIDATION (CRITICAL)
     * ============================================
     */
    if ($payment['payment_type'] === 'school_fee') {

        $expectedAmount = (float)($feeSettings['amount'] ?? 0);
        $paidAmount = (float)$payment['amount_paid'];
        $requiredPercent = (float)($institutionRules['min_percent'] ?? 100);

        $requiredAmount = ($expectedAmount * $requiredPercent) / 100;

        /**
         * ❌ BLOCK INVALID APPROVALS
         */
        if ($status === 'successful' && $paidAmount < $requiredAmount) {
            throw new Exception(
                "Cannot approve. Student has paid " .
                round(($paidAmount / max($expectedAmount, 1)) * 100, 2) .
                "% but required is {$requiredPercent}%"
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
         * PREVENT DOUBLE REGISTRATION UPDATE
         * ============================================
         */
        $existingReg = $model->getRows('semesterregistration', [
            "where" => [
                "student_id" => $payment['student_id'],
                "semester_id" => $payment['semester_id']
            ],
            "return_type" => "single"
        ]);

        if ($existingReg && $existingReg['payment_confirmed'] == 1) {
            throw new Exception("Registration already confirmed for this semester");
        }

        /**
         * UPDATE REGISTRATION
         */
        $regUpdate = $model->update('semesterregistration', [
            'payment_confirmed' => 1,
            'confirmed_at' => date('Y-m-d H:i:s')
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
    } catch (Exception $ex) {}

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}