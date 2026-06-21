<?php

$studentId = $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| STUDENT INFORMATION
|--------------------------------------------------------------------------
*/

$user = $model->getRows('students', [
    'select' => '
        students.*,
        department.name as department_name,
        levels.name as level_name,
        programmes.name as programme_name,
        institutions.name as institution_name
    ',
    'join' => [
        'levels'       => ' ON students.level_id = levels.id',
        'programmes'   => ' ON programmes.id = students.programme_id',
        'department'   => ' ON department.id = students.department_id',
        'institutions' => ' ON institutions.id = students.institution_id'
    ],
    'where' => [
        'students.student_id' => $studentId
    ],
    'return_type' => 'single'
]);

if (!$user) {
    redirectWithToast('error', 'Student record not found.', 'dashboard');
    exit;
}

/*
|--------------------------------------------------------------------------
| SEMESTER REGISTRATION
|--------------------------------------------------------------------------
*/

$semesterReg = $model->getRows('semesterregistration', [
    'where' => [
        'student_id'  => $studentId,
        'semester_id' => $activeSemester['id']
    ],
    'return_type' => 'single'
]);

if (!$semesterReg) {
    redirectWithToast('error', 'Semester registration record not found.', 'dashboard');
    exit;
}

$semesterRegistrationId = $semesterReg['id'];

/*
|--------------------------------------------------------------------------
| FETCH CLEARANCES
|--------------------------------------------------------------------------
*/

