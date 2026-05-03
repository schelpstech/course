<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');

$utility->requireAdmin();

try {

    // =========================
    // VALIDATE INPUT
    // =========================
    $id     = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null;
    $note   = $_POST['note'] ?? '';

    if (!$id || !$status) {
        throw new Exception("Missing required parameters");
    }

    $allowedStatus = ['successful', 'failed'];

    if (!in_array($status, $allowedStatus)) {
        throw new Exception("Invalid status value");
    }

    // =========================
    // BEGIN TRANSACTION
    // =========================
    $model->beginTransaction();

    // =========================
    // UPDATE PAYMENT
    // =========================
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

    // =========================
    // GET PAYMENT DETAILS
    // =========================
    $payerDetails = $model->getById('payments', $id);

    if (!$payerDetails) {
        throw new Exception("Payment record not found");
    }

    // =========================
    // SUCCESS FLOW ONLY
    // =========================
    if ($status === 'successful') {

        $sessionDetails = $model->getById('semesters', $payerDetails['semester_id']);

        if (!$sessionDetails) {
            throw new Exception("Semester not found");
        }

        $registrationUpdate = $model->update('semesterregistration', [
            'payment_confirmed' => 1,
            'confirmed_at'      => date('Y-m-d H:i:s')
        ], [
            'student_id'  => $payerDetails['student_id'],
            'session_id'  => $sessionDetails['session_id'],
            'semester_id' => $payerDetails['semester_id'],
        ]);

        if (!$registrationUpdate) {
            throw new Exception("Failed to update semester registration");
        }

        $utility->logActivity(
            'Payment approved for student ID ' . $payerDetails['student_id'] .
                ' (Semester ID: ' . $payerDetails['semester_id'] . ') by Admin ID: ' . $_SESSION['admin_id']
        );
    }

    // =========================
    // GENERAL LOG
    // =========================
    $utility->logActivity(
        'Payment ID ' . $id . ' updated to "' . $status . '" by Admin ID: ' . $_SESSION['admin_id']
    );

    // =========================
    // COMMIT
    // =========================
    $model->commit();

    echo json_encode([
        "status"  => "success",
        "message" => "Payment updated successfully"
    ]);
} catch (Exception $e) {

    // =========================
    // ROLLBACK ON ERROR
    // =========================
    if ($model->inTransaction()) {
        $model->rollBack();
    }

    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage()
    ]);
}
