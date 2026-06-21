<?php
$admissionPortal = new Admission($db, $model, $utility, $qrcode, $mailservice);
$stats = $admissionPortal->dashboardStats();
$recentApplications = array_slice($admissionPortal->applications(), 0, 8);
?>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['label' => 'Total Applications', 'value' => $stats['total_applications'], 'class' => 'bg-primary'],
        ['label' => 'Submitted Applications', 'value' => $stats['submitted_applications'], 'class' => 'bg-info'],
        ['label' => 'Pending Screening', 'value' => $stats['pending_screening'], 'class' => 'bg-warning'],
        ['label' => 'Admitted Candidates', 'value' => $stats['admitted_candidates'], 'class' => 'bg-success'],
        ['label' => 'Acceptance Fee Paid', 'value' => $stats['acceptance_fee_paid'], 'class' => 'bg-success'],
        ['label' => 'Acceptance Outstanding', 'value' => $stats['acceptance_fee_outstanding'], 'class' => 'bg-danger'],
    ];
    foreach ($cards as $card):
    ?>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <span class="badge <?= $card['class'] ?> mb-3">&nbsp;</span>
                    <p class="text-muted mb-1"><?= htmlspecialchars($card['label']) ?></p>
                    <h3 class="mb-0"><?= number_format((int) $card['value']) ?></h3>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header">
        <h5 class="mb-0">Admission Funnel</h5>
    </div>
    <div class="card-body">
        <?php
        $total = max(1, (int) $stats['total_applications']);
        $funnel = [
            'Submitted' => $stats['submitted_applications'],
            'Pending Screening' => $stats['pending_screening'],
            'Admitted' => $stats['admitted_candidates'],
            'Acceptance Paid' => $stats['acceptance_fee_paid'],
        ];
        foreach ($funnel as $label => $value):
            $percent = min(100, round(((int) $value / $total) * 100));
        ?>
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span><?= htmlspecialchars($label) ?></span>
                    <strong><?= number_format((int) $value) ?></strong>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar" role="progressbar" style="width: <?= $percent ?>%;" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Applications</h5>
        <a href="<?= route('admissionApplications', $utility) ?>" class="btn btn-sm btn-primary">View Applications</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Application No</th>
                        <th>Programme</th>
                        <th>Status</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentApplications as $row): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($row['applicant_name'] ?: 'Incomplete Profile') ?><br>
                                <small class="text-muted"><?= htmlspecialchars($row['email']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($row['application_no']) ?></td>
                            <td><?= htmlspecialchars(($row['programme_name'] ?: '-') . ' / ' . ($row['department_name'] ?: '-')) ?></td>
                            <td><span class="badge bg-info"><?= htmlspecialchars($row['form_status']) ?></span></td>
                            <td><?= htmlspecialchars($row['submitted_at'] ?: $row['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentApplications): ?>
                        <tr><td colspan="5" class="text-center text-muted">No admission applications yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
