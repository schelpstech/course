<?php

$currentPage = $_SESSION['pageid'] ?? 'studentCourseForm';

$studentId  = $_SESSION['user_id'];
$semesterID = (int) $activeSemester['id'];
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
/* FETCH COURSE REGISTRATION */
/* ===================== */

$reg = $model->getRows('course_registered', [
    'where' => [
        'student_id' => $studentId,
        'semester'   => $semesterID,
        'session'    => $sessionID
    ],
    'return_type' => 'single'
]);

if (empty($reg)) {
    redirectWithToast(
        'error',
        'Course Registration Form Not Submitted or Approved for this Semester Yet. Submit Here',
        'editCourseRegistration'
    );
    exit;
}

$approvalStatusRaw = trim($reg['approval_status'] ?? '');
$approvalStatus    = strtolower($approvalStatusRaw);

$allowedDisplayStatuses = ['pending', 'approved'];

if (!in_array($approvalStatus, $allowedDisplayStatuses, true)) {
    redirectWithToast(
        'error',
        'Course Registration Form Not Submitted or Approved for this Semester Yet. Submit Here',
        'editCourseRegistration'
    );
    exit;
}

/* ===================== */
/* CHECK LEVEL MISMATCH */
/* ===================== */

$levelMismatch = false;

if (empty($studentLevelId) || empty($semesterRegLevelId) || $studentLevelId !== $semesterRegLevelId) {
    $levelMismatch = true;
}

/* ===================== */
/* HANDLE LEVEL CONFIRMATION */
/* ===================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['confirm_level_action'])
    && $_POST['confirm_level_action'] === 'confirm_level'
) {
    if (!$utility->validateRequest($_POST['level_csrf_token'] ?? '', 'print_courseform_levelconfirmation')) {
        redirectWithToast('error', 'Unauthorized level confirmation request.', $currentPage);
        exit;
    }

    if (!$levelMismatch) {
        redirectWithToast('success', 'Your level is already confirmed.', $currentPage);
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

        /* ===================== */
        /* UPDATE STUDENT LEVEL */
        /* ===================== */

        $model->update(
            'students',
            [
                'level_id' => $selectedLevelId
            ],
            [
                'student_id' => $studentId
            ]
        );

        /* ===================== */
        /* UPDATE SEMESTER REGISTRATION LEVEL */
        /* ===================== */

        $model->update(
            'semesterregistration',
            [
                'studentLevelId' => $selectedLevelId
            ],
            [
                'student_id'  => $studentId,
                'semester_id' => $semesterID,
                'session_id'  => $sessionID
            ]
        );

        /*
            Important:
            If the course form is currently pending review and level correction happens,
            move it back to submitted so the student can edit the form again.
        */
        if ($approvalStatus === 'pending') {
            $model->update(
                'course_registered',
                [
                    'approval_status' => 'submitted'
                ],
                [
                    'course_regID' => $reg['course_regID']
                ]
            );
        }

        $model->commit();

        $utility->logActivityUsers(
            'Student corrected level mismatch from course form display page. Student ID: ' . $studentId . ', Level ID: ' . $selectedLevelId,
            $_SESSION['user_email'] ?? 'Unknown'
        );

        if ($approvalStatus === 'pending') {
            redirectWithToast(
                'success',
                'Your level has been corrected. Your course form has been moved back for editing. Please review and submit again.',
                'editCourseRegistration'
            );
            exit;
        }

        redirectWithToast(
            'success',
            'Your level has been corrected successfully.',
            $currentPage
        );
        exit;
    } catch (Exception $e) {

        $model->rollBack();

        redirectWithToast(
            'error',
            'Level correction failed. Reason: ' . $e->getMessage(),
            $currentPage
        );
        exit;
    }
}

/* ===================== */
/* IF LEVEL MISMATCH, BLOCK COURSE FORM DISPLAY */
/* ===================== */

