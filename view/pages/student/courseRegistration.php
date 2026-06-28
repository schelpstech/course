<?php

$currentPage = $_SESSION['pageid'] ?? 'courseRegistration';

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
        'students.student_id' => $_SESSION['user_id']
    ],
    'return_type' => 'single'
]);

if (empty($user)) {
    redirectWithToast('error', 'Student record not found.', 'studentDashboard');
    exit;
}

$studentId    = $_SESSION['user_id'];
$departmentId = $user['department_id'];
$programmeId  = $user['programme_id'];
$studentLevelId = (int) $user['level_id'];

$semester     = $activeSemester['name'];
$semesterID   = (int) $activeSemester['id'];
$session      = $activeSession['name'];
$sessionID    = (int) $activeSession['id'];

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

$semesterRegLevelId = !empty($semesterRegistration['studentLevelId'])
    ? (int) $semesterRegistration['studentLevelId']
    : 0;

/* ===================== */
/* FETCH LEVEL NAMES */
/* ===================== */

$studentLevel = $model->getRows('levels', [
    'where' => [
        'id' => $studentLevelId
    ],
    'return_type' => 'single'
]);

$semesterRegLevel = null;

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
/* HANDLE LEVEL CONFIRMATION */
/* ===================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['confirm_level_action'])
    && $_POST['confirm_level_action'] === 'confirm_level'
) {
    if (!$utility->validateRequest($_POST['level_csrf_token'] ?? '', 'levelconfirmation')) {
        redirectWithToast('error', 'Unauthorized level confirmation request.', $currentPage);
        exit;
    }

    if (empty($semesterRegistration)) {
        redirectWithToast('error', 'Semester registration record not found.', $currentPage);
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
            Update semesterregistration table for the active session and semester only
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
            'Student confirmed current semester level. Student ID: ' . $studentId . ', Level ID: ' . $selectedLevelId,
            $_SESSION['user_email'] ?? 'Unknown'
        );

        redirectWithToast(
            'success',
            'Your current level has been confirmed successfully. You can now register your courses.',
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

$hasSemesterRegistration = !empty($semesterRegistration);

$levelMismatch = false;

if ($hasSemesterRegistration) {
    if (empty($semesterRegLevelId) || $studentLevelId !== $semesterRegLevelId) {
        $levelMismatch = true;
    }
}

/*
    Registration is allowed only when:
    1. semesterregistration exists
    2. students.level_id and semesterregistration.studentLevelId match
*/
$canRegisterCourses = $hasSemesterRegistration && !$levelMismatch;

/*
    Use the student level when everything is okay.
    After confirmation, both tables will have the same level.
*/
$levelId = $studentLevelId;

/* ===================== */
/* FETCH COURSES */
/* ===================== */

$deptCourses = [];

if ($canRegisterCourses) {
    $deptCourses = $model->getRows('courses', [
        'where' => [
            'level_id'      => $levelId,
            'semester_id'   => $semesterID,
            'course_status' => 1
        ]
    ]);
}

/* ===================== */
/* FETCH EXISTING COURSE REGISTRATION */
/* ===================== */

$reg = $model->getRows('course_registered', [
    'where' => [
        'student_id' => $studentId,
        'semester'   => $semesterID,
        'session'    => $sessionID
    ],
    'return_type' => 'single'
]);