$clearances = $model->query("
    SELECT
        ct.code,
        sc.clearance_status
    FROM student_clearances sc
    INNER JOIN clearance_types ct
        ON ct.id = sc.clearance_type_id
    WHERE sc.semester_registration_id = '{$semesterRegistrationId}'
");

$clearances = is_array($clearances) ? $clearances : [];

$courseClearance  = 'pending';
$paymentClearance = 'pending';

foreach ($clearances as $clr) {
    if ($clr['code'] === 'COURSE REGISTRATION') {
        $courseClearance = $clr['clearance_status'];
    }

    if ($clr['code'] === 'PAYMENT') {
        $paymentClearance = $clr['clearance_status'];
    }
}

/*
|--------------------------------------------------------------------------
| ELIGIBILITY CHECK
|--------------------------------------------------------------------------
*/

if ($courseClearance !== 'approved' || $paymentClearance !== 'approved') {
    redirectWithToast('error', 'You have not completed all required clearances.', 'dashboard');
    exit;
}

/*
|--------------------------------------------------------------------------
| COURSE REGISTRATION
|--------------------------------------------------------------------------
*/

$reg = $model->getRows('course_registered', [
    'where' => [
        'student_id' => $studentId,
        'semester'   => $activeSemester['id'],
        'session'    => $activeSession['id']
    ],
    'return_type' => 'single'
]);

if (!$reg) {
    redirectWithToast('error', 'Course registration not found.', 'dashboard');
    exit;
}

/*
|--------------------------------------------------------------------------
| COURSES
|--------------------------------------------------------------------------
*/

$courses = $model->getRows('registered_course', [
    'join' => [
        'courses' => ' ON registered_course.course_id = courses.id'
    ],
    'where' => [
        'registered_course.course_regID' => $reg['course_regID']
    ]
]);

$courses = is_array($courses) ? $courses : [];

/*
|--------------------------------------------------------------------------
| QR TOKEN
|--------------------------------------------------------------------------
*/

$matric = $user['matric_no'];

$raw = $matric . '|' . $activeSemester['id'] . '|exam_clearance';

$signature = hash_hmac('sha256', $raw, APP_KEY);

$token = rtrim(
    strtr(base64_encode($raw . '|' . $signature), '+/', '-_'),
    '='
);

$verifyUrl = BASE_URL . '/verifier.php?token=' . $token;

$qrSrc = $qrcode->generateQRCode($verifyUrl);

/*
|--------------------------------------------------------------------------
| PASSPORT
|--------------------------------------------------------------------------
*/

$passport = !empty($user['passport'])
    ? '../' . $user['passport']
    : '../assets/images/default-user.png';

?>

<div class="print-container">

    <!-- WATERMARK -->
    <div class="watermark">
        <?php for ($y = 0; $y < 1600; $y += 180): ?>
            <?php for ($x = -300; $x < 1400; $x += 400): ?>
                <span style="top:<?= $y ?>px; left:<?= $x ?>px;">
                    <?= strtoupper($activeSemester['name']) ?> SEMESTER EXAM CLEARANCE
                </span>
            <?php endfor; ?>
        <?php endfor; ?>
    </div>

    <!-- HEADER -->
    <div class="header-section">

        <div class="header-left" style="width:120px;">
            <img src="../uploads/logo/<?= $institution['inst_logo'] ?>" class="inst-logo">
        </div>

        <div class="header-center">
            <h3><?= strtoupper($institution['name']) ?></h3>
            <h5>OFFICE OF THE REGISTRAR</h5>
            <h3>EXAMINATION CLEARANCE SLIP</h3>
            <p>
                <?= strtoupper($activeSemester['name']) ?> SEMESTER •
                <?= strtoupper($activeSession['name']) ?> SESSION
            </p>
        </div>

        <div class="header-right">
            <img src="<?= $qrSrc ?>" class="qr-code"><br>
            <small>Scan To Verify</small><br>
            <small class="mb-0">
                <strong>Verification No:</strong>
                EXM-<?= date('Y') ?>-<?= $semesterRegistrationId ?>
            </small><br>
            <small class="mb-0">
                <strong>Printed:</strong>
                <?= date('d M Y h:i A') ?>
            </small>
        </div>

    </div>

    <!-- STUDENT INFO -->
    <table class="table table-bordered">
        <tr>
            <td>
                <strong>Name</strong><br>
                <?= $user['first_name'] ?> <?= $user['other_name'] ?> <?= $user['last_name'] ?>
            </td>
            <td>
                <strong>Matric No</strong><br>
                <?= $user['matric_no'] ?>
            </td>
            <td rowspan="3">
                <img src="../<?= $passport ?>" class="passport">
            </td>
        </tr>

        <tr>
            <td>
                <strong>Programme</strong><br>
                <?= $user['programme_name'] ?>
            </td>
            <td>
                <strong>Department</strong><br>
                <?= $user['department_name'] ?>
            </td>
        </tr>

        <tr>
            <td>
                <strong>Level</strong><br>
                <?= $user['level_name'] ?>
            </td>
            <td>
                <strong>Status</strong><br>
                <span class="badge bg-success">ELIGIBLE FOR EXAMINATION</span>
            </td>
        </tr>
    </table>

    <!-- CLEARANCE STATUS -->
    <table class="table table-bordered mb-4">
        <tr>
            <td class="text-center">
                <strong>Course Registration Clearance</strong><br>
                <span class="text-success">CLEARED</span>
            </td>
            <td class="text-center">
                <strong>Payment Clearance</strong><br>
                <span class="text-success">CLEARED</span>
            </td>
            <td class="text-center">
                <strong>Examination Status</strong><br>
                <span class="text-success">APPROVED</span>
            </td>
        </tr>
    </table>

    <!-- COURSES -->
    <h5 class="section-title">Registered Courses</h5>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>S/N</th>
                <th>Course Code</th>
                <th>Course Title</th>
                <th>Unit</th>
                <th>Exam Venue</th>
                <th>Invigilator Signature</th>
                <th>Date</th>
            </tr>
        </thead>

        <tbody>
            <?php
            $sn = 1;
            $totalUnits = 0;

            foreach ($courses as $course):
                $totalUnits += (int)$course['unit'];
            ?>
                <tr>
                    <td><?= $sn++ ?></td>
                    <td><?= $course['course_code'] ?></td>
                    <td><?= $course['course_title'] ?></td>
                    <td><?= $course['unit'] ?></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="text-end mb-4">
        <strong>Total Registered Units: <?= $totalUnits ?></strong>
    </div>

    <!-- SIGNATURE -->
    <div class="student-signature-section">
        <table class="table table-bordered">
            <tr>
                <td width="50%">
                    <strong>Student Signature</strong>
                    <div class="line-lg"></div>
                </td>
                <td width="50%">
                    <strong>Date</strong>
                    <div class="line-lg"></div>
                </td>
            </tr>
        </table>
    </div>

    <h5 class="section-title">Clearance Endorsements</h5>

    <table class="table table-bordered endorsement-table">
        <tr>
            <td class="endorsement-box">
                <strong>Head of Department (HoD)</strong>
                <div class="line"></div>
                Signature & Date
            </td>
            <td class="endorsement-box">
                <strong>Dean, Student Affairs</strong>
                <div class="line"></div>
                Signature & Date
            </td>
            <td class="endorsement-box">
                <strong>Library Department</strong>
                <div class="line"></div>
                Signature & Date
            </td>
        </tr>

        <tr>
            <td class="endorsement-box">
                <strong>Vocational Directorate</strong>
                <div class="line"></div>
                Signature & Date
            </td>
            <td class="endorsement-box">
                <strong>ICT Directorate</strong>
                <div class="line"></div>
                Signature & Date
            </td>
            <td class="endorsement-box">
                <strong>Sports Directorate</strong>
                <div class="line"></div>
                Signature & Date
            </td>
        </tr>

        <tr>
            <td colspan="3" class="registrar-box">
                <strong>Registrar's Office</strong>
                <div class="line-lg"></div>
                Official Signature, Stamp & Date
            </td>
        </tr>
    </table>

    <div class="stamp">
        <div>CLEARED FOR EXAMINATION</div>
        <div>OFFICE OF THE REGISTRAR</div>
        <div>EXAMINATION CLEARANCE</div>
    </div>

</div>

<div class="text-center mt-4 no-print">
    <button onclick="window.print()" class="btn btn-primary">
        Print Clearance
    </button>
</div>

<style>
    body {
        font-family: "Times New Roman", serif;
    }

    .print-container {
        position: relative;
        padding: 5px 20px 20px 20px;
    }

    .watermark {
        position: absolute;
        inset: 0;
        opacity: 0.05;
        z-index: 0;
        overflow: hidden;
        pointer-events: none;
    }

    .watermark span {
        position: absolute;
        transform: rotate(-35deg);
        font-size: 28px;
        font-weight: bold;
        white-space: nowrap;
    }

    .header-section {
        display: grid;
        grid-template-columns: 120px 1fr 180px;
        align-items: start;
        gap: 15px;
        margin-bottom: 15px;
    }

    .header-center {
        text-align: center;
    }

    .inst-logo {
        width: 90px;
    }

    .qr-code {
        width: 120px;
    }

    .passport {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border: 1px solid #000;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th,
    .table td {
        border: 1px solid #000;
        padding: 6px;
    }

    .section-title {
        margin-top: 20px;
        margin-bottom: 10px;
    }

    .line {
        border-bottom: 1px solid #000;
        height: 50px;
    }

    .stamp {
        position: absolute;
        right: 50px;
        bottom: 280px;
        border: 5px solid #198754;
        color: #198754;
        padding: 18px;
        font-size: 20px;
        font-weight: bold;
        text-align: center;
        transform: rotate(-15deg);
        opacity: .22;
    }

    @media print {
        @page {
            size: A4;
            margin: 10mm;
        }

        .no-print {
            display: none;
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
            top: 0;
            left: 0;
            width: 100%;
            padding-top: 0;
        }

        .header-section {
            margin-top: 0;
        }
    }
</style>
