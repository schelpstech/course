<?php

require_once '../../../../start.inc.php';

header('Content-Type: application/json');

$utility->requireAdmin();

$semester_registration_id =
    $_POST['semester_registration_id'] ?? 0;

$clearance_type_id =
    $_POST['clearance_type_id'] ?? 0;

$remark =
    trim($_POST['remark'] ?? '');

if (
    empty($semester_registration_id) ||
    empty($clearance_type_id)
) {
    exit(json_encode([
        'status' => 'error',
        'message' => 'Invalid request'
    ]));
}

try {
    /**
     * -----------------------------------
     * GET REGISTRATION DETAILS
     * -----------------------------------
     */
    $stmt = $db->prepare('
        SELECT

            sr.id,
            sr.student_id,
            sr.semester_id,

            s.institution_id,
            s.department_id,

            sr.studentLevelId,

            sfs.amount AS school_fee,

            ipt.min_percent

        FROM semesterregistration sr

        INNER JOIN students s
            ON s.student_id = sr.student_id

        INNER JOIN school_fee_settings sfs
            ON sfs.semester_id = sr.semester_id
            AND sfs.department_id = s.department_id
            AND sfs.level_id = sr.studentLevelId

        INNER JOIN institution_payment_terms ipt
            ON ipt.institution_id = s.institution_id

        WHERE sr.id = ?
        LIMIT 1
    ');

    $stmt->execute([
        $semester_registration_id
    ]);

    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        exit(json_encode([
            'status' => 'error',
            'message' => 'Registration record not found'
        ]));
    }

    /**
     * -----------------------------------
     * VERIFY PAYMENT CLEARANCE TYPE
     * -----------------------------------
     */
    $typeStmt = $db->prepare("
        SELECT *
        FROM clearance_types
        WHERE id = ?
        AND institution_id = ?
        AND code = 'PAYMENT'
        LIMIT 1
    ");

    $typeStmt->execute([
        $clearance_type_id,
        $record['institution_id']
    ]);

    $clearanceType = $typeStmt->fetch(PDO::FETCH_ASSOC);

    if (!$clearanceType) {
        exit(json_encode([
            'status' => 'error',
            'message' => 'Invalid clearance type'
        ]));
    }

    /**
     * -----------------------------------
     * CALCULATE REQUIRED AMOUNT
     * -----------------------------------
     */
    $requiredAmount =
        ($record['school_fee']
            * $record['min_percent']) / 100;

    /**
     * -----------------------------------
     * CALCULATE SUCCESSFUL PAYMENTS
     * -----------------------------------
     */
    $paymentStmt = $db->prepare("
        SELECT
            COALESCE(
                SUM(amount_paid),
                0
            ) AS total_paid

        FROM payments

        WHERE student_id = ?
        AND semester_id = ?
        AND payment_type = 'school_fee'
        AND status = 'successful'
    ");

    $paymentStmt->execute([
        $record['student_id'],
        $record['semester_id']
    ]);

    $paymentData = $paymentStmt->fetch(PDO::FETCH_ASSOC);

    $amountPaid =
        (float) $paymentData['total_paid'];

    /**
     * -----------------------------------
     * ELIGIBILITY CHECK
     * -----------------------------------
     */
    if ($amountPaid < $requiredAmount) {
        exit(json_encode([
            'status' => 'error',
            'message' =>
                'Student is not eligible for payment clearance. '
                . 'Required: ₦'
                . number_format($requiredAmount, 2)
                . ', Paid: ₦'
                . number_format($amountPaid, 2)
        ]));
    }

    /**
     * -----------------------------------
     * PREVENT DUPLICATE APPROVAL
     * -----------------------------------
     */
    $existing = $db->prepare('
    SELECT clearance_status
    FROM student_clearances
    WHERE semester_registration_id = ?
    AND clearance_type_id = ?
    LIMIT 1
');

    $existing->execute([
        $semester_registration_id,
        $clearance_type_id
    ]);

    $current = $existing->fetch(PDO::FETCH_ASSOC);

    if (
        $current &&
        $current['clearance_status'] === 'approved'
    ) {
        exit(json_encode([
            'status' => 'error',
            'message' => 'Payment clearance has already been approved'
        ]));
    }

    /**
     * -----------------------------------
     * APPROVE CLEARANCE
     * -----------------------------------
     */
    $userId = $_SESSION['admin_id'];

    $approveStmt = $db->prepare("
        INSERT INTO student_clearances
        (
            semester_registration_id,
            clearance_type_id,
            clearance_status,
            remark,
            approved_by,
            approved_at
        )
        VALUES
        (
            ?,
            ?,
            'approved',
            ?,
            ?,
            NOW()
        )

        ON DUPLICATE KEY UPDATE

            clearance_status='approved',
            remark=VALUES(remark),
            approved_by=VALUES(approved_by),
            approved_at=NOW()
    ");

    $approveStmt->execute([
        $semester_registration_id,
        $clearance_type_id,
        $remark,
        $userId
    ]);

    $utility->logActivity(
        'Approved Payment Clearance | Semester Registration ID: '
        . $semester_registration_id
    );

    echo json_encode([
        'status' => 'success',
        'message' => 'Payment clearance approved successfully'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
