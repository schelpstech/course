<div class="row">

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">

            <h5>Payment Clearance Management</h5>

            <div class="row g-2">

                <div class="col-auto">
                    <select id="sessionFilter" class="form-control">
                        <option value="">All Sessions</option>
                    </select>
                </div>

                <div class="col-auto">
                    <select id="semesterFilter" class="form-control">
                        <option value="">All Semesters</option>
                    </select>
                </div>

            </div>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table id="paymentClearanceTable"
                    class="table table-striped table-bordered dataTable">

                    <thead>
                        <tr>
                            <th>SN</th>
                            <th>Student</th>
                            <th>Matric No</th>
                            <th>Department</th>
                            <th>Level</th>
                            <th>Semester</th>
                            <th>Required</th>
                            <th>Paid</th>
                            <th>Eligibility</th>
                            <th>Clearance</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody></tbody>

                </table>

            </div>

        </div>

    </div>

</div>


<!-- PAYMENT REVIEW MODAL -->

<div class="modal fade"
    id="paymentReviewModal"
    tabindex="-1">

    <div class="modal-dialog modal-xl">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    Payment Clearance Review
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <input type="hidden"
                    id="semester_registration_id">

                <input type="hidden"
                    id="clearance_type_id">

                <div class="row">

                    <!-- STUDENT INFO -->

                    <div class="col-md-4">

                        <div class="card">

                            <div class="card-header">
                                Student Information
                            </div>

                            <div class="card-body">

                                <p>
                                    <strong>Name:</strong>
                                    <span id="student_name"></span>
                                </p>

                                <p>
                                    <strong>Matric No:</strong>
                                    <span id="matric_no"></span>
                                </p>

                                <p>
                                    <strong>Institution:</strong>
                                    <span id="institution_name"></span>
                                </p>

                                <p>
                                    <strong>Department:</strong>
                                    <span id="department_name"></span>
                                </p>

                                <p>
                                    <strong>Level:</strong>
                                    <span id="level_name"></span>
                                </p>

                            </div>

                        </div>

                    </div>

                    <!-- PAYMENT SUMMARY -->

                    <div class="col-md-8">

                        <div class="card">

                            <div class="card-header">
                                Payment Analysis
                            </div>

                            <div class="card-body">

                                <div class="row">

                                    <div class="col-md-3">
                                        <div class="alert alert-light">
                                            <strong>School Fee</strong>
                                            <br>
                                            <span id="school_fee"></span>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="alert alert-light">
                                            <strong>Required %</strong>
                                            <br>
                                            <span id="required_percent"></span>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="alert alert-light">
                                            <strong>Required Amount</strong>
                                            <br>
                                            <span id="required_amount"></span>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="alert alert-light">
                                            <strong>Amount Paid</strong>
                                            <br>
                                            <span id="amount_paid"></span>
                                        </div>
                                    </div>

                                </div>

                                <div id="eligibilityBox"></div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- PAYMENT HISTORY -->

                <div class="card mt-3">

                    <div class="card-header">
                        Payment History
                    </div>

                    <div class="card-body">

                        <div class="table-responsive">

                            <table class="table table-bordered">

                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Mode</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>

                                <tbody id="paymentHistoryBody"></tbody>

                            </table>

                        </div>

                    </div>

                </div>


                <!-- REMARK -->

                <div class="mt-3">

                    <label>
                        Remark
                    </label>

                    <textarea
                        id="clearance_remark"
                        class="form-control"
                        rows="3"></textarea>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-light"
                    data-bs-dismiss="modal">

                    Close

                </button>

                <button
                    type="button"
                    id="approveClearanceBtn"
                    class="btn btn-success">

                    Approve Clearance

                </button>

            </div>

        </div>

    </div>

</div>