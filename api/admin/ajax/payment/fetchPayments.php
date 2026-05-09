<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');

// 🔐 Ensure only admins can access
$utility->requireAdmin();

try {

    /**
     * ============================================
     * FETCH ALL PAYMENTS (NO LOSS OF RECORDS)
     * ============================================
     * - payments is the base table
     * - all joins are LEFT JOIN to avoid missing records
     */

    $rows = $model->getRows('payments p', [

        "select" => "
            p.*,

            -- student academic structure
            s.student_id,
            s.level_id,
            s.department_id,
            s.programme_id,
            s.institution_id,

            -- payment rules
            it.min_percent AS payment_percentage,
            f.amount AS expected_amount,

            -- student identity
            s.first_name,
            s.last_name,
            s.other_name,
            s.matric_no
        ",

        /**
         * ============================================
         * USE LEFT JOIN (CRITICAL FIX)
         * ============================================
         * This ensures ALL payments are returned
         */
        "joinl" => [

            // student table
            "students s" => " ON s.student_id = p.student_id",

            // school fee settings (only relevant for school_fee)
            "school_fee_settings f" => "
                ON f.level_id = s.level_id
                AND f.department_id = s.department_id
                AND f.semester_id = p.semester_id
            ",

            // institution
            "institutions i" => " ON i.id = s.institution_id",

            // payment terms (percentage rule)
            "institution_payment_terms it" => "
                ON it.institution_id = s.institution_id
            "
        ],

        "order_by" => "p.created_at DESC"
    ]);


    /**
     * ============================================
     * BUILD RESPONSE
     * ============================================
     */
    $data = [];

    foreach ($rows as $row) {

        // -----------------------------
        // SAFE VALUE EXTRACTION
        // -----------------------------
        $expected   = (float)($row['expected_amount'] ?? 0);
        $paid       = (float)($row['amount_paid'] ?? 0);
        $percentage = (float)($row['payment_percentage'] ?? 100);

        // calculate required minimum payment
        $required = ($expected * $percentage) / 100;

        // -----------------------------
        // DEFAULT RECOMMENDATION
        // -----------------------------
        $canApprove = true;
        $recommendation = "approve";
        $message = "Meets payment requirement";

        /**
         * ============================================
         * APPLY LOGIC ONLY FOR SCHOOL FEES
         * ============================================
         */
        if ($row['payment_type'] === 'school_fee') {

            if ($expected <= 0) {
                $canApprove = false;
                $recommendation = "warning";
                $message = "No fee configured for this level";
            } elseif ($paid < $required) {
                $canApprove = false;
                $recommendation = "reject";
                $message = "Below required threshold ({$percentage}%)";
            }
        }

        // -----------------------------
        // BUILD STUDENT NAME SAFELY
        // -----------------------------
        $studentName = trim(
            ($row['first_name'] ?? '') . ' ' .
                ($row['other_name'] ?? '') . ' ' .
                ($row['last_name'] ?? '')
        );

        $studentName = $studentName ?: "Unknown Student";

        // -----------------------------
        // ACTION BUTTON
        // -----------------------------
        $actions = "";

        // =============================
        // PENDING SCHOOL FEES → REVIEW
        // =============================
        if ($row['payment_type'] === 'school_fee' && $row['status'] === 'pending') {

            $actions = "
        <button class='btn btn-primary btn-sm reviewPaymentBtn'
            data-id='{$row['id']}'
            data-proof='{$row['payment_proof']}'
            data-ref='{$row['paymentReference']}'
            data-expected='{$expected}'
            data-paid='{$paid}'
            data-required='{$required}'
            data-percentage='{$percentage}'
            data-recommendation='{$recommendation}'
            data-message='{$message}'
            data-canapprove='{$canApprove}'>
            Review
        </button>
    ";
        }

        // =============================
        // COMPLETED SCHOOL FEES → VIEW
        // =============================
        elseif (
            $row['payment_type'] === 'school_fee' &&
            ($row['status'] === 'successful' || $row['status'] === 'failed')
        ) {

            if (!empty($row['payment_proof'])) {
                $actions = "
            <a href='../{$row['payment_proof']}' target='_blank'
                class='btn btn-info btn-sm'>
                View Proof
            </a>
        ";
            } else {
                $actions = "<span class='badge bg-secondary'>No Proof</span>";
            }
        }

        // =============================
        // OTHER PAYMENTS
        // =============================
        else {

            if ($row['status'] === 'successful') {
                $actions = "<span class='badge bg-success'>Successful</span>";
            } elseif ($row['status'] === 'failed') {
                $actions = "<span class='badge bg-danger'>Failed</span>";
            } else {
                $actions = "<span class='badge bg-warning text-dark'>Pending</span>";
            }
        }



        // -----------------------------
        // FORMAT OUTPUT
        // -----------------------------
        $data[] = [
            "student_name" => ucfirst($studentName),
            "matric" => $row['matric_no'] ?? "-",
            "paymentReference" => $row['paymentReference'],
            "payment_type" => $row['payment_type'],
            "amount_paid" => "₦" . number_format($paid, 2),
            "payment_mode" => $row['payment_mode'],
            "status" => $row['status'],
            "payment_date" => $row['payment_date'],
            "actions" => $actions
        ];
    }

    /**
     * ============================================
     * SUCCESS RESPONSE
     * ============================================
     */
    echo json_encode(["data" => $data]);
} catch (Exception $e) {

    /**
     * ============================================
     * ERROR RESPONSE
     * ============================================
     */
    echo json_encode([
        "data" => [],
        "error" => $e->getMessage()
    ]);
}
