<div class="row">
    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Manage Semesters</h5>

            <button class="btn btn-info" id="addSemesterBtn">
                <i class="ph ph-plus"></i> Add Semester
            </button>
        </div>

        <div class="card-body">

            <table id="semesterTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>S/N</th>
                        <th>Semester</th>
                        <th>Session</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

        </div>
    </div>
</div>

<div class="modal fade" id="semesterModal">
  <div class="modal-dialog">
    <div class="modal-content">

      <form id="semesterForm">

        <div class="modal-header">
          <h5 id="semesterModalTitle">Add Semester</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <input type="hidden" id="semester_id">

          <!-- SESSION -->
          <div class="mb-3">
            <label>Session</label>
            <select id="session_id" class="form-control"></select>
          </div>

          <!-- SEMESTER -->
          <div class="mb-3">
            <label>Semester</label>
            <select id="name" class="form-control">
              <option value="First">First Semester</option>
              <option value="Second">Second Semester</option>
            </select>
          </div>

          <!-- DATES -->
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
          <button class="btn btn-primary">Save Semester</button>
        </div>

      </form>

    </div>
  </div>
</div>