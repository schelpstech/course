<div class="row">
    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Manage Departments</h5>

            <button class="btn btn-info" id="addDepartmentBtn">
                <i class="ph ph-plus"></i> Add Department
            </button>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table id="departmentTable" class="table table-hover">

                    <thead>
                        <tr>
                            <th>SN</th>
                            <th>Programme</th>
                            <th>Department Name</th>
                            <th>Code</th>
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

<div class="modal fade" id="departmentModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form id="departmentForm">

                <div class="modal-header">
                    <h5 id="departmentModalTitle">Add Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" id="dept_id">

                    <div class="row g-3">

                        <!-- Institution -->
                        <div class="col-md-12">
                            <label>Institution</label>
                            <select id="institution_id" class="form-control" required>
                                <option value="">Select Institution</option>
                            </select>
                        </div>

                        <!-- Programme (DYNAMIC) -->
                        <div class="col-md-12">
                            <label>Programme</label>
                            <select id="programme_id" class="form-control" required>
                                <option value="">Select Programme</option>
                            </select>
                        </div>

                        <!-- Department Name -->
                        <div class="col-md-6">
                            <label>Department Name</label>
                            <input type="text" id="name" class="form-control" required>
                        </div>

                        <!-- Code -->
                        <div class="col-md-6">
                            <label>Department Code</label>
                            <input type="text" id="code" class="form-control" required>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Department</button>
                </div>

            </form>

        </div>
    </div>
</div>