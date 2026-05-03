<div class="row">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Assign School Fees to a Level in a Department for Current Session</h5>

            <button class="btn btn-info" id="addFeeBtn">
                <i class="ph ph-plus"></i> Assign School Fees for Session
            </button>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table id="feeTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>SN</th>
                            <th>Session</th>
                            <th>Department</th>
                            <th>Level</th>
                            <th>Amount</th>
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

<div class="modal fade" id="feeModal">
    <div class="modal-dialog">
        <div class="modal-content">

            <form id="feeForm">

                <div class="modal-header">
                    <h5 id="feeModalTitle">Assign School Fee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" id="fee_id">

                    <!-- Session -->
                    <div class="mb-3">
                        <label>Session</label>
                        <select id="session_id" class="form-control" required></select>
                    </div>

                    <!-- Institution -->
                    <div class="mb-3">
                        <label>Institution</label>
                        <select id="institution_id" class="form-control" required></select>
                    </div>

                    <!-- Programme -->
                    <div class="mb-3">
                        <label>Programme</label>
                        <select id="programme_id" class="form-control" required></select>
                    </div>

                    <!-- Department -->
                    <div class="mb-3">
                        <label>Department</label>
                        <select id="department_id" class="form-control" required></select>
                    </div>

                    <!-- Level -->
                    <div class="mb-3">
                        <label>Level</label>
                        <select id="level_id" class="form-control" required></select>
                    </div>

                    <!-- Amount -->
                    <div class="mb-3">
                        <label>Amount (₦)</label>
                        <input type="number" id="amount" class="form-control" required>
                    </div>

                    <!-- Status -->
                    <div class="mb-3">
                        <label>Status</label>
                        <select id="status" class="form-control">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>

            </form>

        </div>
    </div>
</div>