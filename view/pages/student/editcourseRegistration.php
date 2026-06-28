<?php

$currentPage = $_SESSION['pageid'] ?? 'editCourseRegistration';

$studentId  = $_SESSION['user_id'];
$semester   = $activeSemester['name'];
$semesterID = (int) $activeSemester['id'];
$session    = $activeSession['name'];
$sessionID  = (int) $activeSession['id'];

/* ===================== */
/* FETCH STUDENT */
/* ===================== */

$user = $model->getRows('students', [
    'select' => 'students.*, department.name as department_name, levels.name as level_name, programmes.name as programme_name',
    'join' => [
        'levels'     => ' on students.level_id = levels.id',
        'programmes' => ' on programmes.id = students.programme_id',
        'department' => ' on department.id = students.department_id',
    ],
    'where' => [
        'students.student_id' => $studentId
    ],
    'return_type' => 'single'
]);

if (empty($user)) {
    redirectWithToast('error', 'Student record not found.', 'studentDashboard');
    exit;
}

$studentLevelId = (int) ($user['level_id'] ?? 0);

/* ===================== */
/* FETCH SEMESTER REGISTRATION */
/* ===================== */

$semesterRegistration = $model->getRows('semesterregistration', [
    'where' => [
        'student_id'  => $studentId,
        'semester_id' => $semesterID,
        'session_id'  => $sessionID
    ],
    'return_type' => 'single'
]);

if (empty($semesterRegistration)) {
    redirectWithToast('error', 'Semester registration record not found.', 'studentDashboard');
    exit;
}

$semesterRegLevelId = !empty($semesterRegistration['studentLevelId'])
    ? (int) $semesterRegistration['studentLevelId']
    : 0;

/* ===================== */
/* FETCH LEVEL NAMES */
/* ===================== */

$studentLevel = null;
$semesterRegLevel = null;

if (!empty($studentLevelId)) {
    $studentLevel = $model->getRows('levels', [
        'where' => [
            'id' => $studentLevelId
        ],
        'return_type' => 'single'
    ]);
}

if (!empty($semesterRegLevelId)) {
    $semesterRegLevel = $model->getRows('levels', [
        'where' => [
            'id' => $semesterRegLevelId
        ],
        'return_type' => 'single'
    ]);
}

$studentLevelName = $studentLevel['name'] ?? $user['level_name'] ?? 'Unknown Level';
$semesterRegLevelName = $semesterRegLevel['name'] ?? 'Not Set';

/* ===================== */
/* FETCH EXISTING COURSE REGISTRATION */
/* ===================== */

$reg = $model->getRows('course_registered', [
    'where' => [
        'student_id' => $studentId,
        'semester'   => $semesterID,
        'session'    => $sessionID,
    ],
    'return_type' => 'single'
]);

if (empty($reg)) {
    redirectWithToast('error', 'No course registration found for this semester. Please register your courses first.', 'courseRegistration');
    exit;
}

$approvalStatus = strtolower(trim($reg['approval_status'] ?? ''));

if (!in_array($approvalStatus, ['submitted', 'rejected'], true)) {
    redirectWithToast('error', 'Editing is not allowed after course form approval or final review.', 'studentDashboard');
    exit;
}

/* ===================== */
/* HANDLE LEVEL CONFIRMATION */
/* ===================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['confirm_level_action'])
    && $_POST['confirm_level_action'] === 'confirm_level'
) {
    if (!$utility->validateRequest($_POST['level_csrf_token'] ?? '', 'edit_levelconfirmation')) {
        redirectWithToast('error', 'Unauthorized level confirmation request.', $currentPage);
        exit;
    }

    $selectedLevelId = isset($_POST['confirmed_level_id'])
        ? (int) $_POST['confirmed_level_id']
        : 0;

    /*
        Security:
        Student can only choose between the two level IDs already found
        in students.level_id and semesterregistration.studentLevelId.
    */
    $allowedLevelIds = [];

    if (!empty($studentLevelId)) {
        $allowedLevelIds[] = $studentLevelId;
    }

    if (!empty($semesterRegLevelId)) {
        $allowedLevelIds[] = $semesterRegLevelId;
    }

    $allowedLevelIds = array_values(array_unique($allowedLevelIds));

    if (empty($selectedLevelId) || !in_array($selectedLevelId, $allowedLevelIds, true)) {
        redirectWithToast('error', 'Invalid level selected.', $currentPage);
        exit;
    }

    $selectedLevel = $model->getRows('levels', [
        'where' => [
            'id' => $selectedLevelId
        ],
        'return_type' => 'single'
    ]);

    if (empty($selectedLevel)) {
        redirectWithToast('error', 'Selected level does not exist.', $currentPage);
        exit;
    }

    $model->beginTransaction();

    try {

        /*
            Update students table
        */
        $model->update(
            'students',
            [
                'level_id' => $selectedLevelId
            ],
            [
                'student_id' => $studentId
            ]
        );

        /*
            Update semesterregistration table for active session and semester only
        */
        $model->update(
            'semesterregistration',
            [
                'studentLevelId' => $selectedLevelId
            ],
            [
                'student_id'  => $studentId,
                'session_id'  => $sessionID,
                'semester_id' => $semesterID
            ]
        );

        $model->commit();

        $utility->logActivityUsers(
            'Student corrected level mismatch while editing course registration. Student ID: ' . $studentId . ', Level ID: ' . $selectedLevelId,
            $_SESSION['user_email'] ?? 'Unknown'
        );

        redirectWithToast(
            'success',
            'Your current level has been confirmed successfully. Please review your courses carefully before submitting.',
            $currentPage
        );
        exit;

    } catch (Exception $e) {

        $model->rollBack();

        redirectWithToast(
            'error',
            'Level confirmation failed. Reason: ' . $e->getMessage(),
            $currentPage
        );
        exit;
    }
}