if ($levelMismatch) {
?>

    <!-- ALERTS -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success'];
            unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error'];
            unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-xl-8 col-md-12 mx-auto">

            <div class="card border-warning">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">Level Confirmation Required</h5>
                </div>

                <div class="card-body">

                    <div class="alert alert-warning">
                        Your student profile level and current semester registration level do not match.
                        You must confirm your correct current level before this course form can be displayed or printed.
                    </div>

                    <?php if ($approvalStatus === 'pending'): ?>
                        <div class="alert alert-info">
                            After correction, your course form will be moved from
                            <strong>Pending</strong> back to <strong>Submitted</strong>,
                            so you can review and edit your courses again.
                        </div>
                    <?php elseif ($approvalStatus === 'approved'): ?>
                        <div class="alert alert-danger">
                            This form is already approved. Your level can be corrected, but the form will not be reopened automatically for editing.
                            Please contact the Registry if the approved course form also needs correction.
                        </div>
                    <?php endif; ?>

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
                    <input type="hidden" name="level_csrf_token" value="<?= $utility->generateCsrf('print_courseform_levelconfirmation'); ?>">

                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">
                            Confirm Your Current Level
                        </h5>
                    </div>

                    <div class="modal-body">

                        <div class="alert alert-warning">
                            Please select your correct current level for
                            <strong>
                                <?= htmlspecialchars($activeSemester['name']); ?> Semester,
                                <?= htmlspecialchars($activeSession['name']); ?> Academic Session
                            </strong>.
                        </div>

                        <div class="alert alert-danger">
                            Your course form will be affected by the level you confirm. Choose carefully.
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

                        <?php if ($approvalStatus === 'pending'): ?>
                            <div class="alert alert-info mb-0">
                                After confirmation, your course form status will become
                                <strong>Submitted</strong>, allowing you to edit and submit it again.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
                                After confirmation, both your student profile and current semester registration record will be updated.
                            </div>
                        <?php endif; ?>

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
        document.addEventListener('DOMContentLoaded', function() {
            const levelModalEl = document.getElementById('levelConfirmModal');

            if (levelModalEl) {
                new bootstrap.Modal(levelModalEl).show();
            }
        });
    </script>

<?php
    return;
}

/* ===================== */
/* FETCH REGISTERED COURSES */
/* ===================== */

$courses = $model->getRows(
    'registered_course',
    [
        'join' => [
            'courses' => ' on registered_course.course_id = courses.id'
        ],
        'where' => [
            'registered_course.course_regID' => $reg['courseRegID'] ?? $reg['course_regID']
        ]
    ]
);

/* ===================== */
/* GROUP COURSES */
/* ===================== */

$deptCourses = [];

if (!empty($courses) && is_array($courses)) {
    foreach ($courses as $c) {
        $deptCourses[] = $c;
    }
}

/* ===================== */
/* QR CODE */
/* ===================== */

$matric   = $user['matric_no'];
$semester = $activeSemester['id'];

// Build payload
$raw = $matric . '|' . $semester . '|' . 'course_form';

// Sign with APP_KEY
$signature = hash_hmac('sha256', $raw, APP_KEY);

// Final token
$token = rtrim(strtr(base64_encode($raw . '|' . $signature), '+/', '-_'), '=');

// URL
$verifyUrl = 'https://owutech-edu.org/verifier.php?token=' . urlencode($token);

// Generate QR
$qrSrc = $qrcode->generateQRCode($verifyUrl);

$qrPath = '<img src="' . htmlspecialchars($qrSrc) . '" class="qr-code" style="max-width:260px;">';

$totalUnits = (int) ($reg['total_units'] ?? 0);

?>

<!-- ALERTS -->
<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success no-print">
        <?= $_SESSION['success'];
        unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger no-print">
        <?= $_SESSION['error'];
        unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<div class="print-container">

    <!-- WATERMARK -->
    <div class="watermark"></div>

    <!-- HEADER -->
    <div class="header-section">
        <div class="header-left">
            <img src="../uploads/logo/<?= htmlspecialchars($institution['inst_logo'] ?? 'default.png'); ?>" class="inst-logo">
        </div>

        <div class="header-center" style="text-align: center;">
            <h2 class="inst-name" style="text-align: center;">
                <?= strtoupper(htmlspecialchars($institution['name'] ?? 'Institution Name')); ?>
            </h2>

            <p class="inst-subtitle" style="text-align: center;">
                OFFICE OF THE REGISTRAR
            </p>

            <h3 class="form-title" style="text-align: center;">
                COURSE REGISTRATION FORM
            </h3>

            <p class="session-title" style="text-align: center;">
                <?= strtoupper(htmlspecialchars($activeSemester['name'])); ?> SEMESTER •
                <?= strtoupper(htmlspecialchars($activeSession['name'])); ?> SESSION
            </p>
        </div>

        <div class="header-right">
            <?= $qrPath ?>
            <p class="qr-caption">Scan to Verify</p>
        </div>
    </div>

    <!-- STUDENT INFO -->
    <table class="table table-bordered student-info">
        <tr>
            <td>
                <strong>Name:</strong>
                <h5 class="mb-0">
                    <?= htmlspecialchars(trim($user['first_name'] . ' ' . ($user['other_name'] ?? '') . ' ' . $user['last_name'])); ?>
                </h5>
            </td>

            <td>
                <strong>Matric No:</strong>
                <h5 class="mb-0">
                    <?= htmlspecialchars($user['matric_no']); ?>
                </h5>
            </td>

            <td rowspan="2" class="passport-cell">
                <img src="../<?= htmlspecialchars($user['passport'] ?? 'default.png'); ?>" class="passport">
            </td>
        </tr>

        <tr>
            <td>
                <strong>Department:</strong>
                <h5 class="mb-0">
                    <?= htmlspecialchars($user['department_name']); ?>
                </h5>
            </td>

            <td>
                <strong>Level:</strong>
                <h5 class="mb-0">
                    <?= htmlspecialchars($studentLevelName); ?>
                </h5>
            </td>
        </tr>
    </table>

    <!-- STATUS -->
    <div class="status-box">
        <strong>Status:</strong>
        <?= htmlspecialchars(ucfirst($approvalStatus)); ?>
    </div>

    <!-- DEPARTMENT COURSES -->
    <h4 class="section-title">Registered Courses</h4>

    <table class="table table-bordered course-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Course Title</th>
                <th>Unit</th>
                <th>Type</th>
                <th>Lecturer Signature</th>
            </tr>
        </thead>

        <tbody>
            <?php if (!empty($deptCourses)): ?>

                <?php foreach ($deptCourses as $c): ?>

                    <?php
                    $courseType = strtolower(trim($c['course_type'] ?? ''));
                    ?>

                    <tr>
                        <td><?= htmlspecialchars($c['course_code']); ?></td>
                        <td><?= htmlspecialchars($c['course_title']); ?></td>
                        <td><?= htmlspecialchars($c['unit']); ?></td>
                        <td><?= htmlspecialchars(ucfirst($courseType)); ?></td>
                        <td class="sign-cell"></td>
                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="5" class="text-center">
                        No registered courses found.
                    </td>
                </tr>

            <?php endif; ?>
        </tbody>
    </table>

    <!-- TOTAL UNITS -->
    <p class="total-units">
        Total Units: <strong><?= htmlspecialchars($totalUnits); ?></strong>
    </p>

    <!-- SIGNATURE SECTION -->
    <table class="table table-bordered signature-table">
        <tr>
            <td class="sig-box">
                <div>Student Signature</div>
                <div class="line"></div>
                <div>Date: ____________________</div>
            </td>

            <td class="sig-box">
                <div>Head of Department</div>
                <div class="line"></div>
                <div>Date: ____________________</div>
            </td>

            <td class="sig-box">
                <div>Faculty Officer</div>
                <div class="line"></div>
                <div>Date: ____________________</div>
            </td>

            <td class="sig-box">
                <div>Registry / Directorate Officer</div>
                <div class="line"></div>
                <div>Date: ____________________</div>
            </td>
        </tr>
    </table>

