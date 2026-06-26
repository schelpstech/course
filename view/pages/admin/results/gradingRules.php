<?php
$gradingTokens = [
    'save' => $utility->generateCsrf('grading_rule_save')
];
?>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Grading Rules</h5>
                <button class="btn btn-primary" id="addGradingRuleBtn">
                    <i class="ph ph-plus"></i> Add Rule
                </button>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="gradingRulesTable" class="table table-striped table-bordered dataTable w-100">
                        <thead>
                            <tr>
                                <th>Institution</th>
                                <th>Range</th>
                                <th>Grade</th>
                                <th>Point</th>
                                <th>Remark</th>
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

<div class="modal fade" id="gradingRuleModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="gradingRuleForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="gradingRuleModalTitle">Add Grading Rule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="grading_rule_id" name="id">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Institution</label>
                            <select class="form-control" id="grading_institution_id" name="institution_id" required></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Minimum Score</label>
                            <input type="number" min="0" step="0.01" class="form-control" id="min_score" name="min_score" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Maximum Score</label>
                            <input type="number" min="0" step="0.01" class="form-control" id="max_score" name="max_score" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Letter Grade</label>
                            <input type="text" class="form-control" id="letter_grade" name="letter_grade" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Grade Point</label>
                            <input type="number" min="0" step="0.01" class="form-control" id="grade_point" name="grade_point" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-control" id="grading_status" name="status">
                                <option value="1">Active</option>
                                <option value="0">Disabled</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Remark</label>
                            <input type="text" class="form-control" id="grading_remark" name="remark">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ph ph-floppy-disk"></i> Save Rule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.gradingRuleManager = {
        csrf: <?= json_encode($gradingTokens); ?>
    };
</script>
