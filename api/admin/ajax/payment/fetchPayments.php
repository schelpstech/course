<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');

$utility->requireAdmin();

try {

    $sql = "
        SELECT 
            p.id,
            p.paymentReference,
            p.payment_type,
            p.amount_paid,
            p.payment_date,
            p.payment_mode,
            p.status,
            p.payment_proof,
            p.student_id,
            u.first_name,
            u.other_name,
            u.last_name,
            u.matric_no AS matric
        FROM payments p
        INNER JOIN students u ON u.student_id = p.student_id
        ORDER BY p.created_at DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];

    foreach ($rows as $row) {

        // build name
        $row['student_name'] = ucwords(trim(
            $row['first_name'] . ' ' .
            ($row['other_name'] ?? '') . ' ' .
            $row['last_name']
        ));

        // actions
        $actions = "";

        if ($row['payment_mode'] === "manual" && $row['status'] === "pending") {
            $actions .= "
                <button class='btn btn-primary btn-sm reviewPaymentBtn'
                    data-id='{$row['id']}'
                    data-proof='{$row['payment_proof']}'
                    data-ref='{$row['paymentReference']}'>
                    Review
                </button>
            ";
        } else {
            $actions .= "
                <button class='btn btn-secondary btn-sm viewOnlyBtn'
                    data-id='{$row['id']}'>
                    View
                </button>
            ";
        }

        if (!empty($row['payment_proof'])) {
            $actions .= "
               <br><hr>
                <a href='../{$row['payment_proof']}'
                   target='_blank'
                   class='btn btn-info btn-sm'>
                   Proof
                </a>
            ";
        }

        $row['actions'] = $actions;

        $data[] = $row;
    }

    echo json_encode(["data" => $data]);

} catch (Exception $e) {
    echo json_encode([
        "data" => [],
        "error" => $e->getMessage()
    ]);
}