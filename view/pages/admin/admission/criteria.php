<?php
$admissionPortal = new Admission($db, $model, $utility, $qrcode, $mailservice);
$csrf = $admissionPortal->csrfToken();
$institutions = $admissionPortal->institutions();
$documentCatalog = $admissionPortal->documentCatalog();
?>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Admission Criteria</h5>
                <small class="text-muted">Define eligibility rules per institution and programme.</small>
            </div>
            <div class="card-body">
                <form id="admissionCriteriaForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="id" id="criteriaId">

                    <div class="mb-3">
                        <label class="form-label">Institution</label>
                        <select class="form-select" name="institution_id" id="criteriaInstitution" required>
                            <option value="">Select Institution</option>
                            <?php foreach ($institutions as $institution): ?>
                                <option value="<?= (int) $institution['id'] ?>"><?= htmlspecialchars($institution['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Programme</label>
                        <select class="form-select" name="programme_id" id="criteriaProgramme" required>
                            <option value="">Select Programme</option>
                        </select>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Required Credits</label>
                            <input type="number" min="1" max="9" class="form-control" name="minimum_credits" id="criteriaMinimumCredits" value="5" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Maximum Sittings</label>
                            <select class="form-select" name="maximum_sittings" id="criteriaMaximumSittings">
                                <option value="1">1 Sitting</option>
                                <option value="2" selected>2 Sittings</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Compulsory Subjects</label>
                        <textarea class="form-control" name="compulsory_subjects" id="criteriaCompulsorySubjects" rows="2" placeholder="English Language, Mathematics"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Optional Acceptable Subjects</label>
                        <textarea class="form-control" name="acceptable_subjects" id="criteriaAcceptableSubjects" rows="2" placeholder="Biology, Chemistry, Physics"></textarea>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Minimum JAMB Score</label>
                            <input type="number" min="0" max="400" class="form-control" name="minimum_jamb_score" id="criteriaMinimumJambScore" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="criteriaStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="jamb_registration_required" value="1" id="criteriaJambRequired" checked>
                        <label class="form-check-label" for="criteriaJambRequired">JAMB Registration Number is compulsory</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Required Documents</label>
                        <div class="row g-2">
                            <?php foreach ($documentCatalog as $key => $label): ?>
                                <div class="col-sm-6">
                                    <label class="border rounded p-2 d-flex align-items-center gap-2 h-100">
                                        <input class="form-check-input m-0 criteria-document-option" type="checkbox" name="required_documents[]" value="<?= htmlspecialchars($key) ?>" <?= in_array($key, ['passport', 'birth_certificate', 'olevel_result'], true) ? 'checked' : '' ?>>
                                        <span><?= htmlspecialchars($label) ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Additional Documents</label>
                        <textarea class="form-control" name="additional_documents" id="criteriaAdditionalDocuments" rows="2" placeholder="One custom document per line"></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary flex-fill" type="submit">Save Criteria</button>
                        <button class="btn btn-outline-secondary" type="button" id="resetCriteriaForm">Reset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <div class="row align-items-end g-3">
                    <div class="col-lg-4">
                        <h5 class="mb-0">Configured Criteria</h5>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Institution</label>
                        <select class="form-select" id="criteriaFilterInstitution">
                            <option value="">All Institutions</option>
                            <?php foreach ($institutions as $institution): ?>
                                <option value="<?= (int) $institution['id'] ?>"><?= htmlspecialchars($institution['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="criteriaFilterStatus">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <button class="btn btn-outline-primary w-100" id="reloadAdmissionCriteria">Filter</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <input type="hidden" id="admissionAdminCsrf" value="<?= htmlspecialchars($csrf) ?>">
                <div class="table-responsive">
                    <table class="table table-hover align-middle admissionCriteriaTable" style="width:100%">
                        <thead>
                            <tr>
                                <th>Institution</th>
                                <th>Programme</th>
                                <th>O'Level</th>
                                <th>JAMB</th>
                                <th>Documents</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="duplicateCriteriaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="duplicateCriteriaForm">
            <div class="modal-header">
                <h5 class="modal-title">Duplicate Criteria</h5>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="source_id" id="duplicateCriteriaSourceId">
                <div class="mb-3">
                    <label class="form-label">Target Institution</label>
                    <select class="form-select" name="institution_id" id="duplicateCriteriaInstitution" required>
                        <option value="">Select Institution</option>
                        <?php foreach ($institutions as $institution): ?>
                            <option value="<?= (int) $institution['id'] ?>"><?= htmlspecialchars($institution['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label">Target Programme</label>
                    <select class="form-select" name="programme_id" id="duplicateCriteriaProgramme" required>
                        <option value="">Select Programme</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" type="submit">Duplicate</button>
            </div>
        </form>
    </div>
</div>
