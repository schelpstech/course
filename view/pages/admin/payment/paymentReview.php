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
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <!-- HEADER -->
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Payment Review</h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body">

                <input type="hidden" id="payment_id">

                <!-- REFERENCE -->
                <div class="mb-3">
                    <h5 class="mb-0">
                        <strong>Reference:</strong>
                        <span id="payment_ref" class="text-primary"></span>
                    </h5>
                </div>

                <hr>

                <!-- PROOF -->
                <div class="mb-3 text-center">
                    <div id="proofBox"></div>

                    <a id="downloadProofBtn"
                        href="#"
                        target="_blank"
                        class="btn btn-outline-primary btn-sm mt-2">
                        Open / Download Proof
                    </a>
                </div>

                <hr>

                <!-- PAYMENT BREAKDOWN -->
                <div class="card mb-3">
                    <div class="card-header">
                        Payment Evaluation
                    </div>

                    <div class="card-body">

                        <div class="row text-center">

                            <div class="col-md-4">
                                <small class="text-muted">Institution Terms</small>
                                <h5 id="institution_percentage">0%</h5>
                            </div>

                            <div class="col-md-4">
                                <small class="text-muted">Semester Fee</small>
                                <h5 id="semester_fee">₦0.00</h5>
                            </div>

                            <div class="col-md-4">
                                <small class="text-muted">Amount Paid</small>
                                <h5 id="amount_paid">₦0.00</h5>
                            </div>

                        </div>

                        <hr>

                        <!-- MESSAGE -->
                        <div id="payment_message"></div>

                    </div>
                </div>

                <!-- ADMIN REMARK -->
                <div class="mb-3">
                    <label class="form-label">Admin Remark</label>
                    <textarea id="admin_note"
                        class="form-control"
                        rows="3"
                        placeholder="Enter approval or rejection reason"></textarea>
                </div>

            </div>

            <!-- FOOTER -->
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