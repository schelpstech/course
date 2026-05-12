<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header">
                <h5>Upload Payment Proof for the <?= $CurrentSemester; ?></h5>
            </div>
            <div class="card-body">
                <form id="paymentForm" action="../api/student/upload-receipt.php" method="POST" enctype="multipart/form-data">

                    <div class="row">

                        <!-- Current Semester -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Current Semester</label>
                            <select name="semester_id" class="form-control" required>
                                <option value="<?= $activeSemester['id']; ?>" selected>
                                    <?= $CurrentSemester; ?>
                                </option>
                            </select>
                        </div>

                        <!-- Amount Paid -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount Paid (₦)</label>
                            <input type="number" name="amount_paid" class="form-control" required>
                        </div>

                        <!-- Mode of Payment -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Mode of Payment</label>
                            <select name="payment_mode" class="form-control" required>
                                <option value="">Select Mode</option>
                                <option value="manual" selected>Bank Transfer</option>
            
                            </select>
                        </div>

                        <!-- Date of Payment -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Receipt Number</label>
                            <input type="text" name="receipt_number" minlength="4" maxlength="10" class="form-control" required>
                        </div>
                        <!-- Date of Payment -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Date of Payment</label>
                            <input type="date" name="payment_date" class="form-control" required>
                        </div>

                        <!-- Upload Evidence -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Upload Evidence (PDF only)</label>
                            <input type="file" name="payment_proof" id="paymentProofInput" class="form-control" accept="application/pdf" required>
                            <small class="text-muted">Max size: 100KB</small>
                        </div>

                        <!-- File Info Preview -->
                        <div class="col-md-6 mb-3 text-center">
                            <div id="fileInfo" class="border rounded p-3">
                                <p class="mb-1">No file selected</p>
                                <small class="text-muted">PDF only</small>
                            </div>
                        </div>

                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <input type="hidden" name="csrf_token" value="<?= $utility->generateCsrf('paymentform'); ?>">

                            <div class="col-md-6 offset-md-3 mb-3 text-center">
                                <button type="submit" class="btn btn-success px-4">
                                    Submit Payment
                                </button>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>



<script>
    document.getElementById("paymentProofInput").addEventListener("change", function() {
        const file = this.files[0];
        const fileInfo = document.getElementById("fileInfo");

        if (file) {
            fileInfo.innerHTML = `
            <p class="mb-1"><strong>${file.name}</strong></p>
            <small>${(file.size / 1024).toFixed(2)} KB</small>
        `;
        } else {
            fileInfo.innerHTML = `
            <p class="mb-1">No file selected</p>
            <small class="text-muted">PDF only</small>
        `;
        }
    });
</script>