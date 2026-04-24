<div class="row">

    <!-- ========================== -->
    <!-- HEADER -->
    <!-- ========================== -->
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center">

                <div>
                    <h4 class="mb-1">Student Management</h4>
                    <p class="text-muted mb-0">Add, view and manage all registered students</p>
                </div>

                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal" id="addStudentBtn">
                    + Add Student
                </button>

            </div>
        </div>
    </div>

    <!-- ========================== -->
    <!-- LAB / GROUP / PLACEMENT SECTION -->
    <!-- ========================== -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6>Students per Department</h6>
            </div>

            <div class="card-body">

                <div class="list-group" id="deptStatsList">
                    <!-- AJAX LOAD HERE -->
                </div>

            </div>
        </div>
    </div>

    <!-- ========================== -->
    <!-- STUDENT TABLE -->
    <!-- ========================== -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6>All Students</h6>
            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <div class="card-body">

                        <div class="table-responsive">

                            <table id="studentsTable" class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Matric No</th>
                                        <th>Programme</th>
                                        <th>Level</th>
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
        </div>
    </div>

</div>

<!-- ========================== -->
<!-- ADD STUDENT MODAL -->
<!-- ========================== -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <!-- HEADER -->
            <div class="modal-header">
                <h5 class="modal-title">Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- FORM -->
            <form id="studentForm">

                <div class="modal-body">

                    <div class="row g-3">
                        <input type="hidden" id="student_id">
                        <!-- Matric Number -->
                        <div class="col-md-6">
                            <label class="form-label">Matric Number</label>
                            <input type="text" name="matric_no" class="form-control" required>
                        </div>
                        <!-- Email Address -->
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <!-- First Name -->
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>

                        <!-- Other Name -->
                        <div class="col-md-6">
                            <label class="form-label">Other Name</label>
                            <input type="text" name="other_name" class="form-control">
                        </div>

                        <!-- Last Name -->
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>

                        <!-- Date of Birth -->
                        <div class="col-md-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="dob" class="form-control" required>
                        </div>

                        <!-- Gender -->
                        <div class="col-md-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select Gender</option>
                                <option value="1">Male</option>
                                <option value="2">Female</option>
                            </select>
                        </div>

                        <!-- Institution -->
                        <div class="col-md-6">
                            <label class="form-label">Institution</label>
                            <select name="institution_id" id="institution" class="form-control" required>
                                <option value="">Select Institution</option>
                            </select>
                        </div>

                        <!-- Programme -->
                        <div class="col-md-6">
                            <label class="form-label">Programme</label>
                            <select name="programme_id" id="programme" class="form-control" required>
                                <option value="">Select Programme</option>
                            </select>
                        </div>

                        <!-- Department -->
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select name="department_id" id="department" class="form-control" required>
                                <option value="">Select Department</option>
                            </select>
                        </div>

                        <!-- Level -->
                        <div class="col-md-6">
                            <label class="form-label">Level</label>
                            <select name="level_id" id="level" class="form-select" required>
                                <option value="">Select Level</option>
                            </select>
                        </div>
                    </div>
                </div>
                <!-- FOOTER -->
                <div class="modal-footer">
                    <input type="hidden" name="csrf_token" value="<?= $utility->generateCsrf('add-student-form'); ?>">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Create Student
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>