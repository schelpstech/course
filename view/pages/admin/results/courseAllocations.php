<?php
$allocationCounts = ['total' => 0, 'active' => 0];

if ($rbac->tableExists('course_allocations')) {
    $scopeDepartmentId = $rbac->departmentScopeId();
    $countParams = [];
    $countWhere = 'WHERE 1=1';

    if ($scopeDepartmentId) {
        $countWhere .= ' AND department_id = :department_id';
        $countParams['department_id'] = $scopeDepartmentId;
    }

    $allocationCounts = $model->queryOne("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active
        FROM course_allocations
        {$countWhere}
    ", $countParams) ?: ['total' => 0, 'active' => 0];
}

$allocationTokens = [
    'save' => $utility->generateCsrf('course_allocation_save'),
    'disable' => $utility->generateCsrf('course_allocation_disable')
];
?>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card mb-0">
            <div class="card-body">
                <span class="text-muted">Allocations</span>
                <h3 class="mb-0"><?= $allocationCounts['total']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-0">
            <div class="card-body">
                <span class="text-muted">Active Allocations</span>
                <h3 class="mb-0 text-success"><?= $allocationCounts['active']; ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Course Allocation</h5>
                <button class="btn btn-primary" id="addAllocationBtn">
                    <i class="ph ph-plus"></i> Allocate Course
                </button>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="courseAllocationTable" class="table table-striped table-bordered dataTable w-100">
                        <thead>
                            <tr>
                                <th>Session</th>
                                <th>Semester</th>
                                <th>Course</th>
                                <th>Department</th>
                                <th>Lecturer</th>
                                <th>Status</th>
                                <th>Allocated At</th>
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

<div class="modal fade" id="courseAllocationModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="courseAllocationForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="courseAllocationModalTitle">Allocate Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="allocation_id" name="id">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Academic Session</label>
                            <select class="form-control" id="allocation_session_id" name="academic_session_id" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Semester</label>
                            <select class="form-control" id="allocation_semester_id" name="semester_id" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Institution</label>
                            <select class="form-control" id="allocation_institution_id" name="institution_id" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Programme</label>
                            <select class="form-control" id="allocation_programme_id" name="programme_id"></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select class="form-control" id="allocation_department_id" name="department_id" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Level</label>
                            <select class="form-control" id="allocation_level_id" name="level_id"></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Course</label>
                            <select class="form-control" id="allocation_course_id" name="course_id" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lecturer</label>
                            <select class="form-control" id="allocation_lecturer_id" name="lecturer_id" required></select>
                        </div>
                        <input type="hidden" id="allocation_status" name="status" value="active">
                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                Active allocations are locked. Disable an allocation first before modifying it for reallocation.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ph ph-floppy-disk"></i> Save Allocation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.courseAllocationConfig = {
        csrf: <?= json_encode($allocationTokens); ?>
    };
</script>
