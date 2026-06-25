<?php
$admissionPortal = new Admission($db, $model, $utility, $qrcode, $mailservice);
$stats = $admissionPortal->dashboardStats();
$recentApplications = $stats['recent_applications'] ?? array_slice($admissionPortal->applications(), 0, 8);
$pendingReviews = array_slice($stats['pending_reviews'] ?? [], 0, 8);
$chartPayload = [
    'institution' => $stats['applications_by_institution'] ?? [],
    'programme' => $stats['applications_by_programme'] ?? [],
    'status' => $stats['applications_by_status'] ?? [],
    'trend' => $stats['daily_application_trend'] ?? []
];
?>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['label' => 'Total Applications', 'value' => $stats['total_applications'], 'class' => 'bg-primary'],
        ['label' => 'Submitted', 'value' => $stats['submitted_applications'], 'class' => 'bg-info'],
        ['label' => 'Under Review', 'value' => $stats['under_review'], 'class' => 'bg-warning'],
        ['label' => 'Recommended', 'value' => $stats['recommended'], 'class' => 'bg-secondary'],
        ['label' => 'Offered Admission', 'value' => $stats['offered_admission'], 'class' => 'bg-success'],
        ['label' => 'Accepted', 'value' => $stats['accepted'], 'class' => 'bg-success'],
        ['label' => 'Rejected', 'value' => $stats['rejected'], 'class' => 'bg-danger'],
    ];
    foreach ($cards as $card):
    ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <span class="badge <?= $card['class'] ?> mb-3">&nbsp;</span>
                    <p class="text-muted mb-1"><?= htmlspecialchars($card['label']) ?></p>
                    <h3 class="mb-0"><?= number_format((int) $card['value']) ?></h3>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Applications by Institution</h5>
            </div>
            <div class="card-body">
                <div id="admissionInstitutionChart" style="min-height: 300px;"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Applications by Status</h5>
            </div>
            <div class="card-body">
                <div id="admissionStatusChart" style="min-height: 300px;"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Applications by Programme</h5>
            </div>
            <div class="card-body">
                <div id="admissionProgrammeChart" style="min-height: 300px;"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Daily Application Trend</h5>
            </div>
            <div class="card-body">
                <div id="admissionTrendChart" style="min-height: 300px;"></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center bg-white">
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
    </div>

    <div class="col-xl-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Pending Reviews</h5>
            </div>
            <div class="card-body">
                <?php foreach ($pendingReviews as $row): ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <strong><?= htmlspecialchars($row['applicant_name'] ?: 'Incomplete Profile') ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($row['application_no']) ?></small>
                        </div>
                        <span class="badge bg-warning text-dark"><?= htmlspecialchars($row['form_status']) ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if (!$pendingReviews): ?>
                    <p class="text-muted mb-0">No applications are pending review.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Payment Summary</h5>
            </div>
            <div class="card-body">
                <?php foreach ($stats['payment_summary'] ?? [] as $payment): ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $payment['payment_type']))) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars(ucfirst($payment['status'])) ?></small>
                        </div>
                        <div class="text-end">
                            <strong><?= number_format((int) $payment['total']) ?></strong><br>
                            <small class="text-muted">NGN <?= number_format((float) $payment['amount'], 2) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($stats['payment_summary'])): ?>
                    <p class="text-muted mb-0">No payment activity yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    window.admissionDashboardData = <?= json_encode($chartPayload) ?>;
</script>
