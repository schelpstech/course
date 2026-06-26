<?php
$resultConfigTokens = [
    'save' => $utility->generateCsrf('result_config_save')
];
?>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Result Configuration</h5>
                <button class="btn btn-primary" id="addResultConfigBtn">
                    <i class="ph ph-plus"></i> Add Config
                </button>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="resultConfigTable" class="table table-striped table-bordered dataTable w-100">
                        <thead>
                            <tr>
                                <th>Session</th>
                                <th>Semester</th>
                                <th>CA</th>
                                <th>Exam</th>
                                <th>Total</th>
                                <th>Entry</th>
                                <th>Publication</th>
                                <th>Deadline</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="resultConfigModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="resultConfigForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="resultConfigModalTitle">Add Result Configuration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="result_config_id" name="id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Academic Session</label>
                            <select class="form-control" id="result_session_id" name="academic_session_id" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Semester</label>
                            <select class="form-control" id="result_semester_id" name="semester_id" required></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">CA Mark</label>
                            <input type="number" min="0" step="0.01" class="form-control" id="ca_max_score" name="ca_max_score" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Exam Mark</label>
                            <input type="number" min="0" step="0.01" class="form-control" id="exam_max_score" name="exam_max_score" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total Mark</label>
                            <input type="number" min="1" step="0.01" class="form-control" id="total_max_score" name="total_max_score" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">CA Entry</label>
                            <select class="form-control" id="ca_entry_enabled" name="ca_entry_enabled">
                                <option value="1">Enabled</option>
                                <option value="0">Disabled</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Exam Entry</label>
                            <select class="form-control" id="exam_entry_enabled" name="exam_entry_enabled">
                                <option value="1">Enabled</option>
                                <option value="0">Disabled</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Publication</label>
                            <select class="form-control" id="result_publication_enabled" name="result_publication_enabled">
                                <option value="0">Disabled</option>
                                <option value="1">Enabled</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">GPA</label>
                            <select class="form-control" id="gpa_enabled" name="gpa_enabled">
                                <option value="1">Enabled</option>
                                <option value="0">Disabled</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Editing</label>
                            <select class="form-control" id="editing_enabled" name="editing_enabled">
                                <option value="1">Enabled</option>
                                <option value="0">Disabled</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-control" id="result_config_status" name="status">
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Submission Deadline</label>
                            <input type="datetime-local" class="form-control" id="submission_deadline" name="submission_deadline">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Grace Period (minutes)</label>
                            <input type="number" min="0" class="form-control" id="grace_period" name="grace_period" value="0">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" id="result_config_remarks" name="remarks" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ph ph-floppy-disk"></i> Save Config
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.resultConfigManager = {
        csrf: <?= json_encode($resultConfigTokens); ?>
    };
</script>
