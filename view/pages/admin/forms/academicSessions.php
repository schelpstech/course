<div class="row">
    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Academic Sessions</h5>

            <button class="btn btn-info" id="addSessionBtn">
                <i class="ph ph-plus"></i> Add Session
            </button>
        </div>

        <div class="card-body">

            <table id="sessionTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>S/N</th>
                        <th>Session</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

        </div>
    </div>
</div>

<div class="modal fade" id="sessionModal">
    <div class="modal-dialog">
        <div class="modal-content">

            <form id="sessionForm">

                <div class="modal-header">
                    <h5 id="sessionModalTitle">Add Session</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" id="session_id">

                    <div class="mb-3">
                        <label>Session Name</label>
                        <input type="text" id="name" class="form-control" placeholder="2025/2026">
                    </div>

                    <div class="mb-3">
                        <label>Start Date</label>
                        <input type="date" id="start_date" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>End Date</label>
                        <input type="date" id="end_date" class="form-control">
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