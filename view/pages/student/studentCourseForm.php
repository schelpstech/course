<?php

$studentId = $_SESSION['user_id'];

/* ===================== */
/* FETCH STUDENT */
/* ===================== */
$user = $model->getRows('students', [
    'select' => 'students.*, department.name as department_name, levels.name as level_name, programmes.name as programme_name',
    'join' => [
        'levels' => ' on students.level_id = levels.id',
        'programmes' => ' on programmes.id = students.programme_id',
        'department' => ' on department.id = students.department_id',
    ],
    'where' => ['students.student_id' => $studentId],
    'return_type' => 'single'
]);

/* ===================== */
/* FETCH REGISTRATION */
/* ===================== */
$reg = $model->getRows('course_registered', [
    'where' => [
        'student_id' => $studentId,
        'semester'   => $activeSemester['id'],
        'session'    => $activeSession['id'],
        'approval_status'    => "pending"
    ],
    'return_type' => 'single'
]);

if (!in_array($reg['approval_status'], ['pending', 'Approved'])) {
    redirectWithToast('error', ' Course Registration Form Not Submitted or Approved for this Semester Yet. Submit Here', 'editCourseRegistration');
    exit;
}

/* ===================== */
/* FETCH COURSES */
/* ===================== */

$courses = $model->getRows(
    "registered_course",
    [
        'join' => [
            'courses' => ' on registered_course.course_id = courses.id'
        ],
        'where' => ['registered_course.course_regID' => $reg['course_regID']]
    ]
);


/* ===================== */
/* GROUP COURSES */
/* ===================== */
$deptCourses = [];

foreach ($courses as $c) {
    $deptCourses[] = $c;
}


$qrSrc = $qrcode->generateQRCode("This Course Form is Genuine. Online Verification will be available Shortly");

$qrPath = '<img src="' . $qrSrc . '" class="qr-code" style="max-width:260px;">';

?>


<div class="print-container">

    <!-- WATERMARK -->
    <div class="watermark"></div>

    <!-- HEADER -->
    <div class="header-section">
        <div class="header-left">
            <img src="../uploads/logo/<?= $institution['inst_logo'] ?>" class="inst-logo">
        </div>

        <div class="header-center" style="text-align: center;">
            <h2 class="inst-name" style="text-align: center;"><?= strtoupper($institution['name']) ?></h2>
            <p class="inst-subtitle" style="text-align: center;">OFFICE OF THE REGISTRAR</p>
            <h3 class="form-title" style="text-align: center;">COURSE REGISTRATION FORM</h3>
            <p class="session-title" style="text-align: center;"><?= strtoupper($activeSemester['name']); ?> SEMESTER • <?= strtoupper($activeSession['name']); ?> SESSION</p>
        </div>

        <div class="header-right">
            <?= $qrPath ?>
            <p class="qr-caption">Scan to Verify</p>
        </div>
    </div>

    <!-- STUDENT INFO -->
    <table class="table table-bordered student-info">
        <tr>
            <td><strong>Name:</strong>
                <h5> <?= $user['first_name'] . ' ' . $user['last_name']; ?></h5>
            </td>
            <td><strong>Matric No:</strong> <?= $user['matric_no']; ?></td>
            <td rowspan="2" class="passport-cell">
                <img src="../<?= $user['passport']; ?>" class="passport">
            </td>
        </tr>
        <tr>
            <td><strong>Department:</strong> <?= $user['department_name']; ?></td>
            <td><strong>Level:</strong> <?= $user['level_name']; ?></td>
        </tr>
    </table>

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
            <?php foreach ($deptCourses as $c): ?>
                <tr>
                    <td><?= $c['course_code']; ?></td>
                    <td><?= $c['course_title']; ?></td>
                    <td><?= $c['unit']; ?></td>
                    <td><?= ucfirst($c['course_type']); ?></td>
                    <td class="sign-cell"></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- TOTAL UNITS -->
    <p class="total-units">Total Units: <strong><?= $reg['total_units']; ?></strong></p>

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
    <button onclick="window.print()" class="btn btn-primary">Print</button>
    <a href="#" class="btn btn-success">Download PDF</a>
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
        background: url('../<?= $institution['inst_logo'] ?>') no-repeat center;
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
            display: none;
        }
    }
</style>