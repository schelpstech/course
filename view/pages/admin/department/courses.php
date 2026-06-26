<?php
$departmentCourseTokens = [
    'save' => $utility->generateCsrf('department_course_save'),
    'toggle' => $utility->generateCsrf('department_course_toggle')
];
?>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Department Courses</h5>
                <button class="btn btn-primary" id="addDepartmentCourseBtn">
                    <i class="ph ph-plus"></i> Add Course
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="departmentCoursesTable" class="table table-striped table-bordered dataTable w-100">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Title</th>
                                <th>Unit</th>
                                <th>Level</th>
                                <th>Semester</th>
                                <th>Type</th>
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

<div class="modal fade" id="departmentCourseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="departmentCourseForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="departmentCourseModalTitle">Add Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="department_course_id" name="id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Level</label>
                            <select class="form-control" id="department_course_level_id" name="level_id" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Semester</label>
                            <select class="form-control" id="department_course_semester_id" name="semester_id" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Course Code</label>
                            <input type="text" class="form-control" id="department_course_code" name="course_code" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Units</label>
                            <input type="number" min="1" class="form-control" id="department_course_unit" name="unit" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Course Title</label>
                            <input type="text" class="form-control" id="department_course_title" name="course_title" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <select class="form-control" id="department_course_type" name="course_type">
                                <option value="core">Core</option>
                                <option value="elective">Elective</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ph ph-floppy-disk"></i> Save Course
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.departmentCourseConfig = {
        csrf: <?= json_encode($departmentCourseTokens); ?>
    };
</script>
