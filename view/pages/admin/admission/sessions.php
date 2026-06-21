<?php
$admissionPortal = new Admission($db, $model, $utility, $qrcode, $mailservice);
$csrf = $admissionPortal->csrfToken();
$academicSessions = $admissionPortal->academicSessions();
$sessions = $admissionPortal->admissionSessions();
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Admission Session</h5>
            </div>
            <div class="card-body">
                <form id="admissionSessionForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="id" id="admissionSessionId">
                    <div class="mb-3">
                        <label class="form-label">Academic Session</label>
                        <select class="form-select" name="session_id" id="admissionAcademicSession" required>
                            <option value="">Select Session</option>
                            <?php foreach ($academicSessions as $session): ?>
                                <option value="<?= (int) $session['id'] ?>"><?= htmlspecialchars($session['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Application Fee</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="application_fee" id="admissionApplicationFee" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Acceptance Fee</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="acceptance_fee" id="admissionAcceptanceFee" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" id="admissionStartDate" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" id="admissionEndDate" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="admissionStatus" required>
                            <option value="inactive">Inactive</option>
                            <option value="active">Active</option>
                        </select>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Save Session</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Admission Sessions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Session</th>
                                <th>Fees</th>
                                <th>Dates</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $session): ?>
                                <tr>
                                    <td><?= htmlspecialchars($session['academic_session_name']) ?></td>
                                    <td>
                                        Application: NGN <?= number_format((float) $session['application_fee'], 2) ?><br>
                                        Acceptance: NGN <?= number_format((float) $session['acceptance_fee'], 2) ?>
                                    </td>
                                    <td><?= htmlspecialchars($session['start_date']) ?> to <?= htmlspecialchars($session['end_date']) ?></td>
                                    <td><span class="badge <?= $session['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= htmlspecialchars($session['status']) ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary editAdmissionSession"
                                                data-id="<?= (int) $session['id'] ?>"
                                                data-session="<?= (int) $session['session_id'] ?>"
                                                data-application-fee="<?= htmlspecialchars($session['application_fee']) ?>"
                                                data-acceptance-fee="<?= htmlspecialchars($session['acceptance_fee']) ?>"
                                                data-start="<?= htmlspecialchars($session['start_date']) ?>"
                                                data-end="<?= htmlspecialchars($session['end_date']) ?>"
                                                data-status="<?= htmlspecialchars($session['status']) ?>">
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$sessions): ?>
                                <tr><td colspan="5" class="text-center text-muted">No admission sessions configured.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
