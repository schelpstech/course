<?php
$scoreTokens = [
    'save' => $utility->generateCsrf('lecturer_score_save'),
    'submit' => $utility->generateCsrf('lecturer_score_submit')
];
?>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Scoresheet</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label">Allocated Course</label>
                        <select class="form-control" id="scoresheet_allocation_id"></select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100" id="loadScoresheetBtn">
                            <i class="ph ph-arrow-square-in"></i> Load Scoresheet
                        </button>
                    </div>
                </div>

                <div id="scoresheetCourseInfo" class="mt-4"></div>
            </div>
        </div>
    </div>
</div>

<div class="row d-none" id="scoresheetWorkspace">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#classListTab" type="button">Class List</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#caScoresTab" type="button">CA Scoresheet</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#examScoresTab" type="button">Exam Scoresheet</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#downloadScoresheetTab" type="button">Download</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="classListTab">
                        <div class="table-responsive">
                            <table id="lecturerClassListTable" class="table table-striped table-bordered w-100">
                                <thead>
                                    <tr>
                                        <th>Passport</th>
                                        <th>Matric Number</th>
                                        <th>Full Name</th>
                                        <th>Gender</th>
                                        <th>Programme</th>
                                        <th>Department</th>
                                        <th>Level</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="caScoresTab">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong>CA Max:</strong> <span id="caMaxScore">0</span>
                                <span class="ms-3 text-muted" id="caLastSaved"></span>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-outline-primary saveScoresBtn" data-component="ca">
                                    <i class="ph ph-floppy-disk"></i> Save Draft
                                </button>
                                <button class="btn btn-success submitScoresBtn" data-component="ca">
                                    <i class="ph ph-paper-plane-tilt"></i> Submit CA
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="caScoresTable" class="table table-striped table-bordered w-100">
                                <thead>
                                    <tr>
                                        <th>Matric Number</th>
                                        <th>Student Name</th>
                                        <th>CA Score</th>
                                        <th>Total</th>
                                        <th>Grade</th>
                                        <th>Remark</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="examScoresTab">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong>Exam Max:</strong> <span id="examMaxScore">0</span>
                                <span class="ms-3 text-muted" id="examLastSaved"></span>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-outline-primary saveScoresBtn" data-component="exam">
                                    <i class="ph ph-floppy-disk"></i> Save Draft
                                </button>
                                <button class="btn btn-success submitScoresBtn" data-component="exam">
                                    <i class="ph ph-paper-plane-tilt"></i> Submit Exam
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="examScoresTable" class="table table-striped table-bordered w-100">
                                <thead>
                                    <tr>
                                        <th>Matric Number</th>
                                        <th>Student Name</th>
                                        <th>Exam Score</th>
                                        <th>Total</th>
                                        <th>Grade</th>
                                        <th>Remark</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="downloadScoresheetTab">
                        <div class="alert alert-info mb-3" id="downloadScoresheetNotice">
                            Download becomes available after both CA and Exam scores have been submitted.
                        </div>
                        <a href="#" target="_blank" class="btn btn-primary disabled" id="downloadScoresheetBtn">
                            <i class="ph ph-download-simple"></i> Download Submitted Scoresheet
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.lecturerScoresheetConfig = {
        csrf: <?= json_encode($scoreTokens); ?>
    };
</script>
