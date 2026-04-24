<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header">
                <h5>Complete Your Profile</h5>
            </div>
            <div class="card-body">
                <?php

                $student = null;

                if (!empty($_SESSION['user_id'])) {
                    $student = $model->getRows('students', [
                        'where' => ['student_id' => $_SESSION['user_id']],
                        'return_type' => 'single',
                        'join' => [
                            'users' => 'ON users.id = students.student_id'
                        ],
                    ]);
                }
                ?>

                <form id="profileForm" action="../api/student/saveprofile.php" method="POST" enctype="multipart/form-data">

                    <div class="row">

                        <!-- Passport Upload -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Passport</label>
                            <input type="file" name="passport" id="passportInput" class="form-control" accept="image/*">
                            <small class="text-muted">Max 100KB (JPG/PNG)</small>
                        </div>

                        <!-- Preview -->
                        <div class="col-md-6 mb-3 text-center">
                            <img
                                id="passportPreview"
                                src="<?= !empty($student['passport'])
                                            ? '../' . htmlspecialchars($student['passport'])
                                            : '../assets/images/storage/placeholders/student.png'; ?>"
                                alt="Passport Preview"
                                class="img-thumbnail mb-2"
                                style="width: 150px; height: 150px; object-fit: cover;">
                            <label class="form-label d-block">Passport Photograph</label>
                        </div>

                        <!-- Matric No -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Matric Number</label>
                            <input type="text" readonly class="form-control"
                                value="<?= htmlspecialchars($student['matric_no'] ?? '') ?>" required>
                        </div>

                        <!-- First Name -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" readonly class="form-control"
                                value="<?= htmlspecialchars($student['first_name'] ?? '') ?>" required>
                        </div>

                        <!-- Last Name -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" readonly class="form-control"
                                value="<?= htmlspecialchars($student['last_name'] ?? '') ?>" required>
                        </div>

                        <!-- Other Name -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Other Name</label>
                            <input type="text" readonly class="form-control"
                                value="<?= htmlspecialchars($student['other_name'] ?? '') ?>">
                        </div>

                        <!-- Gender -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender</label>
                            <select readonly class="form-control">
                                <option value="1" <?= ($student['gender'] ?? '') == '1' ? 'selected' : '' ?>>Male</option>
                                <option value="2" <?= ($student['gender'] ?? '') == '2' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>

                        <!-- Date of Birth -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" readonly class="form-control"
                                value="<?= htmlspecialchars($student['dateofbirth'] ?? '') ?>">
                        </div>

                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control"
                                value="<?= htmlspecialchars($student['email'] ?? '') ?>" readonly>
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control"
                                value="<?= htmlspecialchars($student['phone'] ?? '') ?>" required minlength="11" maxlength="11" >
                        </div>

                        <!-- Institution -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Institution</label>
                            <select readonly class="form-control" required>
                                <?php
                                if (!empty($student['institution_id'])) {
                                    $inst = $model->getRows('institutions', [
                                        'where' => ['id' => $student['institution_id']],
                                        'return_type' => 'single'
                                    ]);
                                    if ($inst) {
                                        echo '<option value="' . $inst['id'] . '" selected>' . $inst['name'] . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Programme -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Programme</label>
                            <select readonly class="form-control" required>
                                <?php
                                if (!empty($student['programme_id'])) {
                                    $prog = $model->getRows('programmes', [
                                        'where' => ['id' => $student['programme_id']],
                                        'return_type' => 'single'
                                    ]);
                                    if ($prog) {
                                        echo '<option value="' . $prog['id'] . '" selected>' . $prog['name'] . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Department -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department</label>
                            <select class="form-control" readonly required>
                                <?php
                                if (!empty($student['department_id'])) {
                                    $dept = $model->getRows('department', [
                                        'where' => ['id' => $student['department_id']],
                                        'return_type' => 'single'
                                    ]);
                                    if ($dept) {
                                        echo '<option value="' . $dept['id'] . '" selected>' . $dept['name'] . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Level -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Level</label>
                            <select class="form-control" readonly required>
                                <?php
                                if (!empty($student['level_id'])) {
                                    $level = $model->getRows('levels', [
                                        'where' => ['id' => $student['level_id']],
                                        'return_type' => 'single'
                                    ]);
                                    if ($level) {
                                        echo '<option value="' . $level['id'] . '" selected>' . $level['name'] . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <input type="hidden" name="csrf_token" value="<?= $utility->generateCsrf('studentform'); ?>">
                            <div class="col-md-6 offset-md-3 mb-3 text-center">
                                <button type="submit" class="btn btn-primary px-4">
                                    <?= $student ? 'Update Profile' : 'Save Profile' ?>
                                </button>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
</div>