<?php
require_once '../../../../start.inc.php';

$utility->requireAdmin();

$id = $_GET['id'] ?? null;

if (!$id) {
    echo "<div class='text-danger'>Invalid request</div>";
    exit;
}

/**
 * 1. GET STUDENT + REGISTRATION INFO
 */
$student = $model->query("
    SELECT 
        cr.course_regID,
        cr.approval_status,
        s.first_name,
        s.last_name,
        s.other_name,
        s.matric_no,
        s.passport,
        d.code AS department,
        l.code AS level,
        p.name AS programme,
        i.name AS institution
    FROM course_registered cr
    JOIN students s ON s.student_id = cr.student_id
    LEFT JOIN department d ON d.id = s.department_id
    LEFT JOIN levels l ON l.id = s.level_id
    LEFT JOIN programmes p ON p.id = s.programme_id
    LEFT JOIN institutions i ON i.id = s.institution_id
    WHERE cr.course_regID = '$id'
    LIMIT 1
");

if (!$student) {
    echo "<div class='text-danger'>Student record not found</div>";
    exit;
}

$student = $student[0];

/**
 * 2. GET COURSES
 */
$courses = $model->query("
    SELECT c.course_code, c.course_title, c.unit
    FROM registered_course rc
    JOIN courses c ON c.id = rc.course_id
    WHERE rc.course_regID = '$id'
");

$totalUnits = 0;
?>

<!-- ================= STUDENT HEADER ================= -->
<div class="card mb-3">
    <div class="card-body d-flex align-items-center">

        <div style="margin-right:15px;">
            <img src="../<?= $student['passport'] ?: '../assets/img/default.png' ?>"
                 width="90" height="90"
                 style="border-radius:10px; object-fit:cover;">
        </div>

        <div>
            <h4 class="mb-1">
                <?= $student['first_name'] . ' ' . $student['other_name'] . ' ' . $student['last_name'] ?>
            </h4>

            <p class="mb-1">
                <strong>Matric:</strong> <?= $student['matric_no'] ?>
            </p>

            <p class="mb-1">
                <strong>Institution:</strong> <?= $student['institution'] ?>
            </p>

            <p class="mb-1">
                <strong>Program:</strong> <?= $student['programme'] ?>
            </p>

            <p class="mb-1">
                <strong>Department:</strong> <?= $student['department'] ?> |
                <strong>Level:</strong> <?= $student['level'] ?>
            </p>

            <p class="mb-0">
                <strong>Status:</strong>
                <?= strtoupper($student['approval_status']) ?>
            </p>
        </div>

    </div>
</div>

<!-- ================= COURSES TABLE ================= -->
<div class="card">
    <div class="card-body">

        <?php if (!$courses): ?>
            <div class="text-danger">No courses found</div>
        <?php else: ?>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Course Code</th>
                        <th>Course Title</th>
                        <th>Units</th>
                    </tr>
                </thead>

                <tbody>
                    <?php $count = 1; ?>
                    <?php foreach ($courses as $c): ?>
                        <?php $totalUnits += $c['unit']; ?>
                        <tr>
                            <td><?= $count++ ?></td>
                            <td><?= $c['course_code'] ?></td>
                            <td><?= $c['course_title'] ?></td>
                            <td><?= $c['unit'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="text-end mt-2">
                <strong>Total Units: <?= $totalUnits ?></strong>
            </div>

        <?php endif; ?>

    </div>
</div>