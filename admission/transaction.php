<?php
include './partials/header.php';

$applicantId = (int) ($_SESSION['admission_applicant_id'] ?? 0);

if (!$applicantId) {
    header("Location: index.php");
    exit;
}

$application = $admission->getApplicationForApplicant($applicantId);

if (!$application) {
    header("Location: dashboard.php");
    exit;
}

$activeSession = $admission->activeSession();

require_once './helpers/admission_helper.php';
/*
|--------------------------------------------------------------------------
| Get Applicant Transactions
|--------------------------------------------------------------------------
|
| Replace this query if your table names differ
|
*/

$stmt = $db->prepare("
    SELECT *
    FROM admission_payments
    WHERE application_id = ?
    ORDER BY id DESC
");

$stmt->execute([$application['id']]);

$transactions = $stmt->fetchAll();

$totalTransactions = count($transactions);

$successful = count(array_filter($transactions, fn($t) => $t['status'] === 'paid'));
$pending = count(array_filter($transactions, fn($t) => $t['status'] === 'pending'));
$failed = count(array_filter($transactions, fn($t) => $t['status'] === 'failed'));
?>

<div class="dashboard-layout">

    <?php include './partials/sidebar.php'; ?>

    <div class="dashboard-content">

        <?php include './partials/topbar.php'; ?>

        <main class="container-fluid py-4">

            <section class="dashboard-hero">

                <div class="hero-content">

                    <div>

                        <span class="hero-badge">
                            PAYMENT CENTER
                        </span>

                        <h2>Transaction History</h2>

                        <p>
                            View all application payments, download receipts,
                            and revalidate pending transactions.
                        </p>

                    </div>


                </div>

            </section>

            <!-- SUMMARY CARDS -->
            <div class="row g-4 mb-4">

                <div class="col-xl-3 col-md-6">

                    <div class="metric-card">

                        <div class="metric-icon metric-blue">
                            <i class="bi bi-receipt"></i>
                        </div>

                        <small>Total Transactions</small>

                        <h4><?= $totalTransactions ?></h4>

                    </div>

                </div>

                <div class="col-xl-3 col-md-6">

                    <div class="metric-card">

                        <div class="metric-icon metric-green">
                            <i class="bi bi-check-circle"></i>
                        </div>

                        <small>Successful</small>

                        <h4><?= $successful ?></h4>

                    </div>

                </div>

                <div class="col-xl-3 col-md-6">

                    <div class="metric-card">

                        <div class="metric-icon metric-orange">
                            <i class="bi bi-clock-history"></i>
                        </div>

                        <small>Pending</small>

                        <h4><?= $pending ?></h4>

                    </div>

                </div>

                <div class="col-xl-3 col-md-6">

                    <div class="metric-card">

                        <div class="metric-icon metric-red">
                            <i class="bi bi-x-circle"></i>
                        </div>

                        <small>Failed</small>

                        <h4><?= $failed ?></h4>

                    </div>

                </div>

            </div>

            <!-- TRANSACTION TABLE -->
            <section class="surface p-4">

                <div class="d-flex justify-content-between align-items-center mb-4">

                    <div>

                        <h4 class="mb-1">
                            Payment Transactions
                        </h4>

                        <small class="text-muted">
                            All application related payments
                        </small>

                    </div>

                </div>

                <div class="table-responsive">

                    <table class="table align-middle transaction-table">

                        <thead>

                            <tr>

                                <th>Date</th>

                                <th>Invoice No</th>

                                <th>Payment Type</th>

                                <th>Amount</th>

                                <th>Status</th>

                                <th width="220">
                                    Actions
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if (!$transactions): ?>

                                <tr>

                                    <td colspan="6" class="text-center py-5">

                                        <i class="bi bi-receipt display-4 d-block mb-3 text-muted"></i>

                                        <h5>
                                            No Transactions Found
                                        </h5>

                                    </td>

                                </tr>

                            <?php endif; ?>

                            <?php foreach ($transactions as $transaction): ?>

                                <tr>

                                    <td>

                                        <?= date('d M Y h:i A', strtotime($transaction['created_at'])) ?>

                                    </td>

                                    <td>

                                        <strong>
                                            <?= h($transaction['invoice_no']) ?>
                                        </strong>

                                    </td>

                                    <td>

                                        <?= ucwords(str_replace('_', ' ', $transaction['payment_type'])) ?>

                                    </td>

                                    <td>

                                        ₦<?= number_format((float)$transaction['amount'], 2) ?>

                                    </td>

                                    <td>
                                        <?php if ($transaction['status'] == 'paid'): ?>
                                            <span class="badge bg-success">
                                                Paid
                                            </span>
                                        <?php elseif ($transaction['status'] == 'pending'): ?>
                                            <span class="badge bg-warning text-dark">
                                                Pending
                                            </span>
                                        <?php elseif ($transaction['status'] == 'failed'): ?>
                                            <span class="badge bg-danger">
                                                Failed
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                Unpaid
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($transaction['status'] === 'paid'): ?>

                                            <a
                                                href="../api/admission/download-receipt.php?id=<?= (int)$transaction['id'] ?>"
                                                class="btn btn-success btn-sm">

                                                <i class="bi bi-download"></i>
                                                Receipt

                                            </a>


                                        <?php else: ?>

                                            <button
                                                type="button"
                                                class="btn btn-sm btn-warning revalidate-payment"
                                                data-id="<?= $transaction['id'] ?>">

                                                <i class="bi bi-arrow-repeat"></i>

                                                Revalidate

                                            </button>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        </main>

    </div>

</div>

<?php include './partials/footer.php'; ?>

<script>
    $(document).on("click", ".revalidate-payment", function() {
        const paymentId = $(this).data("id");

        Swal.fire({
            title: "Revalidate Payment?",
            text: "This will verify the transaction with the payment gateway.",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Yes, Verify",
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: "../api/admission/revalidate-payment.php",
                method: "POST",
                data: {
                    payment_id: paymentId,
                },

                success(response) {
                    Swal.fire(
                        "Successful",
                        response.message,
                        "success"
                    );

                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                },

                error(xhr) {
                    const response = xhr.responseJSON || {};

                    Swal.fire(
                        "Error",
                        response.message || "Verification failed",
                        "error"
                    );
                },
            });
        });
    });
</script>