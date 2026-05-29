<?php
require_once 'start.inc.php';

$token = $_GET['token'] ?? '';

$status = 'invalid';
$student = null;
$stats = null;
$type = null;
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

    // Decode URL-safe base64
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

                // 1. Get student
                $studentRes = $model->query("
                    SELECT s.*, i.name AS institution, p.name AS programme,
                           d.name AS department, l.name AS level
                    FROM students s
                    LEFT JOIN institutions i ON i.id = s.institution_id
                    LEFT JOIN programmes p ON p.id = s.programme_id
                    LEFT JOIN departments d ON d.id = s.department_id
                    LEFT JOIN levels l ON l.id = s.level_id
                    WHERE s.matric_no = '$matric'
                    LIMIT 1
                ");

                if ($studentRes) {

                    $student = $studentRes[0];

                    // 2. Course registration
                    $regRes = $model->getRows("course_registered", [
                        "where" => [
                            "student_id" => $student['id'],   // FIXED assumption
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

                            // Only approved is valid
                            if ($approvalStatus === 'approved') {

                                $stats = [
                                    'courses' => $NumberCourses,
                                    'units'   => $regRes['total_units'] ?? 0
                                ];

                                $status = 'valid';

                            } elseif ($approvalStatus === 'pending') {
                                $status = 'pending_approval';

                            } elseif ($approvalStatus === 'submitted') {
                                $status = 'submitted';

                            } elseif ($approvalStatus === 'rejected') {
                                $status = 'rejected';

                            } else {
                                $status = 'unknown_status';
                            }

                        } else {
                            $status = 'no_registration';
                        }

                    } else {
                        $status = 'no_registration';
                    }

                } else {
                    $status = 'student_not_found';
                }

            } else {
                $status = 'unsupported_type';
            }

        } else {
            $status = 'tampered';
        }

    } else {
        $status = 'invalid_format';
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
        }

        .badge-valid {
            background: #16a34a;
            padding: 10px 16px;
            border-radius: 50px;
            font-weight: bold;
        }

        .badge-invalid {
            background: #dc2626;
            padding: 10px 16px;
            border-radius: 50px;
            font-weight: bold;
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
    </style>
</head>

<body>

<div class="container py-5">

    <div class="text-center mb-4">
        <h2><?= $student['institution'] ?></h2>
        <h3>Course Form Verification Portal</h3>
    </div>

    <?php if ($status === 'valid'): ?>

        <div class="card-premium">

            <div class="d-flex align-items-center gap-3">

                <img src="<?= $student['passport'] ?>" class="passport">

                <div>
                    <div class="badge-valid">✔ VERIFIED COURSE FORM</div>
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

    <?php elseif ($status === 'pending_approval'): ?>

        <div class="card-premium text-center">
            <div class="badge-invalid" style="background:#f59e0b;">
                ⏳ COURSE FORM PENDING APPROVAL - CONTACT REGISTRY FOR APPROVAL
            </div>
        </div>

    <?php elseif ($status === 'submitted'): ?>

        <div class="card-premium text-center">
            <div class="badge-invalid" style="background:#3b82f6;">
                📤 COURSE FORM SAVED PENDING SUBMISSION
            </div>
        </div>

    <?php elseif ($status === 'rejected'): ?>

        <div class="card-premium text-center">
            <div class="badge-invalid">
                ❌ COURSE FORM REJECTED
            </div>
        </div>

    <?php elseif ($status === 'tampered'): ?>

        <div class="card-premium text-center">
            <div class="badge-invalid">⚠ QR CODE TAMPERED</div>
        </div>

    <?php else: ?>

        <div class="card-premium text-center">
            <div class="badge-invalid">❌ <?= $status ?></div>
        </div>

    <?php endif; ?>

</div>

</body>
</html>