if ($reg) {
    redirectWithToast('error', 'Course Registration for this Semester found. Modify and Submit here', 'editCourseRegistration');
    exit;
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
    <div class="col-xl-10 col-md-12 mx-auto">

        <div class="card">

            <div class="card-body">

                <!-- ===================== -->
                <!-- INSTITUTION HEADER -->
                <!-- ===================== -->
                <div class="row mb-4 align-items-center course-form-heading">
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

                    <div class="col-md-2 text-center">
                        <img src="../<?= htmlspecialchars($user['passport'] ?? 'default.png'); ?>"
                            style="width:80px;height:80px;border-radius:10px;object-fit:cover;">
                    </div>

                    <div class="col-md-10">
                        <table class="table table-sm table-border mb-0">
                            <tr>
                                <td><strong>Name:</strong></td>
                                <td>
                                    <strong>
                                        <?= htmlspecialchars(trim($user['first_name'] . ' ' . $user['other_name'] . ' ' . $user['last_name'])); ?>
                                    </strong>
                                </td>

                                <td><strong>Matric:</strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($user['matric_no']); ?></strong>
                                </td>
                            </tr>

                            <tr>
                                <td><strong>Department:</strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($user['department_name']); ?></strong>
                                </td>

                                <td><strong>Level:</strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($studentLevelName); ?></strong>
                                </td>
                            </tr>
                        </table>
                    </div>

                </div>

                <hr>

                <?php if (!$hasSemesterRegistration): ?>

                    <div class="alert alert-danger">
                        <strong>Semester registration record not found.</strong><br>
                        You cannot register courses because your semester registration record for
                        <?= htmlspecialchars($semester); ?> Semester,
                        <?= htmlspecialchars($session); ?> Academic Session was not found.
                        Please contact the Registry.
                    </div>

                <?php elseif ($levelMismatch): ?>

                    <div class="alert alert-warning">
                        <strong>Level confirmation required.</strong><br>
                        We noticed that your level on your student profile is different from your level on the current semester registration record.
                        Please confirm your correct current level before registering courses.
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
                                    <td><?= $semesterRegLevelId ? htmlspecialchars($semesterRegLevelId) : 'Not Set'; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#levelConfirmModal">
                        Confirm Current Level
                    </button>

                <?php else: ?>

                    <form method="POST" action="../api/student/registerCourses.php" id="courseForm">

                        <input type="hidden" name="semester" value="<?= htmlspecialchars($semester); ?>">
                        <input type="hidden" name="session" value="<?= htmlspecialchars($sessionID); ?>">
                        <input type="hidden" name="csrf_token" value="<?= $utility->generateCsrf('courseformpayment'); ?>">

                        <!-- ===================== -->
                        <!-- DEPARTMENT COURSES -->
                        <!-- ===================== -->
                        <h5>Department Courses</h5>

                        <div class="table-responsive mb-4">
                            <table class="table table-hover">

                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Select</th>
                                        <th>Code</th>
                                        <th>Title</th>
                                        <th>Unit</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php if (!empty($deptCourses) && is_array($deptCourses)): ?>

                                        <?php $i = 1; ?>

                                        <?php foreach ($deptCourses as $course): ?>

                                            <?php
                                                $courseType = strtolower(trim($course['course_type'] ?? ''));
                                                $courseUnit = (int) ($course['unit'] ?? 0);
                                            ?>

                                            <tr>

                                                <td><?= $i++; ?></td>

                                                <td>
                                                    <?php if ($courseType === 'core'): ?>

                                                        <input type="checkbox" checked disabled>

                                                        <input type="hidden"
                                                            name="courses[]"
                                                            value="<?= htmlspecialchars($course['id']); ?>"
                                                            class="core-unit"
                                                            data-unit="<?= htmlspecialchars($courseUnit); ?>">

                                                    <?php else: ?>

                                                        <input type="checkbox"
                                                            class="course-checkbox"
                                                            name="courses[]"
                                                            value="<?= htmlspecialchars($course['id']); ?>"
                                                            data-unit="<?= htmlspecialchars($courseUnit); ?>">

                                                    <?php endif; ?>
                                                </td>

                                                <td><?= htmlspecialchars($course['course_code']); ?></td>

                                                <td><?= htmlspecialchars($course['course_title']); ?></td>

                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?= htmlspecialchars($courseUnit); ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <span class="badge <?= $courseType === 'core' ? 'bg-success' : 'bg-warning'; ?>">
                                                        <?= htmlspecialchars(ucfirst($courseType)); ?>
                                                    </span>
                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    <?php else: ?>

                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                <div class="py-4">
                                                    <i class="ph ph-book-open fs-2"></i><br><br>

                                                    <?php if (empty($levelId) || empty($semesterID)): ?>

                                                        Missing level or semester configuration.
                                                        <?= htmlspecialchars($levelId . ' - ' . $semesterID); ?>

                                                    <?php else: ?>

                                                        No courses available for your level and semester.
                                                        <?= htmlspecialchars($levelId . ' - ' . $semesterID); ?>

                                                    <?php endif; ?>

                                                </div>
                                            </td>
                                        </tr>

                                    <?php endif; ?>

                                </tbody>
                            </table>
                        </div>

                        <!-- ===================== -->
                        <!-- SUMMARY -->
                        <!-- ===================== -->
                        <div class="row mt-4">

                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    Units: <strong id="selectedUnits">0</strong> / 40
                                </div>
                            </div>

                            <div class="col-md-6 text-end">
                                <button type="button" class="btn btn-warning" id="previewBtn">
                                    Preview Registration
                                </button>
                            </div>

                        </div>

                    </form>

                <?php endif; ?>

            </div>
        </div>
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
                <input type="hidden" name="level_csrf_token" value="<?= $utility->generateCsrf('levelconfirmation'); ?>">

                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        Confirm Your Current Level
                    </h5>
                </div>

                <div class="modal-body">

                    <div class="alert alert-warning">
                        Your student profile level and semester registration level do not match.
                        Please select your correct current level for
                        <strong><?= htmlspecialchars($semester); ?> Semester, <?= htmlspecialchars($session); ?> Academic Session</strong>.
                    </div>

                    <div class="row">

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
                                        Your semester registration level is empty. Confirming will update it from your student profile level.
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

<!-- ===================== -->
<!-- PREVIEW MODAL -->
<!-- ===================== -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <!-- ===================== -->
            <!-- INSTITUTION HEADER -->
            <!-- ===================== -->
            <div class="row mb-4 align-items-center p-3">
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

            <div class="modal-header">
                <h5>Preview</h5>
            </div>

            <div class="modal-body">
                <div id="previewContent"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="submit" form="courseForm" class="btn btn-success">
                    Save Course Registration Form
                </button>
            </div>

        </div>
    </div>
</div>

<!-- ===================== -->
<!-- JS -->
<!-- ===================== -->
<script>
    let maxUnits = 40;

    function calculateUnits() {
        let total = 0;

        document.querySelectorAll('.core-unit').forEach(el => {
            total += parseInt(el.dataset.unit) || 0;
        });

        document.querySelectorAll('.course-checkbox:checked').forEach(el => {
            total += parseInt(el.dataset.unit) || 0;
        });

        const selectedUnits = document.getElementById('selectedUnits');
        const previewBtn = document.getElementById('previewBtn');

        if (selectedUnits) {
            selectedUnits.innerText = total;
        }

        if (previewBtn) {
            previewBtn.disabled = total > maxUnits;
        }
    }

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('course-checkbox')) {
            calculateUnits();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        calculateUnits();

        <?php if ($levelMismatch): ?>
            const levelModalEl = document.getElementById('levelConfirmModal');
            if (levelModalEl) {
                new bootstrap.Modal(levelModalEl).show();
            }
        <?php endif; ?>
    });

    /* PREVIEW */
    const previewBtn = document.getElementById('previewBtn');

    if (previewBtn) {
        previewBtn.addEventListener('click', function() {

            let html = '<table class="table table-bordered">';
            html += '<tr><th>Code</th><th>Title</th><th>Unit</th></tr>';

            let total = 0;

            document.querySelectorAll('.core-unit').forEach(el => {
                let row = el.closest('tr');
                let code = row.children[2].innerText;
                let title = row.children[3].innerText;
                let unit = parseInt(el.dataset.unit) || 0;

                total += unit;

                html += `<tr><td>${code}</td><td>${title} (Core)</td><td>${unit}</td></tr>`;
            });

            document.querySelectorAll('.course-checkbox:checked').forEach(el => {
                let row = el.closest('tr');
                let code = row.children[2].innerText;
                let title = row.children[3].innerText;
                let unit = parseInt(el.dataset.unit) || 0;

                total += unit;

                html += `<tr><td>${code}</td><td>${title} (Elective)</td><td>${unit}</td></tr>`;
            });

            html += `<tr><th colspan="2">Total</th><th>${total}</th></tr>`;
            html += '</table>';

            document.getElementById('previewContent').innerHTML = html;

            new bootstrap.Modal(document.getElementById('previewModal')).show();
        });
    }
</script>