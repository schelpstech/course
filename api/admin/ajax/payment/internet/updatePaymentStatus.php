<?php
require_once '../../../../../start.inc.php';

header('Content-Type: application/json');

// 🔐 Admin only
$utility->requireAdmin();

try {

    /**
     * ============================================
     * VALIDATE INPUT
     * ============================================
     */
    $id     = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null;
    $note   =  htmlspecialchars($_POST['note'] ?? '', ENT_QUOTES);

    if (!$id || !$status) {
        throw new Exception("Missing parameters");
    }

    if (!in_array($status, ['successful', 'failed'])) {
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
     * FETCH TARGET PAYMENT
     * ============================================
     */
    $payment = $model->getById('payments', $id);

    if (!$payment) {
        throw new Exception("Payment not found");
    }

    $studentId  = $payment['student_id'];
    $semesterId = $payment['semester_id'];

    /**
     * ============================================
     * FETCH ALL COURSE REG PAYMENTS FOR STUDENT
     * ============================================
     */
    $allPayments = $model->getRows('payments', [
        "where" => [
            "student_id"   => $studentId,
            "semester_id"  => $semesterId,
            "payment_type" => "course_reg"
        ]
    ]);

    /**
     * ============================================
     * CHECK IF SUCCESSFUL RECORD EXISTS
     * ============================================
     */
    $existingSuccessful = array_filter($allPayments, function ($p) {
        return $p['status'] === 'successful';
    });

    /**
     * ============================================
     * CASE: APPROVAL REQUEST
     * ============================================
     */
    if ($status === 'successful') {

        // ❌ If another successful record exists → reject this one
        if (!empty($existingSuccessful)) {

            $model->update('payments', [
                'status'       => 'failed',
                'admin_note'   => 'Duplicate record - already cleared',
                'approved_by'  => $_SESSION['admin_id'],
                'approved_at'  => date('Y-m-d H:i:s')
            ], ['id' => $id]);

            throw new Exception("Student already has a successful clearance");
        }

        /**
         * ============================================
         * APPROVE SELECTED RECORD
         * ============================================
         */
        $model->update('payments', [
            'status'       => 'successful',
            'payment_mode' => 'manual', // 🔥 IMPORTANT
            'admin_note'   => $note ?: ' - Cleared manually via admin portal',
            'approved_by'  => $_SESSION['admin_id'],
            'approved_at'  => date('Y-m-d H:i:s')
        ], ['id' => $id]);

        /**
         * ============================================
         * FAIL OTHER DUPLICATE PENDING RECORDS
         * ============================================
         */
        foreach ($allPayments as $p) {

            if ($p['id'] != $id && $p['status'] === 'pending') {

                $model->update('payments', [
                    'status'       => 'failed',
                    'admin_note'   => 'Duplicate record',
                    'approved_by'  => $_SESSION['admin_id'],
                    'approved_at'  => date('Y-m-d H:i:s')
                ], ['id' => $p['id']]);
            }
        }
    }

    /**
     * ============================================
     * CASE: REJECTION REQUEST
     * ============================================
     */
    else {

        $model->update('payments', [
            'status'       => 'failed',
            'admin_note'   => $note ?: 'Rejected by admin',
            'approved_by'  => $_SESSION['admin_id'],
            'approved_at'  => date('Y-m-d H:i:s')
        ], ['id' => $id]);
    }

    /**
     * ============================================
     * UPDATE REGISTRATION IF SUCCESSFUL
     * ============================================
     */
    if ($status === 'successful') {

        $semester = $model->getById('semesters', $semesterId);

        $model->update('semesterregistration', [
            'payment_confirmed' => 1,
            'confirmed_at' => date('Y-m-d H:i:s')
        ], [
            'student_id'  => $studentId,
            'session_id'  => $semester['session_id'],
            'semester_id' => $semesterId
        ]);

        $utility->logActivity(
            "Course registration clearance approved for student {$studentId} by admin {$_SESSION['admin_id']}"
        );
    }

    /**
     * ============================================
     * COMMIT
     * ============================================
     */
    $model->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Payment processed successfully"
    ]);
} catch (Exception $e) {

    try {
        $model->rollBack();
    } catch (Exception $ex) {
    }

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
