<?php
require_once 'start.inc.php';

$token = $_GET['token'] ?? '';

$systemStatus = 'invalid';
$student = null;
$stats = null;
$approvalStatus = null;

function base64_url_decode($input)
{
    $remainder = strlen($input) % 4;
    if ($remainder) {
        $input .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($input, '-_', '+/'));
}

if ($token) {

    $decoded = base64_url_decode($token);
    $parts = explode('|', $decoded);

    if (count($parts) === 4) {

        list($matric, $semester, $type, $signature) = $parts;

        $raw = $matric . '|' . $semester . '|' . $type;
        $expected = hash_hmac('sha256', $raw, APP_KEY);

        if (hash_equals($expected, $signature)) {

            if ($type === 'course_form') {

                $matric = trim($matric);
                $semester = trim($semester);

                // STUDENT INFO
                $studentRes = $model->query("
                    SELECT s.*, i.name AS institution, p.name AS programme,
                           d.name AS department, l.name AS level
                    FROM students s
                    LEFT JOIN institutions i ON i.id = s.institution_id
                    LEFT JOIN programmes p ON p.id = s.programme_id
                    LEFT JOIN department d ON d.id = s.department_id
                    LEFT JOIN levels l ON l.id = s.level_id
                    WHERE s.matric_no = '$matric'
                    LIMIT 1
                ");

                if ($studentRes) {

                    $student = $studentRes[0];

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

                } else {
                    $systemStatus = 'student_not_found';
                }

            } else {
                $systemStatus = 'unsupported_type';
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
    </style>
</head>

<body>

<div class="container py-5">

    <div class="text-center mb-4">
        <h2><?= $student['institution'] ?? '' ?></h2>
        <h3>Course Form Verification Portal</h3>
    </div>

    <?php if ($systemStatus === 'qr_valid'): ?>

        <div class="card-premium">

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

        </div>

    <?php elseif ($systemStatus === 'tampered'): ?>

        <div class="card-premium text-center">
            <div class="badge-danger">⚠ QR CODE TAMPERED</div>
        </div>

    <?php elseif ($systemStatus === 'no_registration'): ?>

        <div class="card-premium text-center">
            <div class="badge-danger">❌ NO COURSE REGISTRATION FOUND</div>
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