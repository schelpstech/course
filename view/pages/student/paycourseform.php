<?php
// Fees for that session + semester
$semester_fees = $model->getRows('fees', [
    'where' => [
        'session_id' => $activeSession['id'],
        'semester_id' => $activeSemester['id']
    ]
]);

$total = 0;
foreach ($semester_fees as $fee) {
    $total += $fee['amount'];
}

$invoiceRef = 'INV-' . strtoupper(bin2hex(random_bytes(3)));
?>

<div class="row">
    <div class="col-sm-12">

        <!-- ACTION BAR -->
        <div class="d-print-none card mb-3">
            <div class="card-body p-3">
                <ul class="list-inline ms-auto mb-0 d-flex justify-content-end flex-wrap">

                    <!-- PRINT BUTTON -->
                    <li class="list-inline-item">
                        <button onclick="window.print()" class="btn-print">
                            <i class="ph ph-printer"></i> Print
                        </button>
                    </li>

                    <!-- PAY NOW BUTTON -->
                    <li class="list-inline-item">
                        <form method="POST" action="../api/student/paymentHandler.php">
                            <input type="hidden" name="csrf_token" value="<?= $utility->generateCsrf('courseformpayment'); ?>">
                            <input type="hidden" name="session_id" value="<?= $activeSession['id']; ?>">
                            <input type="hidden" name="semester_id" value="<?= $activeSemester['id']; ?>">

                            <button type="submit" class="btn-pay-now">
                                <i class="ph ph-credit-card"></i>
                                PAY NOW
                            </button>
                        </form>
                    </li>

                </ul>
            </div>
        </div>

        <!-- INVOICE -->
        <div class="card">
            <div class="card-body">

                <div class="row g-3">

                    <!-- HEADER -->
                    <div class="col-12">
                        <div class="row align-items-center g-3">

                            <div class="col-sm-6">
                                <div class="d-flex align-items-center mb-2">
                                    <img src="../assets/images/logo.png" class="img-fluid brand-logo" />
                                </div>
                                <button class="btn btn-danger"> <b> UNPAID INVOICE : <?= $invoiceRef; ?></b></button>
                            </div>

                            <div class="col-sm-6 text-sm-end">
                                <h6>Date <span class="text-muted"><?= date('d/m/Y'); ?></span></h6>
                                <h6>Session <span class="text-muted"><?= $activeSession['name']; ?></span></h6>
                                <h6>Semester <span class="text-muted"><?= $activeSemester['name']; ?></span></h6>
                            </div><br>
                                <hr>

                        </div>
                    </div>

                    <!-- FROM -->
                    <div class="col-sm-6">
                        <div class="border rounded p-3">
                            <h6>From:</h6>
                            <h5><?= $institution['name'] ?? 'Institution Name'; ?></h5>
                            <p class="mb-0"><?= $institution['inst_email'] ?? 'Institution Email'; ?></p>
                            <p class="mb-0"><?= $institution['inst_address'] ?? 'Institution Address'; ?></p>
                        </div>
                    </div>

                    <!-- TO -->
                    <div class="col-sm-6">
                        <div class="border rounded p-3">
                            <h6>To:</h6>
                            <h5><?= $student['first_name'] . ' ' . $student['last_name']; ?></h5>
                            <p class="mb-0"><?= $student['matric_no']; ?></p>
                            <p class="mb-0"><?= $user_email; ?></p>
                        </div>
                    </div>

                    <!-- TABLE -->
                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Fee</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php $i = 1;
                                    foreach ($semester_fees as $fee): ?>
                                        <tr>
                                            <td><?= $i++; ?></td>
                                            <td><?= $fee['fee_name']; ?></td>
                                            <td class="text-end">₦<?= number_format($fee['amount'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>

                            </table>
                        </div>

                        <hr>
                    </div>

                    <!-- TOTAL -->
                    <div class="col-12">
                        <div class="invoice-total ms-auto" style="width: 400px">
                            <div class="row">

                                <div class="col-6">
                                    <p class="f-w-600 text-start">Total :</p>
                                </div>

                                <div class="col-6">
                                    <p class="f-w-600 text-end text-primary">
                                        ₦<?= number_format($total, 2); ?>
                                    </p>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- NOTE -->
                    <div class="col-12">
                        <label class="form-label">Note</label>
                        <p class="mb-0">
                            Please complete your payment to proceed with course registration.
                        </p>
                    </div>

                </div>

            </div>
        </div>

    </div>
</div>