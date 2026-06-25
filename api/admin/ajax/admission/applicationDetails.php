<?php
require_once __DIR__ . '/bootstrap.php';

function adm_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function adm_check(bool $ok): string
{
    return $ok
        ? '<i class="bi bi-check-circle-fill text-success"></i>'
        : '<i class="bi bi-x-circle-fill text-danger"></i>';
}

function adm_file_url(string $path): string
{
    return '../' . ltrim($path, '/');
}

function adm_table_empty(int $colspan, string $message): string
{
    return '<tr><td colspan="' . $colspan . '" class="text-center text-muted py-3">' . adm_h($message) . '</td></tr>';
}

try {
    $application = $admission->applicationAdminDetails((int) ($_GET['application_id'] ?? 0), (int) $_SESSION['admin_id']);
    $evaluation = $application['evaluation'] ?? [];
    $passport = null;
    foreach ($application['documents'] ?? [] as $document) {
        if ($document['document_type'] === 'passport') {
            $passport = $document;
            break;
        }
    }
    $acceptancePaid = false;
    foreach ($application['payments'] ?? [] as $payment) {
        if (($payment['payment_type'] ?? '') === 'acceptance_fee' && ($payment['status'] ?? '') === 'paid') {
            $acceptancePaid = true;
            break;
        }
    }
    $isMigrated = !empty($application['migrated_user_id']);

    ob_start();
    ?>
    <div class="admission-detail-shell">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <?php if ($passport): ?>
                            <img src="<?= adm_h(adm_file_url($passport['file_path'])) ?>" class="rounded shadow-sm mb-3" style="width: 120px; height: 120px; object-fit: cover;" alt="Passport">
                        <?php else: ?>
                            <div class="rounded bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 120px; height: 120px;">
                                <i class="bi bi-person fs-1 text-muted"></i>
                            </div>
                        <?php endif; ?>
                        <h5><?= adm_h(trim(($application['surname'] ?? '') . ' ' . ($application['first_name'] ?? '') . ' ' . ($application['other_name'] ?? '')) ?: 'Incomplete Profile') ?></h5>
                        <p class="text-muted mb-1"><?= adm_h($application['application_no']) ?></p>
                        <span class="badge bg-info"><?= adm_h($application['form_status']) ?></span>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <strong>Personal Information</strong>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4"><small class="text-muted d-block">Gender</small><strong><?= adm_h($application['gender'] ?? '-') ?></strong></div>
                            <div class="col-md-4"><small class="text-muted d-block">Date of Birth</small><strong><?= adm_h($application['date_of_birth'] ?? '-') ?></strong></div>
                            <div class="col-md-4"><small class="text-muted d-block">State</small><strong><?= adm_h($application['state_of_origin'] ?? '-') ?></strong></div>
                            <div class="col-md-4"><small class="text-muted d-block">Email</small><strong><?= adm_h($application['contact_email'] ?? $application['applicant_email'] ?? '-') ?></strong></div>
                            <div class="col-md-4"><small class="text-muted d-block">Phone</small><strong><?= adm_h($application['contact_phone'] ?? $application['applicant_phone'] ?? '-') ?></strong></div>
                            <div class="col-md-4"><small class="text-muted d-block">LGA</small><strong><?= adm_h($application['local_government'] ?? '-') ?></strong></div>
                            <div class="col-12"><small class="text-muted d-block">Address</small><strong><?= adm_h($application['contact_address'] ?? '-') ?></strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white"><strong>Academic History</strong></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Institution</th><th>Certificate</th><th>Years</th></tr></thead>
                            <tbody>
                                <?php foreach ($application['history'] ?? [] as $row): ?>
                                    <tr>
                                        <td><?= adm_h($row['institution_name']) ?><br><small class="text-muted"><?= adm_h($row['location']) ?></small></td>
                                        <td><?= adm_h($row['certificate_obtained']) ?></td>
                                        <td><?= adm_h($row['start_year']) ?> - <?= adm_h($row['end_year'] ?: 'Present') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($application['history'])) echo adm_table_empty(3, 'No academic history submitted.'); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white"><strong>Programme Information</strong></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6"><small class="text-muted d-block">Institution</small><strong><?= adm_h($application['institution_name'] ?? '-') ?></strong></div>
                            <div class="col-md-6"><small class="text-muted d-block">Programme</small><strong><?= adm_h($application['programme_name'] ?? '-') ?></strong></div>
                            <div class="col-md-6"><small class="text-muted d-block">Department</small><strong><?= adm_h($application['department_name'] ?? '-') ?></strong></div>
                            <div class="col-md-6"><small class="text-muted d-block">Mode of Entry</small><strong><?= adm_h($application['mode_of_entry'] ?? '-') ?></strong></div>
                            <div class="col-md-6"><small class="text-muted d-block">JAMB Reg No</small><strong><?= adm_h($application['jamb_registration_number'] ?? '-') ?></strong></div>
                            <div class="col-md-6"><small class="text-muted d-block">JAMB Score</small><strong><?= adm_h($application['jamb_score'] ?? '-') ?></strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white"><strong>O'Level Results</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($application['sittings'] ?? [] as $sitting): ?>
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex justify-content-between mb-2">
                                    <strong>Sitting <?= (int) $sitting['sitting_no'] ?></strong>
                                    <span class="badge bg-light text-dark"><?= adm_h($sitting['exam_type']) ?> <?= adm_h($sitting['exam_year']) ?></span>
                                </div>
                                <small class="text-muted d-block mb-2"><?= adm_h($sitting['exam_number']) ?></small>
                                <table class="table table-sm mb-0">
                                    <thead><tr><th>Subject</th><th>Grade</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($sitting['results'] ?? [] as $result): ?>
                                            <tr><td><?= adm_h($result['subject']) ?></td><td><strong><?= adm_h($result['grade']) ?></strong></td></tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($application['sittings'])): ?>
                        <div class="col-12 text-muted">No O'Level result submitted.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white"><strong>Uploaded Documents</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($application['documents'] ?? [] as $document): ?>
                        <?php $isImage = strpos((string) $document['mime_type'], 'image/') === 0; ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex justify-content-between gap-2">
                                    <strong><?= adm_h(ucwords(str_replace('_', ' ', $document['document_type']))) ?></strong>
                                    <span class="badge bg-light text-dark"><?= adm_h($document['validation_status']) ?></span>
                                </div>
                                <small class="text-muted d-block my-2"><?= adm_h($document['original_name']) ?></small>
                                <div class="d-flex gap-2">
                                    <a class="btn btn-sm btn-outline-primary <?= $isImage ? 'admissionDocumentPreview' : '' ?>" href="<?= adm_h(adm_file_url($document['file_path'])) ?>" target="_blank">Open</a>
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= adm_h(adm_file_url($document['file_path'])) ?>" download>Download</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($application['documents'])): ?>
                        <div class="col-12 text-muted">No document uploaded.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white"><strong>Payments</strong></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Invoice</th><th>Type</th><th>Amount</th><th>Status</th><th>Paid</th></tr></thead>
                            <tbody>
                                <?php foreach ($application['payments'] ?? [] as $payment): ?>
                                    <tr>
                                        <td><?= adm_h($payment['invoice_no']) ?><br><small><?= adm_h($payment['reference'] ?? '-') ?></small></td>
                                        <td><?= adm_h(str_replace('_', ' ', $payment['payment_type'])) ?></td>
                                        <td>NGN <?= number_format((float) $payment['amount'], 2) ?></td>
                                        <td><span class="badge bg-light text-dark"><?= adm_h($payment['status']) ?></span></td>
                                        <td><?= adm_h($payment['paid_at'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($application['payments'])) echo adm_table_empty(5, 'No payment record.'); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white"><strong>Admission Evaluation</strong></div>
                    <div class="card-body">
                        <?php if ($evaluation): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <small class="text-muted d-block">Recommendation</small>
                                    <h5 class="mb-0"><?= adm_h($evaluation['recommendation']) ?></h5>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block">Compliance</small>
                                    <h4 class="mb-0"><?= number_format((float) $evaluation['compliance_percentage'], 0) ?>%</h4>
                                </div>
                            </div>
                            <div class="progress mb-3" style="height: 10px;">
                                <div class="progress-bar" style="width: <?= min(100, (float) $evaluation['compliance_percentage']) ?>%"></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">O'Level</small>
                                    <div><?= adm_check(!empty($evaluation['olevel']['overall_pass'])) ?> Credits: <?= (int) ($evaluation['olevel']['credits'] ?? 0) ?>, Passes: <?= (int) ($evaluation['olevel']['passes'] ?? 0) ?></div>
                                    <small class="text-muted">Missing: <?= adm_h(implode(', ', $evaluation['olevel']['subjects_missing'] ?? []) ?: 'None') ?></small>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">JAMB</small>
                                    <div><?= adm_check(!empty($evaluation['jamb']['overall_pass'])) ?> Score: <?= (int) ($evaluation['jamb']['jamb_score'] ?? 0) ?> / <?= (int) ($evaluation['jamb']['minimum_score'] ?? 0) ?></div>
                                    <small class="text-muted">Reg No: <?= !empty($evaluation['jamb']['registration_number_available']) ? 'Available' : 'Missing' ?></small>
                                </div>
                                <div class="col-12">
                                    <small class="text-muted d-block">Documents</small>
                                    <div class="d-flex flex-wrap gap-2 mt-1">
                                        <?php foreach ($evaluation['documents']['items'] ?? [] as $item): ?>
                                            <span class="badge bg-light text-dark border"><?= adm_check(!empty($item['uploaded'])) ?> <?= adm_h($item['label']) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">Evaluation is not available yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white"><strong>Admission Office Remarks</strong></div>
                    <div class="card-body">
                        <?php if (!empty($application['screening_actions'])): ?>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($application['screening_actions'] as $action): ?>
                                    <div class="border rounded p-3">
                                        <div class="d-flex justify-content-between gap-2 mb-2">
                                            <div>
                                                <strong><?= adm_h($action['to_status'] ?: $action['action']) ?></strong>
                                                <small class="text-muted d-block"><?= adm_h($action['admin_name'] ?? 'Admission Office') ?></small>
                                            </div>
                                            <small class="text-muted"><?= adm_h($action['created_at'] ?? '') ?></small>
                                        </div>
                                        <?php if (!empty($action['remarks'])): ?>
                                            <p class="mb-2"><?= nl2br(adm_h($action['remarks'])) ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($action['rejection_reason'])): ?>
                                            <div class="alert alert-danger py-2 px-3 mb-0">
                                                <strong>Rejection Reason:</strong>
                                                <?= nl2br(adm_h($action['rejection_reason'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">No screening remarks have been recorded.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white"><strong>Student Migration</strong></div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Acceptance Fee</small>
                                <span class="badge <?= $acceptancePaid ? 'bg-success' : 'bg-warning text-dark' ?>">
                                    <?= $acceptancePaid ? 'Paid' : 'Outstanding' ?>
                                </span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Migration Status</small>
                                <span class="badge <?= $isMigrated ? 'bg-success' : 'bg-light text-dark border' ?>">
                                    <?= $isMigrated ? 'Migrated' : 'Not Migrated' ?>
                                </span>
                            </div>
                            <?php if ($isMigrated): ?>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Matric Number</small>
                                    <strong><?= adm_h($application['matric_no']) ?></strong>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Student Record</small>
                                    <strong>#<?= (int) $application['student_record_id'] ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($isMigrated): ?>
                            <p class="text-muted mb-0">This applicant has already been moved to the users and students tables.</p>
                        <?php else: ?>
                            <button
                                class="btn btn-success"
                                id="submitModalStudentMigration"
                                data-id="<?= (int) $application['id'] ?>"
                                <?= (!$acceptancePaid || ($application['form_status'] ?? '') !== 'Accepted') ? 'disabled' : '' ?>>
                                <i class="bi bi-arrow-right-circle me-1"></i>
                                Migrate to Student
                            </button>
                            <?php if (!$acceptancePaid || ($application['form_status'] ?? '') !== 'Accepted'): ?>
                                <small class="text-muted d-block mt-2">
                                    Migration is enabled only after the applicant accepts the offer and pays the acceptance fee.
                                </small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white"><strong>Decision Workflow</strong></div>
            <div class="card-body">
                <input type="hidden" id="modalApplicationId" value="<?= (int) $application['id'] ?>">
                <div class="row g-3">
                    <div class="col-lg-4">
                        <label class="form-label">Decision</label>
                        <select class="form-select" id="modalAdmissionAction">
                            <option value="pending">Pending Review</option>
                            <option value="review">Under Review</option>
                            <option value="recommend">Recommended</option>
                            <option value="offer">Offered Admission</option>
                            <option value="reject">Rejected</option>
                            <option value="accept">Accepted</option>
                            <option value="reverse">Reverse to Under Review</option>
                            <option value="allow_edit">Allow Applicant Edit</option>
                            <option value="remark">Add Remark Only</option>
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Remarks (Visible to Applicant)</label>
                        <textarea class="form-control" id="modalAdmissionRemarks" rows="2"></textarea>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Rejection Reason</label>
                        <textarea class="form-control" id="modalRejectionReason" rows="2"></textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" id="submitModalAdmissionDecision">Save Decision</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php

    admission_admin_json([
        'status' => true,
        'html' => ob_get_clean(),
        'title' => trim(($application['surname'] ?? '') . ' ' . ($application['first_name'] ?? '')) ?: $application['application_no']
    ]);
} catch (Throwable $e) {
    admission_admin_json(['status' => false, 'message' => $e->getMessage()], 422);
}
