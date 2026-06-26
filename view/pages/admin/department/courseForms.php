<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Department Course Forms</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Session</label>
                        <select id="departmentCourseFormSession" class="form-control"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Semester</label>
                        <select id="departmentCourseFormSemester" class="form-control"></select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="departmentCourseFormsTable" class="table table-striped table-bordered dataTable w-100">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Level</th>
                                <th>Courses</th>
                                <th>Status</th>
                                <th>Created</th>
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

<div class="modal fade" id="departmentCourseFormModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registered Courses</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="departmentCourseFormModalBody">
                <div class="text-center text-muted">Loading...</div>
            </div>
        </div>
    </div>
</div>
