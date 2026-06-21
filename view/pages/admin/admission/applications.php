<?php
$admissionPortal = new Admission($db, $model, $utility, $qrcode, $mailservice);
$csrf = $admissionPortal->csrfToken();
$sessions = $admissionPortal->admissionSessions();
?>

<div class="card border-0 shadow-sm">
    <div class="card-header">
        <div class="row align-items-end g-3">
            <div class="col-lg-4">
                <h5 class="mb-0">Admission Applications</h5>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Session</label>
                <select class="form-select" id="admissionSessionFilter">
                    <option value="">All Sessions</option>
                    <?php foreach ($sessions as $session): ?>
                        <option value="<?= (int) $session['id'] ?>"><?= htmlspecialchars($session['academic_session_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Status</label>
                <select class="form-select" id="admissionStatusFilter">
                    <option value="">All Statuses</option>
                    <?php foreach (['Submitted', 'Under Review', 'Recommended', 'Offered Admission', 'Rejected', 'Accepted'] as $status): ?>
                        <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <button class="btn btn-primary w-100" id="reloadAdmissionApplications">Filter</button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <input type="hidden" id="admissionAdminCsrf" value="<?= htmlspecialchars($csrf) ?>">
        <div class="table-responsive">
            <table class="table table-hover align-middle admissionApplicationsTable" style="width:100%">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Numbers</th>
                        <th>Programme</th>
                        <th>Session</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