/* ===================== */
/* CHECK LEVEL MISMATCH */
/* ===================== */

$levelMismatch = false;

if (empty($studentLevelId) || empty($semesterRegLevelId) || $studentLevelId !== $semesterRegLevelId) {
    $levelMismatch = true;
}

$canEditCourses = !$levelMismatch;

/*
    Once both tables agree, this is the confirmed level.
*/
$confirmedLevelId = $studentLevelId;

/* ===================== */
/* FETCH SELECTED COURSES */
/* ===================== */

$registeredCourses = [];

if ($canEditCourses) {
    $registeredCourses = $model->getRows('registered_course', [
        'where' => [
            'course_regID' => $reg['course_regID']
        ]
    ]);
}

$selectedIds = array_map('intval', array_column($registeredCourses, 'course_id'));

/* ===================== */
/* FETCH ALL COURSES FOR CONFIRMED LEVEL */
/* ===================== */

$deptCourses = [];

if ($canEditCourses) {
    $deptCourses = $model->getRows('courses', [
        'where' => [
            'level_id'      => $confirmedLevelId,
            'semester_id'   => $semesterID,
            'course_status' => 1
        ]
    ]);
}

?>

<!-- ALERTS -->
<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-xl-10 offset-xl-1 col-md-12">

        <!-- ===================== -->
        <!-- INSTITUTION HEADER -->
        <!-- ===================== -->
        <div class="row mb-4 align-items-center">
            <div class="col-md-2 text-center">
                <img src="../uploads/logo/<?= htmlspecialchars($institution['inst_logo'] ?? 'default.png'); ?>"
                    style="width:80px;height:80px;border-radius:10px;object-fit:cover;">
            </div>

            <div class="col-md-10 text-center">
                <h3 style="font-weight:700;">
                    <?= htmlspecialchars($institution['name'] ?? 'Institution Name'); ?>
                </h3>

                <p style="margin:0;">Course Registration Form</p>

                <p>
                    <?= strtoupper(htmlspecialchars($semester)); ?> SEMESTER
                    <?= strtoupper(htmlspecialchars($session)); ?> ACADEMIC SESSION
                </p>
            </div>
        </div>

        <hr>

        <!-- ===================== -->
        <!-- STUDENT INFO -->
        <!-- ===================== -->
        <div class="row mb-4 align-items-center">

            <div class="col-md-10">
                <table class="table table-sm table-border mb-0">
                    <tr>
                        <td><strong>Name:</strong></td>
                        <td>
                            <h5>
                                <strong>
                                    <?= htmlspecialchars(trim($user['first_name'] . ' ' . ($user['other_name'] ?? '') . ' ' . $user['last_name'])); ?>
                                </strong>
                            </h5>
                        </td>

                        <td><strong>Matric:</strong></td>
                        <td>
                            <h6>
                                <strong><?= htmlspecialchars($user['matric_no']); ?></strong>
                            </h6>
                        </td>
                    </tr>

                    <tr>
                        <td><strong>Department:</strong></td>
                        <td>
                            <h6><?= htmlspecialchars($user['department_name']); ?></h6>
                        </td>

                        <td><strong>Level:</strong></td>
                        <td>
                            <h6><?= htmlspecialchars($studentLevelName); ?></h6>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="col-md-2 text-center">
                <img src="../<?= htmlspecialchars($user['passport'] ?? 'default.png'); ?>"
                    style="width:100px;height:100px;border-radius:10px;object-fit:cover;">
            </div>

        </div>

        <hr>

        <?php if ($levelMismatch): ?>

            <!-- ===================== -->
            <!-- LEVEL MISMATCH WARNING -->
            <!-- ===================== -->
            <div class="card border-warning">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">Level Confirmation Required</h5>
                </div>

                <div class="card-body">

                    <div class="alert alert-warning">
                        Your student profile level and current semester registration level do not match.
                        You must confirm your correct current level before editing your course form.
                    </div>

                    <div class="alert alert-info">
                        This correction is restricted to the two level records already attached to your account.
                        After confirmation, both records will be updated to the selected level.
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Source</th>
                                    <th>Level</th>
                                    <th>Level ID</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr>
                                    <td>Student Profile</td>
                                    <td><?= htmlspecialchars($studentLevelName); ?></td>
                                    <td><?= htmlspecialchars($studentLevelId); ?></td>
                                </tr>

                                <tr>
                                    <td>Current Semester Registration</td>
                                    <td><?= htmlspecialchars($semesterRegLevelName); ?></td>
                                    <td>
                                        <?= $semesterRegLevelId ? htmlspecialchars($semesterRegLevelId) : 'Not Set'; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#levelConfirmModal">
                        Confirm Current Level
                    </button>

                </div>
            </div>

        <?php else: ?>

            <!-- ===================== -->
            <!-- COURSE FORM -->
            <!-- ===================== -->
            <div class="card">

                <div class="card-header d-flex justify-content-between align-items-center">

                    <h5 class="mb-0">Edit Course Registration</h5>

                    <div class="unit-box">
                        Units:
                        <strong id="totalUnits">0</strong> / 40
                    </div>

                    <small id="unitWarning" style="display:none;color:red;">
                        You have exceeded maximum allowed units
                    </small>
                </div>

                <div class="card-body">

                    <div class="alert alert-info">
                        Please review your registered courses carefully. All core courses are compulsory and cannot be removed.
                    </div>

                    <form method="POST" action="../api/student/updateCourseForm.php" id="editCourseForm">

                        <input type="hidden" name="csrf_token" value="<?= $utility->generateCsrf('editcourses'); ?>">

                        <!-- ===================== -->
                        <!-- DEPARTMENT COURSES -->
                        <!-- ===================== -->
                        <h6 class="section-title">Registered Courses</h6>

                        <div class="table-responsive mb-4">
                            <table class="table table-hover custom-table">

                                <thead>
                                    <tr>
                                        <th>Select</th>
                                        <th>Code</th>
                                        <th>Title</th>
                                        <th>Unit</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php if (!empty($deptCourses) && is_array($deptCourses)): ?>

                                        <?php foreach ($deptCourses as $course): ?>

                                            <?php
                                                $courseType = strtolower(trim($course['course_type'] ?? ''));
                                                $courseUnit = (int) ($course['unit'] ?? 0);
                                                $courseId   = (int) ($course['id'] ?? 0);
                                            ?>

                                            <tr>

                                                <td>
                                                    <?php if ($courseType === 'core'): ?>

                                                        <input type="checkbox" checked disabled>

                                                        <input type="hidden"
                                                            name="courses[]"
                                                            value="<?= htmlspecialchars($courseId); ?>"
                                                            class="core-unit"
                                                            data-unit="<?= htmlspecialchars($courseUnit); ?>">

                                                    <?php else: ?>

                                                        <input type="checkbox"
                                                            class="course-checkbox"
                                                            name="courses[]"
                                                            value="<?= htmlspecialchars($courseId); ?>"
                                                            data-unit="<?= htmlspecialchars($courseUnit); ?>"
                                                            <?= in_array($courseId, $selectedIds, true) ? 'checked' : ''; ?>>

                                                    <?php endif; ?>
                                                </td>

                                                <td>
                                                    <strong><?= htmlspecialchars($course['course_code']); ?></strong>
                                                </td>

                                                <td>
                                                    <h6><?= htmlspecialchars($course['course_title']); ?></h6>
                                                </td>

                                                <td>
                                                    <button type="button" class="btn btn-light">
                                                        <?= htmlspecialchars($courseUnit); ?>
                                                    </button>
                                                </td>

                                                <td>
                                                    <button type="button" class="btn <?= $courseType === 'core' ? 'btn-danger' : 'btn-success'; ?>">
                                                        <?= htmlspecialchars(ucfirst($courseType)); ?>
                                                    </button>
                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    <?php else: ?>

                                        <tr>
                                            <td colspan="5" class="text-center text-muted">
                                                <div class="py-4">
                                                    <i class="ph ph-book-open fs-2"></i><br><br>
                                                    No courses available for your confirmed level and semester.
                                                    <?= htmlspecialchars($confirmedLevelId . ' - ' . $semesterID); ?>
                                                </div>
                                            </td>
                                        </tr>

                                    <?php endif; ?>

                                </tbody>

                            </table>
                        </div>

                        <!-- ACTION -->
                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary px-4" id="submitBtn">
                                Submit Course Registration Form - No Correction
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        <?php endif; ?>

    </div>
