<?php

$user = $model->getRows('students', [
    'select' => 'students.*, department.name as department_name, levels.name as level_name, programmes.name as programme_name',
    'join' => [
        'levels' => ' on students.level_id = levels.id',
        'programmes' => ' on programmes.id = students.programme_id',
        'department' => ' on department.id = students.department_id',
    ],
    'where' => ['students.student_id' => $_SESSION['user_id']],
    'return_type' => 'single'
]);
$levelId      = $user['level_id'];
$semester     = $activeSemester['name'];
$semesterID     = $activeSemester['id'];
$session     = $activeSession['name'];

/* ===================== */
/* FETCH REGISTRATION */
/* ===================== */
$reg = $model->getRows('course_registered', [
    'where' => [
        'student_id' => $_SESSION['user_id'],
        'semester'   => $activeSemester['id'],
        'session'    => $activeSession['id'],
    ],
    'return_type' => 'single'
]);

if (!in_array($reg['approval_status'], ['submitted', 'rejected'])) {
    redirectWithToast('error', 'Editing not allowed after course form submission', 'studentDashboard');
    exit;
}

/* ===================== */
/* FETCH SELECTED COURSES */
/* ===================== */
$registeredCourses = $model->getRows('registered_course', [
    'where' => ['course_regID' => $reg['course_regID']]
]);

$selectedIds = array_column($registeredCourses, 'course_id');

/* ===================== */
/* FETCH ALL COURSES */
/* ===================== */
$deptCourses = $model->getRows('courses', [
    'where' => [
        'level_id'      => $user['level_id'],
        'semester_id'      => $activeSemester['id'],
        'course_status' => 1
    ]
]);

?>

<div class="row">
    <div class="col-xl-10 offset-xl-1 col-md-12">

        <!-- ===================== -->
        <!-- INSTITUTION HEADER -->
        <!-- ===================== -->
        <div class="row mb-4 align-items-center">
            <div class="col-md-2 text-center">
                <img src="../uploads/logo/<?= $institution['inst_logo']  ?? 'default.png'; ?>"
                    style="width:80px;height:80px;border-radius:10px;object-fit:cover;">
            </div>
            <div class="col-md-10 text-center">
                <h3 style="font-weight:700;"><?= $institution['name'] ?? 'Institution Name'; ?></h3>
                <p style="margin:0;">Course Registration Form</p>
                <p><?= strtoupper($semester); ?> SEMESTER <?= strtoupper($session); ?> ACADEMIC SESSION </p>
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
                        <td><b>
                                <h5><?= $user['first_name'] . ' ' . $user['last_name']; ?></h5>
                            </b></td>
                        <td><strong>Matric:</strong></td>
                        <td></b>
                            <h6><?= $user['matric_no']; ?></h6></b>
                        </td>
                    </tr>

                    <tr>
                        <td><strong>Department:</strong></td>
                        <td>
                            <h6><?= $user['department_name']; ?></h6>
                        </td>
                        <td><strong>Level:</strong></td>
                        <td>
                            <h6><?= $user['level_name']; ?></h6>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-2 text-center">
                <img src="../<?= $user['passport'] ?? 'default.png'; ?>"
                    style="width: 100px;height:100px;border-radius:10px;object-fit:cover;">
            </div>

        </div>
        <hr>

        <!-- ===================== -->
        <!-- COURSE FORM -->
        <!-- ===================== -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">

                <h5 class="mb-0">Edit Course Registration</h5>

                <div class="unit-box">
                    Units:
                    <strong id="totalUnits">0</strong> / 30
                </div>
                <small id="unitWarning" style="display:none;color:red;">
                    You have exceeded maximum allowed units
                </small>
            </div>

            <div class="card-body">

                <form method="POST" action="../api/student/updateCourseForm.php">

                    <input type="hidden" name="csrf_token" value="<?= $utility->generateCsrf('editcourses') ?>">

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
                                <?php foreach ($deptCourses as $course): ?>
                                    <tr>

                                        <td>
                                            <?php if ($course['course_type'] == 'core'): ?>
                                                <input type="checkbox" checked disabled>
                                                <input type="hidden"
                                                    name="courses[]"
                                                    value="<?= $course['id'] ?>"
                                                    class="core-unit"
                                                    data-unit="<?= $course['unit'] ?>">
                                            <?php else: ?>
                                                <input type="checkbox"
                                                    class="course-checkbox"
                                                    name="courses[]"
                                                    value="<?= $course['id'] ?>"
                                                    data-unit="<?= $course['unit'] ?>"
                                                    <?= in_array($course['id'], $selectedIds) ? 'checked' : '' ?>>
                                            <?php endif; ?>
                                        </td>

                                        <td><strong><?= $course['course_code'] ?></strong></td>
                                        <td>
                                            <h6><?= $course['course_title'] ?></h6>
                                        </td>

                                        <td>
                                            <button type="button" class="btn btn-light">
                                                <?= $course['unit'] ?>
                                            </button>
                                        </td>

                                        <td>
                                            <button type="button" class="btn  <?= $course['course_type'] == 'core' ? 'btn-danger' : 'btn-success' ?>">
                                                <?= ucfirst($course['course_type']) ?>
                                            </button>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                        </table>
                    </div>


                    <!-- ACTION -->
                    <div class="text-right mt-4">
                        <button class="btn btn-primary px-4">
                            Submit Course Registration Form - No Correction 
                        </button>
                    </div>

                </form>

            </div>
        </div>

    </div>
</div>

<script>
    let maxUnits = 30;

    function calcUnits() {
        let total = 0;

        // ✅ 1. ALWAYS include core courses (hidden inputs)
        document.querySelectorAll('.core-unit').forEach(el => {
            total += parseInt(el.dataset.unit) || 0;
        });

        // ✅ 2. Add selected electives
        document.querySelectorAll('.course-checkbox:checked').forEach(el => {
            total += parseInt(el.dataset.unit) || 0;
        });

        document.getElementById('totalUnits').innerText = total;

        // Optional: visual control
        if (total > maxUnits) {
            document.getElementById('totalUnits').style.color = 'red';
            document.getElementById('unitWarning').style.display = 'block';
        } else {
            document.getElementById('totalUnits').style.color = 'green';
            document.getElementById('unitWarning').style.display = 'none';
        }
    }
    // Trigger on change
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('course-checkbox')) {
            calcUnits();
        }
    });
    // Run on load
    document.addEventListener('DOMContentLoaded', calcUnits);
</script>