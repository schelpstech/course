<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header table-card-header">
                <h5>Semester Registration Tracker</h5>
                <small>Track all students progress with semester registration activities</small>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select id="sessionFilter" class="form-control"></select>
                    </div>

                    <div class="col-md-4">
                        <select id="semesterFilter" class="form-control"></select>
                    </div>
                </div>
            </div>



            <div class="card-body">
                <div class="dt-responsive table-responsive">
                    <table id="regTable" class="table table-striped table-bordered dataTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Department</th>
                                <th>Receipt Upload</th>
                                <th>Validation</th>
                                <th>Internet Fee</th>
                                <th>Registration</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                        </tbody>

                        <tfoot>
                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Department</th>
                                <th>Receipt Upload</th>
                                <th>Validation</th>
                                <th>Internet Fee</th>
                                <th>Registration</th>
                                <th>Status</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .device-col {
        max-width: 250px;
        white-space: normal !important;
        word-break: break-word;
        overflow-wrap: anywhere;
    }
</style>