<div class="row">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Configure Institution Clearance Types</h5>

            <button class="btn btn-info" id="addClearanceBtn">
                <i class="ph ph-plus"></i> Add Clearance Type
            </button>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table id="clearanceTable" class="table table-striped table-bordered dataTable">
                    <thead>
                        <tr>
                            <th>SN</th>
                            <th>Institution</th>
                            <th>Clearance Name</th>
                            <th>Code</th>
                            <th>Mandatory</th>
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

<div class="modal fade" id="clearanceModal">
    <div class="modal-dialog">
        <div class="modal-content">

            <form id="clearanceForm">

                <div class="modal-header">
                    <h5 id="clearanceModalTitle">Add Clearance Type</h5>
                    <button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" id="clearance_id">

                    <div class="row g-3">

                        <div class="col-md-12">
                            <label>Institution</label>
                            <select id="institution_id"
                                class="form-control" required>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label>Clearance Name</label>
                            <input type="text"
                                id="name"
                                class="form-control"
                                required>
                        </div>

                        <div class="col-md-12">
                            <label>Code</label>
                            <input type="text"
                                id="code"
                                class="form-control"
                                placeholder="PAYMENT"
                                required>
                        </div>

                        <div class="col-md-12">
                            <label>Description</label>
                            <textarea id="description"
                                class="form-control"></textarea>
                        </div>

                        <div class="col-md-12">
                            <label>Mandatory</label>
                            <select id="is_mandatory"
                                class="form-control">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label>Status</label>
                            <select id="status"
                                class="form-control">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit"
                        class="btn btn-primary">
                        Save Clearance Type
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>