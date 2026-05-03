<div class="container-fluid mt-4">

    <div class="d-flex justify-content-between mb-3">
        <h4>Payment Review Dashboard</h4>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <table id="paymentTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Matric</th>
                        <th>Reference</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Mode</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>

        </div>
    </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Payment Review</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <input type="hidden" id="payment_id">

                <p><strong>Reference:</strong> <span id="payment_ref"></span></p>

                <div class="mb-3 text-center" id="proofBox"></div>
                <a id="downloadProofBtn"
                    href="#"
                    target="_blank"
                    class="btn btn-outline-primary btn-sm mb-2">
                    Download / Open Full PDF
                </a>
                <div class="mb-3">
                    <label>Admin Remark</label>
                    <textarea id="admin_note" class="form-control" rows="3"
                        placeholder="Enter approval / rejection reason"></textarea>
                </div>

            </div>

            <div class="modal-footer">

                <button class="btn btn-success" id="approveBtn">
                    Approve
                </button>

                <button class="btn btn-danger" id="rejectBtn">
                    Reject
                </button>

            </div>

        </div>
    </div>
</div>