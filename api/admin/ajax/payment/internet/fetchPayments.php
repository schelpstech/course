<?php
require_once '../../../../../start.inc.php';

header('Content-Type: application/json');

// 🔐 Restrict access to admins only
$utility->requireAdmin();

try {

    /**
     * =====================================================
     * FETCH ALL COURSE REGISTRATION RECORDS
     * =====================================================
     * - We still use payments table
     * - These records represent "clearance requests"
     * - No dependency on proof or online transaction
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

            -- student identity
            s.first_name,
            s.last_name,
            s.other_name,
            s.matric_no
        ",

        "joinl" => [
            "students s" => " ON s.student_id = p.student_id",
            "institutions i" => " ON i.id = s.institution_id"
        ],

        "where" => [
            "p.payment_type" => "course_reg"
        ],

        "order_by" => "p.created_at DESC"
    ]);


    /**
     * =====================================================
     * GET ACTIVE SEMESTER + EXPECTED FEES
     * =====================================================
     */
    $semesterData = $model->getRows('semesters', [
        "where" => ["is_active" => 1],
        "return_type" => "single"
    ]);

    $semesterId = $semesterData['id'] ?? 0;
    $sessionId  = $semesterData['session_id'] ?? 0;

    $semesterFees = (float) $model->sumQuery('fees', 'amount', [
        "where" => [
            "semester_id" => $semesterId,
            "session_id"  => $sessionId
        ]
    ]);


    /**
     * =====================================================
     * PROCESS RECORDS
     * =====================================================
     */
    $data = [];

    foreach ($rows as $row) {

        // -----------------------------
        // SAFE VALUES
        // -----------------------------
        $expected = $semesterFees;
        $paid     = (float)($row['amount_paid'] ?? 0);

        /**
         * =====================================================
         * ADMIN DECISION CONTEXT
         * =====================================================
         * No proof required
         * Admin decides based on external school list
         */
        $canApprove     = true;
        $recommendation = "approve";
        $message        = "Awaiting admin confirmation (school payment list)";

        // Optional sanity check
        if ($expected <= 0) {
            $canApprove     = false;
            $recommendation = "warning";
            $message        = "No fee configured for current semester";
        }

        // -----------------------------
        // BUILD STUDENT NAME
        // -----------------------------
        $studentName = trim(
            ($row['first_name'] ?? '') . ' ' .
            ($row['other_name'] ?? '') . ' ' .
            ($row['last_name'] ?? '')
        );

        if (empty($studentName)) {
            $studentName = "Unknown Student";
        }

        // -----------------------------
        // ACTION BUTTONS
        // -----------------------------
        $actions = "";

        /**
         * PENDING → ADMIN REVIEW
         */
        if ($row['status'] === 'pending') {

            $actions = "
                <button class='btn btn-primary btn-sm reviewPaymentBtn'
                    data-id='{$row['id']}'
                    data-ref='{$row['paymentReference']}'
                    data-expected='{$expected}'
                    data-paid='{$paid}'
                    data-recommendation='{$recommendation}'
                    data-message='{$message}'
                    data-canapprove='{$canApprove}'
                    data-source='school_clearance'>
                    Confirm Clearance
                </button>
            ";
        }

        /**
         * SUCCESSFUL → CLEARED
         */
        elseif ($row['status'] === 'successful') {

            $actions = "<span class='badge bg-success'>Cleared</span>";
        }

        /**
         * FAILED → REJECTED
         */
        elseif ($row['status'] === 'failed') {

            $actions = "<span class='badge bg-danger'>Rejected</span>";
        }

        /**
         * DEFAULT FALLBACK
         */
        else {
            $actions = "<span class='badge bg-warning text-dark'>Pending</span>";
        }

        // -----------------------------
        // STATUS BADGE
        // -----------------------------
        $statusBadge = match ($row['status']) {
            'successful' => "<span class='badge bg-success'>Successful</span>",
            'failed'     => "<span class='badge bg-danger'>Rejected</span>",
            default      => "<span class='badge bg-warning text-dark'>Pending</span>",
        };

        /**
         * =====================================================
         * FINAL OUTPUT FORMAT
         * =====================================================
         */
        $data[] = [
            "student_name"     => ucfirst($studentName),
            "matric"           => $row['matric_no'] ?? "-",
            "paymentReference" => $row['paymentReference'],
            "payment_type"     => $row['payment_type'],
            "amount_paid"      => "₦" . number_format($paid, 2),
            "payment_mode"     => ucfirst($row['payment_mode'] ?? 'Online'),
            "status"           => $statusBadge,
            "payment_date"     => $row['payment_date'],
            "actions"          => $actions
        ];
    }

    /**
     * =====================================================
     * SUCCESS RESPONSE
     * =====================================================
     */
    echo json_encode(["data" => $data]);

} catch (Exception $e) {

    /**
     * =====================================================
     * ERROR RESPONSE
     * =====================================================
     */
    echo json_encode([
        "data"  => [],
        "error" => $e->getMessage()
    ]);
}