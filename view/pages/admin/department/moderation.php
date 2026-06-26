<?php
$moderationTokens = [
    'moderate' => $utility->generateCsrf('department_result_moderate')
];
?>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Department Result Moderation</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="departmentResultSheetsTable" class="table table-striped table-bordered dataTable w-100">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Lecturer</th>
                                <th>Level</th>
                                <th>Session</th>
                                <th>Semester</th>
                                <th>Students</th>
                                <th>Submitted</th>
                                <th>Pass Rate</th>
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

<div class="modal fade" id="departmentResultSheetModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Result Sheet Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="departmentResultSheetBody">
                <div class="text-center text-muted">Loading...</div>
            </div>
            <div class="modal-footer">
                <textarea class="form-control" id="departmentModerationRemarks" rows="2" placeholder="Remarks for lecturer or records"></textarea>
                <div class="btn-group ms-auto">
                    <button class="btn btn-success moderateResultSheet" data-action="approve">Approve</button>
                    <button class="btn btn-warning moderateResultSheet" data-action="return">Return</button>
                    <button class="btn btn-danger moderateResultSheet" data-action="reject">Reject</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.departmentModerationConfig = {
        csrf: <?= json_encode($moderationTokens); ?>
    };
</script>
