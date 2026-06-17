<div class="row">
    <div class="col-sm-12">
        <div class="card">

            <!-- HEADER -->
            <div class="card-header table-card-header">
                <h5>Course Registration Tracker</h5>
                <small>Manage student course registrations by session and semester</small>
            </div>

            <!-- FILTER SECTION -->
            <div class="card-body">
                <div class="row mb-3">

                    <div class="col-md-4">
                        <label>Session</label>
                        <select id="sessionFilter" class="form-control">
                            <option value="">Select Session</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Semester</label>
                        <select id="semesterFilter" class="form-control">
                            <option value="">Select Semester</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- TABLE SECTION -->
            <div class="card-body">

                <div class="dt-responsive table-responsive">

                    <table id="courseformTable" class="table table-striped table-bordered dataTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Level</th>
                                <th>Courses</th>
                                <th>Status</th>
                                <th>Course Clearance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody id="courseformTableBody">
                            <!-- AJAX LOADED -->
                        </tbody>

                        <tfoot>
                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Level</th>
                                <th>Courses</th>
                                <th>Status</th>
                                <th>Course Clearance</th>
                                <th>Actions</th>
                            </tr>
                        </tfoot>
                    </table>

                </div>
            </div>

        </div>
    </div>
</div>


<div class="modal fade" id="coursesModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Registered Courses</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" id="coursesModalBody">
        <div class="text-center">Loading...</div>
      </div>

    </div>
  </div>
</div>

<style>
.status-badge {
    padding: 5px 10px;
    border-radius: 6px;
    color: #fff;
    font-size: 12px;
    text-transform: uppercase;
}

.status-pending { background: #f0ad4e; }
.status-submitted { background: #5bc0de; }
.status-approved { background: #5cb85c; }
.status-rejected { background: #d9534f; }

.device-col {
    max-width: 250px;
    white-space: normal !important;
    word-break: break-word;
}
</style>