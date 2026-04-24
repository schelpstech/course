<div class="row">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Manage Programmes</h5>

            <button class="btn btn-info" id="addProgrammeBtn">
                <i class="ph ph-plus"></i> Add Programme
            </button>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table id="programmeTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>SN</th>
                            <th>Institution</th>
                            <th>Programme Name</th>
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

<!-- MODAL -->
<div class="modal fade" id="programmeModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form id="programmeForm">

                <div class="modal-header">
                    <h5 id="programmeModalTitle">Add Programme</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" id="prog_id">
                    <input type="hidden" id="action">

                    <div class="row g-3">

                        <!-- Institution -->
                        <div class="col-md-12">
                            <label>Institution</label>
                            <select id="institution_id" class="form-control" required>
                                <option value="">Select Institution</option>
                            </select>
                        </div>

                        <!-- Programme Name -->
                        <div class="col-md-6">
                            <label>Programme Name</label>
                            <input type="text" id="name" class="form-control" required>
                        </div>

                        <!-- Code -->
                        <div class="col-md-6">
                            <label>Programme Code</label>
                            <input type="text" id="code" class="form-control" required>
                        </div>


                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Programme</button>
                </div>

            </form>

        </div>
    </div>
</div>