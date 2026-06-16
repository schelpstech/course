<?php

require_once '../../../../start.inc.php';

header('Content-Type: application/json');

$utility->requireAdmin();

$semester_registration_id =
    $_POST['semester_registration_id'] ?? 0;

try {

    $stmt = $db->prepare("
        SELECT

            sr.id AS semester_registration_id,

            s.student_id,

            s.institution_id,

            s.passport,

            s.matric_no,

            CONCAT(
                s.first_name,' ',
                IFNULL(s.other_name,''),' ',
                s.last_name
            ) AS student_name,

            i.name AS institution_name,

            d.name AS department_name,

            l.name AS level_name,

            ac.name AS session_name,

            sem.name AS semester_name,

            sr.semester_id,

            sfs.amount AS school_fee,

            ipt.min_percent,

            ROUND(
                (sfs.amount * ipt.min_percent / 100),
                2
            ) AS required_amount,

            ct.id AS clearance_type_id,

            sc.status AS clearance_status

        FROM semesterregistration sr

        INNER JOIN students s
            ON s.student_id = sr.student_id

        INNER JOIN institutions i
            ON i.id = s.institution_id

        INNER JOIN department d
            ON d.id = s.department_id

        INNER JOIN levels l
            ON l.id = sr.studentLevelId

        INNER JOIN semesters sem
            ON sem.id = sr.semester_id

        INNER JOIN academic_sessions ac
            ON ac.id = sem.session_id

        INNER JOIN school_fee_settings sfs
            ON sfs.semester_id = sr.semester_id
            AND sfs.department_id = s.department_id
            AND sfs.level_id = sr.studentLevelId

        INNER JOIN institution_payment_terms ipt
            ON ipt.institution_id = s.institution_id
            AND ipt.status = 1

        LEFT JOIN clearance_types ct
            ON ct.institution_id = s.institution_id
            AND ct.code = 'PAYMENT'
            AND ct.status = 1

        LEFT JOIN student_clearances sc
            ON sc.semester_registration_id = sr.id
            AND sc.clearance_type_id = ct.id

        WHERE sr.id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $semester_registration_id
    ]);

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {

        exit(json_encode([
            'status' => 'error',
            'message' => 'Record not found'
        ]));
    }

    /**
     * -----------------------------------
     * PAYMENT HISTORY
     * -----------------------------------
     */
    $payStmt = $db->prepare("
        SELECT
            paymentReference,
            amount_paid,
            payment_date,
            payment_mode,
            status,
            admin_note
        FROM payments
        WHERE student_id = ?
        AND semester_id = ?
        AND payment_type = 'school_fee'
        ORDER BY payment_date DESC
    ");

    $payStmt->execute([
        $data['student_id'],
        $data['semester_id']
    ]);

    $payments =
        $payStmt->fetchAll(PDO::FETCH_ASSOC);

    /**
     * -----------------------------------
     * SUCCESSFUL PAYMENT TOTAL
     * -----------------------------------
     */
    $amountPaid = 0;

    foreach ($payments as $payment) {

        if ($payment['status'] === 'successful') {

            $amountPaid +=
                (float)$payment['amount_paid'];
        }
    }

    /**
     * -----------------------------------
     * PASSPORT VALIDATION
     * -----------------------------------
     */
    $data['passport_url'] = null;

    if (!empty($data['passport'])) {

        $passportPath =
            "../../../../uploads/passports/" .
            $data['passport'];

        if (file_exists($passportPath)) {

            $data['passport_url'] =
                "uploads/passports/" .
                $data['passport'];
        }
    }

    /**
     * -----------------------------------
     * CALCULATED VALUES
     * -----------------------------------
     */
    $requiredAmount =
        (float)$data['required_amount'];

    $data['amount_paid'] =
        $amountPaid;

    $data['payment_balance'] =
        max(
            0,
            $requiredAmount - $amountPaid
        );

    $data['eligible'] =
        $amountPaid >= $requiredAmount
        ? 1
        : 0;

    $data['clearance_status'] =
        $data['clearance_status']
        ?: 'pending';

    $data['payments'] =
        $payments;

    echo json_encode([
        'status' => 'success',
        'data' => $data
    ]);

} catch (Exception $e) {

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}