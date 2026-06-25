<?php
$admissionPortal = new Admission($db, $model, $utility, $qrcode, $mailservice);
$csrf = $admissionPortal->csrfToken();
$sessions = $admissionPortal->admissionSessions();
$institutions = $admissionPortal->institutions();
?>

<style>
    .admission-applications-wrap {
        overflow: visible;
    }

    .admissionApplicationsTable td,
    .admissionApplicationsTable th {
        vertical-align: middle;
    }

    .admission-row-actions .dropdown-menu {
        min-width: 230px;
    }
</style>

<div class="card border-0 shadow-sm">
    <div class="card-header">
        <div class="row align-items-end g-3">
            <div class="col-lg-4">
                <h5 class="mb-0">Admission Applications</h5>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Session</label>
                <select class="form-select" id="admissionSessionFilter">
                    <option value="">All Sessions</option>
                    <?php foreach ($sessions as $session): ?>
                        <option value="<?= (int) $session['id'] ?>"><?= htmlspecialchars($session['academic_session_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Status</label>
                <select class="form-select" id="admissionStatusFilter">
                    <option value="">All Statuses</option>
                    <?php foreach (['Submitted', 'Pending Review', 'Under Review', 'Recommended', 'Offered Admission', 'Rejected', 'Accepted'] as $status): ?>
                        <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Institution</label>
                <select class="form-select" id="admissionInstitutionFilter">
                    <option value="">All Institutions</option>
                    <?php foreach ($institutions as $institution): ?>
                        <option value="<?= (int) $institution['id'] ?>"><?= htmlspecialchars($institution['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <button class="btn btn-primary w-100" id="reloadAdmissionApplications">Filter</button>
            </div>
        </div>
        <div class="row align-items-end g-3 mt-2">
            <div class="col-lg-2">
                <label class="form-label">Programme</label>
                <select class="form-select" id="admissionProgrammeFilter">
                    <option value="">All Programmes</option>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Department</label>
                <select class="form-select" id="admissionDepartmentFilter">
                    <option value="">All Departments</option>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Gender</label>
                <select class="form-select" id="admissionGenderFilter">
                    <option value="">All</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Mode</label>
                <select class="form-select" id="admissionModeFilter">
                    <option value="">All Modes</option>
                    <option value="JAMB UTME">JAMB UTME</option>
                    <option value="Direct Entry">Direct Entry</option>
                    <option value="Remedial">Remedial</option>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Payment</label>
                <select class="form-select" id="admissionPaymentFilter">
                    <option value="">All</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Search</label>
                <input class="form-control" id="admissionSearchFilter" placeholder="Name, email, JAMB...">
            </div>
            <div class="col-lg-2">
                <label class="form-label">State</label>
                <input class="form-control" id="admissionStateFilter" placeholder="State of origin">
            </div>
        </div>
    </div>
    <div class="card-body">
        <input type="hidden" id="admissionAdminCsrf" value="<?= htmlspecialchars($csrf) ?>">
        <div class="table-responsive admission-applications-wrap">
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

<div class="modal fade" id="admissionApplicationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="admissionApplicationModalTitle">Applicant Details</h5>
                    <small class="text-muted">Complete admission record and decision workflow</small>
                </div>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="admissionApplicationModalBody">
                <div class="text-center text-muted py-5">Loading applicant details...</div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="admissionDocumentLightbox" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0">
            <img src="" alt="Document Preview" id="admissionDocumentLightboxImage" class="img-fluid rounded">
        </div>
    </div>
</div>
