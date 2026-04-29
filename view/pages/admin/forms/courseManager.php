<div class="row">
    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Manage Courses</h5>

            <button class="btn btn-info" id="addCourseBtn">
                <i class="ph ph-plus"></i> Add Course
            </button>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table id="courseTable"  class="table table-striped table-bordered dataTable">
                    <thead>
                        <tr>
                            <th>S/N</th>
                            <th>Code</th>
                            <th>Title</th>
                            <th>Unit</th>
                            <th>Level</th>
                            <th>Semester</th>
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

<div class="modal fade" id="courseModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form id="courseForm">

                <div class="modal-header">
                    <h5 id="courseModalTitle">Add Course</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" id="course_id">

                    <div class="row g-3">

                        <!-- Institution -->
                        <div class="col-md-6">
                            <label>Institution</label>
                            <select id="institution_id" class="form-control"></select>
                        </div>

                        <!-- Programme -->
                        <div class="col-md-6">
                            <label>Programme</label>
                            <select id="programme_id" class="form-control"></select>
                        </div>

                        <!-- Department -->
                        <div class="col-md-6">
                            <label>Department</label>
                            <select id="department_id" class="form-control"></select>
                        </div>

                        <!-- Level -->
                        <div class="col-md-6">
                            <label>Level</label>
                            <select id="level_id" class="form-control"></select>
                        </div>

                        <!-- Semester -->
                        <div class="col-md-6">
                            <label>Semester</label>
                            <select id="semester_id" class="form-control"></select>
                        </div>

                        <!-- Course Code -->
                        <div class="col-md-6">
                            <label>Course Code</label>
                            <input type="text" id="course_code" class="form-control">
                        </div>

                        <!-- Title -->
                        <div class="col-md-12">
                            <label>Course Title</label>
                            <input type="text" id="course_title" class="form-control">
                        </div>

                        <!-- Unit -->
                        <div class="col-md-6">
                            <label>Unit</label>
                            <input type="number" id="unit" class="form-control">
                        </div>

                        <!-- Type -->
                        <div class="col-md-6">
                            <label>Type</label>
                            <select id="course_type" class="form-control">
                                <option value="core">Core</option>
                                <option value="elective">Elective</option>
                            </select>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary">Save Course</button>
                </div>

            </form>

        </div>
    </div>
</div>