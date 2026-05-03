<div class="row">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Configure Institution Payment Terms</h5>

            <button class="btn btn-info" id="addPaymentBtn">
                <i class="ph ph-plus"></i> Add Payment Terms
            </button>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table id="paymentTable" class="table table-striped table-bordered dataTable">
                    <thead>
                        <tr>
                            <th>SN</th>
                            <th>Institution</th>
                            <th>Minimum Payment (%)</th>
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
<div class="modal fade" id="paymentModal">
    <div class="modal-dialog">
        <div class="modal-content">

            <form id="paymentForm">

                <div class="modal-header">
                    <h5 id="paymentModalTitle">Add Payment Terms</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" id="payment_id">
                    <input type="hidden" id="action">

                    <div class="row g-3">

                        <!-- Institution -->
                        <div class="col-md-12">
                            <label>Institution</label>
                            <select id="institution_id" class="form-control" required>
                                <option value="">Select Institution</option>
                            </select>
                        </div>

                        <!-- Minimum Payment % -->
                        <div class="col-md-12">
                            <label>Minimum Payment Percentage (%)</label>
                            <input type="number" id="min_percent" class="form-control" min="1" max="100" required>
                        </div>

                        <!-- Status -->
                        <div class="col-md-12">
                            <label>Status</label>
                            <select id="status" class="form-control">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Payment Terms</button>
                </div>

            </form>

        </div>
    </div>
</div>