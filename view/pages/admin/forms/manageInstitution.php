
<div class="row">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Manage Institutions</h5>

            <button class="btn btn-info" id="addNewBtn">
                <i class="ph ph-plus"></i> Add Institution
            </button>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table id="institutionTable"  class="table table-striped table-bordered dataTable">
                    <thead>
                        <tr>
                            <th>Logo</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                           
                    </tbody>

                </table>
            </div>

        </div>
    </div>
</div>

<!-- MODAL -->
<div class="modal fade" id="institutionModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form id="institutionForm" enctype="multipart/form-data">

                <div class="modal-header">
                    <h5 id="modalTitle">Add Institution</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" id="inst_id">
                    <input type="hidden" id="action" ">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label>Name</label>
                            <input type="text" id="name" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label>Email</label>
                            <input type="email" id="email" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label>Slogan</label>
                            <input type="text" id="slogan" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label>Logo</label>
                            <input type="file" id="logo" class="form-control">
                        </div>

                        <div class="col-md-12">
                            <label>Address</label>
                            <textarea id="address" class="form-control" required></textarea>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary">Save</button>
                </div>

            </form>

        </div>
    </div>
</div>