</div>

<!-- ===================== -->
<!-- LEVEL CONFIRMATION MODAL -->
<!-- ===================== -->
<div class="modal fade" id="levelConfirmModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <form method="POST">

                <input type="hidden" name="confirm_level_action" value="confirm_level">
                <input type="hidden" name="level_csrf_token" value="<?= $utility->generateCsrf('edit_levelconfirmation'); ?>">

                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        Confirm Your Current Level
                    </h5>
                </div>

                <div class="modal-body">

                    <div class="alert alert-warning">
                        Your student profile level and semester registration level do not match.
                        Please select your correct current level for
                        <strong>
                            <?= htmlspecialchars($semester); ?> Semester,
                            <?= htmlspecialchars($session); ?> Academic Session
                        </strong>.
                    </div>

                    <div class="alert alert-danger">
                        Please choose carefully. Your course list will be loaded based on the level you confirm.
                    </div>

                    <div class="row">

                        <?php if (!empty($studentLevelId)): ?>

                            <div class="col-md-6 mb-3">
                                <label class="card h-100 p-3 border">
                                    <div class="form-check">
                                        <input class="form-check-input"
                                            type="radio"
                                            name="confirmed_level_id"
                                            value="<?= htmlspecialchars($studentLevelId); ?>"
                                            required>

                                        <span class="form-check-label">
                                            <strong>Student Profile Level</strong>
                                        </span>
                                    </div>

                                    <hr>

                                    <h5 class="mb-1">
                                        <?= htmlspecialchars($studentLevelName); ?>
                                    </h5>

                                    <small class="text-muted">
                                        Level ID: <?= htmlspecialchars($studentLevelId); ?>
                                    </small>
                                </label>
                            </div>

                        <?php endif; ?>

                        <?php if (!empty($semesterRegLevelId)): ?>

                            <div class="col-md-6 mb-3">
                                <label class="card h-100 p-3 border">
                                    <div class="form-check">
                                        <input class="form-check-input"
                                            type="radio"
                                            name="confirmed_level_id"
                                            value="<?= htmlspecialchars($semesterRegLevelId); ?>"
                                            required>

                                        <span class="form-check-label">
                                            <strong>Semester Registration Level</strong>
                                        </span>
                                    </div>

                                    <hr>

                                    <h5 class="mb-1">
                                        <?= htmlspecialchars($semesterRegLevelName); ?>
                                    </h5>

                                    <small class="text-muted">
                                        Level ID: <?= htmlspecialchars($semesterRegLevelId); ?>
                                    </small>
                                </label>
                            </div>

                        <?php else: ?>

                            <div class="col-md-6 mb-3">
                                <div class="card h-100 p-3 border bg-light">
                                    <strong>Semester Registration Level</strong>
                                    <hr>
                                    <h5 class="mb-1 text-danger">Not Set</h5>

                                    <small class="text-muted">
                                        Your semester registration level is empty. Confirming the student profile level will update it.
                                    </small>
                                </div>
                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="alert alert-info mb-0">
                        After confirmation, both your student profile and current semester registration record will be updated with the selected level.
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">
                        Confirm and Continue
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<script>
    let maxUnits = 40;

    function calcUnits() {
        let total = 0;

        document.querySelectorAll('.core-unit').forEach(el => {
            total += parseInt(el.dataset.unit) || 0;
        });

        document.querySelectorAll('.course-checkbox:checked').forEach(el => {
            total += parseInt(el.dataset.unit) || 0;
        });

        const totalUnits = document.getElementById('totalUnits');
        const unitWarning = document.getElementById('unitWarning');
        const submitBtn = document.getElementById('submitBtn');

        if (totalUnits) {
            totalUnits.innerText = total;
        }

        if (total > maxUnits) {
            if (totalUnits) {
                totalUnits.style.color = 'red';
            }

            if (unitWarning) {
                unitWarning.style.display = 'block';
            }

            if (submitBtn) {
                submitBtn.disabled = true;
            }
        } else {
            if (totalUnits) {
                totalUnits.style.color = 'green';
            }

            if (unitWarning) {
                unitWarning.style.display = 'none';
            }

            if (submitBtn) {
                submitBtn.disabled = false;
            }
        }
    }

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('course-checkbox')) {
            calcUnits();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        calcUnits();

        <?php if ($levelMismatch): ?>
            const levelModalEl = document.getElementById('levelConfirmModal');

            if (levelModalEl) {
                new bootstrap.Modal(levelModalEl).show();
            }
        <?php endif; ?>
    });
</script>