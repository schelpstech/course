<div class="row">
    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Manage Levels</h5>

            <button class="btn btn-info" id="addLevelBtn">
                <i class="ph ph-plus"></i> Add Level
            </button>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table id="levelTable" class="table table-hover">

                    <thead>
                        <tr>
                            <th>S/N</th>
                            <th>Department</th>
                            <th>Level</th>
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

<div class="modal fade" id="levelModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form id="levelForm">

                <div class="modal-header">
                    <h5 id="levelModalTitle">Add Level</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" id="level_id">

                    <div class="row g-3">

                        <!-- Institution -->
                        <div class="col-md-4">
                            <label>Institution</label>
                            <select id="institution_id" class="form-control"></select>
                        </div>

                        <!-- Programme -->
                        <div class="col-md-4">
                            <label>Programme</label>
                            <select id="programme_id" class="form-control"></select>
                        </div>

                        <!-- Department -->
                        <div class="col-md-4">
                            <label>Department</label>
                            <select id="department_id" class="form-control"></select>
                        </div>

                        <!-- Level Name -->
                        <div class="col-md-6">
                            <label>Level</label>
                            <input type="text" id="name" class="form-control" placeholder="e.g 100, ND1">
                        </div>

                        <!-- Code -->
                        <div class="col-md-6">
                            <label>Code</label>
                            <input type="text" id="code" class="form-control">
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary">Save Level</button>
                </div>

            </form>

        </div>
    </div>
</div>