</div>

<!-- PRINT BUTTONS -->
<div class="text-center mt-4 no-print">
    <button onclick="window.print()" class="btn btn-primary">
        Print
    </button>

    <a href="#" class="btn btn-success">
        Download PDF
    </a>
</div>

<style>
    /* GENERAL */
    body {
        font-family: "Times New Roman", serif;
    }

    .print-container {
        position: relative;
        padding: 20px;
    }

    /* WATERMARK */
    .watermark {
        background: url('../<?= htmlspecialchars($institution['inst_logo'] ?? 'default.png'); ?>') no-repeat center;
        background-size: 350px;
        opacity: 0.06;
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 0;
    }

    /* HEADER */
    .header-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .inst-logo {
        width: 90px;
    }

    .inst-name {
        font-size: 22px;
        font-weight: bold;
        margin: 0;
    }

    .inst-subtitle {
        font-size: 12px;
        margin: 0;
        letter-spacing: 1px;
    }

    .form-title {
        font-size: 18px;
        margin: 5px 0;
        font-weight: bold;
    }

    .session-title {
        font-size: 13px;
        margin: 0;
    }

    .qr-code {
        width: 120px;
    }

    .qr-caption {
        font-size: 10px;
        text-align: center;
    }

    .status-box {
        text-align: right;
        font-size: 14px;
        margin-bottom: 10px;
    }

    /* TABLES */
    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .table th,
    .table td {
        border: 1px solid #000;
        padding: 6px;
    }

    .section-title {
        margin-top: 25px;
        margin-bottom: 8px;
        font-size: 16px;
        font-weight: bold;
        border-bottom: 1px solid #000;
        padding-bottom: 3px;
    }

    .passport {
        width: 120px;
        height: 120px;
        border: 1px solid #000;
        object-fit: cover;
    }

    .sign-cell {
        height: 35px;
    }

    .total-units {
        text-align: right;
        font-size: 15px;
        margin-top: 10px;
    }

    /* SIGNATURE BOXES */
    .sig-box {
        height: 70px;
        text-align: center;
        vertical-align: bottom;
        padding-bottom: 10px;
    }

    .signature-table td {
        height: 120px;
        vertical-align: bottom;
        padding: 10px;
    }

    .sig-box div:first-child {
        font-weight: bold;
        margin-bottom: 20px;
    }

    .line {
        border-bottom: 1px solid #000;
        height: 30px;
        margin-bottom: 10px;
    }

    /* PRINT */
    @media print {
        @page {
            size: A4;
            margin: 15mm;
        }

        body * {
            visibility: hidden;
        }

        .print-container,
        .print-container * {
            visibility: visible;
        }

        .print-container {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }

        .no-print {
            display: none !important;
        }
    }
</style>