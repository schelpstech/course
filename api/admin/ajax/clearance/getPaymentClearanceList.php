<?php

require_once '../../../../start.inc.php';

header('Content-Type: application/json');

$utility->requireAdmin();

try {

    $sql = "
        SELECT

            sr.id AS semester_registration_id,

            s.student_id,

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

            sfs.amount AS school_fee,

            ipt.min_percent,

            ROUND(
                (sfs.amount * ipt.min_percent / 100),
                2
            ) AS required_amount,

            COALESCE((
                SELECT SUM(p.amount_paid)
                FROM payments p
                WHERE p.student_id = sr.student_id
                AND p.semester_id = sr.semester_id
                AND p.payment_type = 'school_fee'
                AND p.status = 'successful'
            ),0) AS amount_paid,

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

        INNER JOIN school_fee_settings sfs
            ON sfs.semester_id = sr.semester_id
            AND sfs.department_id = s.department_id
            AND sfs.level_id = sr.studentLevelId

        INNER JOIN institution_payment_terms ipt
            ON ipt.institution_id = s.institution_id
            AND ipt.status = 1

        INNER JOIN semesters sem
            ON sem.id = sr.semester_id

        INNER JOIN academic_sessions ac
            ON ac.id = sem.session_id

        LEFT JOIN clearance_types ct
            ON ct.institution_id = s.institution_id
            AND ct.code = 'PAYMENT'
            AND ct.status = 1

        LEFT JOIN student_clearances sc
            ON sc.semester_registration_id = sr.id
            AND sc.clearance_type_id = ct.id

        ORDER BY student_name ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute();

    $rows = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $amountPaid = (float)$row['amount_paid'];
        $requiredAmount = (float)$row['required_amount'];

        $row['eligible'] =
            $amountPaid >= $requiredAmount ? 1 : 0;

        $row['payment_balance'] =
            max(
                0,
                $requiredAmount - $amountPaid
            );

        $row['clearance_status'] =
            $row['clearance_status'] ?: 'pending';

        $rows[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $rows
    ]);

} catch (Exception $e) {

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}