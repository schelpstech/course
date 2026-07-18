<?php
require_once 'start.inc.php';

$token = $_GET['token'] ?? '';

$systemStatus = 'invalid';
$student = null;
$stats = null;
$approvalStatus = null;
$documentType = null;
$semesterInfo = null;
$clearanceStatuses = [
    'course' => 'pending',
    'payment' => 'pending'
];
$examEligible = false;

function base64_url_decode($input)
{
    $remainder = strlen($input) % 4;
    if ($remainder) {
        $input .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($input, '-_', '+/'), true);
}

if ($token) {

    $decoded = base64_url_decode($token);
    $parts = is_string($decoded) ? explode('|', $decoded) : [];

    if (count($parts) === 4) {

        list($matric, $semester, $type, $signature) = $parts;

        $raw = $matric . '|' . $semester . '|' . $type;
        $expected = hash_hmac('sha256', $raw, APP_KEY);

        if (hash_equals($expected, $signature)) {

            $matric = trim($matric);
            $semester = trim($semester);

            // STUDENT INFO (shared by all supported verification documents)
            $studentRes = $model->query("
                SELECT s.*, i.name AS institution, p.name AS programme,
                       d.name AS department, l.name AS level
                FROM students s
                LEFT JOIN institutions i ON i.id = s.institution_id
                LEFT JOIN programmes p ON p.id = s.programme_id
                LEFT JOIN department d ON d.id = s.department_id
                LEFT JOIN levels l ON l.id = s.level_id
                WHERE s.matric_no = :matric
                LIMIT 1
            ", ['matric' => $matric]);

            if (!$studentRes) {
                $systemStatus = 'student_not_found';
            } else {
                $student = $studentRes[0];

                if ($type === 'course_form') {
                    $documentType = 'course_form';

                    // COURSE REGISTRATION
                    $regRes = $model->getRows("course_registered", [
                        "where" => [
                            "student_id" => $student['student_id'],
                            "semester"   => $semester
                        ],
                        "return_type" => "single"
                    ]);

                    if ($regRes) {

                        $approvalStatus = $regRes['approval_status'] ?? 'pending';

                        $NumberCourses = $model->countRows("registered_course", [
                            "where" => [
                                "course_regID" => $regRes['course_regID']
                            ]
                        ]);

                        if ($NumberCourses > 0) {

                            $stats = [
                                'courses' => $NumberCourses,
                                'units'   => $regRes['total_units'] ?? 0
                            ];

                            $systemStatus = 'qr_valid';

                        } else {
                            $systemStatus = 'no_registration';
                        }

                    } else {
                        $systemStatus = 'no_registration';
                    }

                } elseif ($type === 'exam_clearance') {
                    $documentType = 'exam_clearance';

                    // Resolve the semester registration represented by the QR token.
                    $semesterRegistrationRes = $model->query("
                        SELECT sr.id, sr.semester_id, sem.name AS semester_name,
                               ac.name AS session_name
                        FROM semesterregistration sr
                        INNER JOIN semesters sem ON sem.id = sr.semester_id
                        LEFT JOIN academic_sessions ac ON ac.id = sem.session_id
                        WHERE sr.student_id = :student_id
                          AND sr.semester_id = :semester_id
                        LIMIT 1
                    ", [
                        'student_id' => $student['student_id'],
                        'semester_id' => $semester
                    ]);

                    if (!$semesterRegistrationRes) {
                        $systemStatus = 'no_clearance_record';
                    } else {
                        $semesterInfo = $semesterRegistrationRes[0];

                        // Read the live clearance decisions, rather than trusting the
                        // fact that a slip was previously generated.
                        $clearanceRes = $model->query("
                            SELECT ct.code, sc.clearance_status
                            FROM student_clearances sc
                            INNER JOIN clearance_types ct
                                ON ct.id = sc.clearance_type_id
                            WHERE sc.semester_registration_id = :registration_id
                              AND ct.code IN ('PAYMENT', 'COURSE REGISTRATION')
                        ", [
                            'registration_id' => $semesterInfo['id']
                        ]);

                        foreach ($clearanceRes ?: [] as $clearance) {
                            $status = strtolower(trim($clearance['clearance_status'] ?? 'pending'));

                            if ($clearance['code'] === 'PAYMENT') {
                                $clearanceStatuses['payment'] = $status;
                            } elseif ($clearance['code'] === 'COURSE REGISTRATION') {
                                $clearanceStatuses['course'] = $status;
                            }
                        }

                        $examEligible = $clearanceStatuses['payment'] === 'approved'
                            && $clearanceStatuses['course'] === 'approved';
                        $systemStatus = 'qr_valid';
                    }
                } else {
                    $systemStatus = 'unsupported_type';
                }
            }

        } else {
            $systemStatus = 'tampered';
        }

    } else {
        $systemStatus = 'invalid_format';
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Verification Portal</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #0f172a;
            color: #fff;
        }

        .card-premium {
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            position: relative;
        }

        .badge-valid {
            background: #16a34a;
            padding: 10px 16px;
            border-radius: 50px;
            font-weight: bold;
            display: inline-block;
        }

        .badge-warning {
            background: #f59e0b;
            padding: 10px 16px;
            border-radius: 50px;
            font-weight: bold;
            display: inline-block;
        }

        .badge-info {
            background: #3b82f6;
            padding: 10px 16px;
            border-radius: 50px;
            font-weight: bold;
            display: inline-block;
        }

        .badge-danger {
            background: #dc2626;
            padding: 10px 16px;
            border-radius: 50px;
            font-weight: bold;
            display: inline-block;
        }

        .passport {
            width: 140px;
            height: 140px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid #334155;
        }

        .label {
            color: #94a3b8;
            font-size: 13px;
        }

        .value {
            font-size: 15px;
            font-weight: 500;
        }

        /* VERIFIED STAMP */
        .verified-stamp {
            position: absolute;
            top: 20px;
            right: 20px;
            border: 3px solid #16a34a;
            color: #16a34a;
            padding: 10px 14px;
            border-radius: 10px;
            font-weight: bold;
            transform: rotate(-10deg);
            opacity: 0.9;
        }

        .clearance-box {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 18px;
            height: 100%;
        }

        .status-approved { color: #4ade80; }
        .status-pending, .status-submitted { color: #fbbf24; }
        .status-rejected { color: #f87171; }

        @media (max-width: 767px) {
            .verified-stamp {
                position: static;
                display: inline-block;
                margin-bottom: 18px;
                transform: none;
            }

            .student-summary {
                align-items: flex-start !important;
            }
        }
    </style>
</head>

<body>

<div class="container py-5">

    <div class="text-center mb-4">
        <h2><?= htmlspecialchars($student['institution'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
        <h3>
            <?= $documentType === 'exam_clearance'
                ? 'Examination Clearance Verification Portal'
                : 'Course Form Verification Portal' ?>
        </h3>
    </div>

    <?php if ($systemStatus === 'qr_valid'): ?>

        <div class="card-premium">

            <?php if ($documentType === 'exam_clearance'): ?>

                <?php if ($examEligible): ?>
                    <div class="verified-stamp">&#10004; VERIFIED &amp; CLEARED</div>
                <?php endif; ?>

                <div class="d-flex align-items-center gap-3 student-summary">
                    <img
                        src="<?= htmlspecialchars(!empty($student['passport']) ? $student['passport'] : 'assets/images/default-user.png', ENT_QUOTES, 'UTF-8') ?>"
                        alt="Student passport"
                        class="passport"
                    >

                    <div>
                        <?php if ($examEligible): ?>
                            <span class="badge-valid">&#10004; ELIGIBLE FOR EXAMINATION</span>
                        <?php else: ?>
                            <span class="badge-warning">&#9888; CLEARANCE INCOMPLETE</span>
                        <?php endif; ?>

                        <h4 class="mt-2 mb-0">
                            <?= htmlspecialchars(trim(($student['first_name'] ?? '') . ' ' . ($student['other_name'] ?? '') . ' ' . ($student['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                        </h4>
                        <small><?= htmlspecialchars($student['matric_no'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                </div>

                <hr style="border-color:#1f2937">

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="label">Institution</div>
                        <div class="value"><?= htmlspecialchars($student['institution'] ?? 'Not available', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="label">Programme</div>
                        <div class="value"><?= htmlspecialchars($student['programme'] ?? 'Not available', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="label">Department</div>
                        <div class="value"><?= htmlspecialchars($student['department'] ?? 'Not available', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="label">Level</div>
                        <div class="value"><?= htmlspecialchars($student['level'] ?? 'Not available', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="label">Semester</div>
                        <div class="value"><?= htmlspecialchars($semesterInfo['semester_name'] ?? 'Not available', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="label">Academic Session</div>
                        <div class="value"><?= htmlspecialchars($semesterInfo['session_name'] ?? 'Not available', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>

                <hr style="border-color:#1f2937">

                <div class="row g-3 text-center">
                    <?php foreach ([
                        'course' => 'Course Form Clearance',
                        'payment' => 'Payment Clearance'
                    ] as $clearanceKey => $clearanceLabel): ?>
                        <?php $clearanceStatus = $clearanceStatuses[$clearanceKey] ?? 'pending'; ?>
                        <div class="col-md-6">
                            <div class="clearance-box">
                                <div class="label"><?= $clearanceLabel ?></div>
                                <h5 class="mt-2 mb-0 status-<?= htmlspecialchars($clearanceStatus, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= $clearanceStatus === 'approved' ? '&#10004; ' : '&#9888; ' ?>
                                    <?= htmlspecialchars(strtoupper($clearanceStatus), ENT_QUOTES, 'UTF-8') ?>
                                </h5>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-3 text-center label">
                    This page displays the current clearance records held by the portal.
                </div>

            <?php else: ?>

            <?php if ($approvalStatus === 'approved'): ?>
                <div class="verified-stamp">✔ VERIFIED BY REGISTRAR</div>
            <?php endif; ?>

            <div class="d-flex align-items-center gap-3">

                <img src="<?= $student['passport'] ?>" class="passport">

                <div>

                    <?php if ($approvalStatus === 'approved'): ?>
                        <span class="badge-valid">✔ APPROVED</span>
                    <?php elseif ($approvalStatus === 'pending'): ?>
                        <span class="badge-warning">⏳ PENDING APPROVAL</span>
                    <?php elseif ($approvalStatus === 'submitted'): ?>
                        <span class="badge-info">📤 SUBMITTED</span>
                    <?php elseif ($approvalStatus === 'rejected'): ?>
                        <span class="badge-danger">❌ REJECTED</span>
                    <?php endif; ?>

                    <h4 class="mt-2 mb-0">
                        <?= $student['first_name'] . ' ' . $student['last_name'] ?>
                    </h4>

                    <small><?= $student['matric_no'] ?></small>

                </div>

            </div>

            <hr style="border-color:#1f2937">

            <div class="row">

                <div class="col-md-6">
                    <div class="label">Institution</div>
                    <div class="value"><?= $student['institution'] ?></div>
                </div>

                <div class="col-md-6">
                    <div class="label">Programme</div>
                    <div class="value"><?= $student['programme'] ?></div>
                </div>

                <div class="col-md-6 mt-3">
                    <div class="label">Department</div>
                    <div class="value"><?= $student['department'] ?></div>
                </div>

                <div class="col-md-6 mt-3">
                    <div class="label">Level</div>
                    <div class="value"><?= $student['level'] ?></div>
                </div>

            </div>

            <hr style="border-color:#1f2937">

            <div class="row text-center">

                <div class="col-md-6">
                    <h5><?= $stats['courses'] ?></h5>
                    <small>Total Courses Registered</small>
                </div>

                <div class="col-md-6">
                    <h5><?= $stats['units'] ?></h5>
                    <small>Total Course Units</small>
                </div>

            </div>

            <?php endif; ?>

        </div>

    <?php elseif ($systemStatus === 'tampered'): ?>

        <div class="card-premium text-center">
            <div class="badge-danger">⚠ QR CODE TAMPERED</div>
        </div>

    <?php elseif ($systemStatus === 'no_registration'): ?>

        <div class="card-premium text-center">
            <div class="badge-danger">❌ NO COURSE REGISTRATION FOUND</div>
        </div>

    <?php elseif ($systemStatus === 'no_clearance_record'): ?>

        <div class="card-premium text-center">
            <div class="badge-danger">&#10060; NO SEMESTER CLEARANCE RECORD FOUND</div>
        </div>

    <?php else: ?>

        <div class="card-premium text-center">
            <div class="badge-danger">
                ❌ <?= $systemStatus ?>
            </div>
        </div>

    <?php endif; ?>

</div>

</body>
</html>
