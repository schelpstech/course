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

$departmentId = $user['department_id'];
$programmeId  = $user['programme_id'];
$levelId      = $user['level_id'];
$semester     = $activeSemester['name'];
$semesterID     = $activeSemester['id'];
$session     = $activeSession['name'];

/* ===================== */
/* FETCH COURSES */
/* ===================== */

$deptCourses = $model->getRows('courses', [
    'where' => [
        'level_id' => $levelId,
        'semester_id' => $semesterID,
        'course_status' => 1
    ]
]);


/* ===================== */
/* FETCH REGISTRATION */
/* ===================== */
$reg = $model->getRows('course_registered', [
    'where' => [
        'student_id' => $_SESSION['user_id'],
        'semester'   => $activeSemester['id'],
        'session'    => $activeSession['id']
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
    <div class="alert alert-success"><?= $_SESSION['success'];
                                        unset($_SESSION['success']); ?></div>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['error'];
                                    unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-xl-8 offset-2 col-md-12">

        <div class="card">

            <div class="card-body">

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

                    <div class="col-md-2 text-center">
                        <img src="../<?= $user['passport'] ?? 'default.png'; ?>"
                            style="width:80px;height:80px;border-radius:10px;object-fit:cover;">
                    </div>

                    <div class="col-md-10">
                        <table class="table table-sm table-border mb-0">
                            <tr>
                                <td><strong>Name:</strong></td>
                                <td><?= $user['first_name'] . ' ' . $user['last_name']; ?></td>
                                <td><strong>Matric:</strong></td>
                                <td><?= $user['matric_no']; ?></td>
                            </tr>

                            <tr>
                                <td><strong>Department:</strong></td>
                                <td><?= $user['department_name']; ?></td>
                                <td><strong>Level:</strong></td>
                                <td><?= $user['level_name']; ?></td>
                            </tr>
                        </table>
                    </div>

                </div>
                <hr>
                <form method="POST" action="../api/student/registerCourses.php" id="courseForm">

                    <input type="hidden" name="semester" value="<?= $semester; ?>">
                    <input type="hidden" name="session" value="<?= $activeSession['id']; ?>">
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

                                    <?php $i = 1;
                                    foreach ($deptCourses as $course): ?>
                                        <tr>

                                            <td><?= $i++; ?></td>

                                            <td>
                                                <?php if ($course['course_type'] == 'core'): ?>
                                                    <input type="checkbox" checked disabled>
                                                    <input type="hidden" name="courses[]"
                                                        value="<?= $course['id']; ?>"
                                                        class="core-unit"
                                                        data-unit="<?= $course['unit']; ?>">
                                                <?php else: ?>
                                                    <input type="checkbox"
                                                        class="course-checkbox"
                                                        name="courses[]"
                                                        value="<?= $course['id']; ?>"
                                                        data-unit="<?= $course['unit']; ?>">
                                                <?php endif; ?>
                                            </td>

                                            <td><?= htmlspecialchars($course['course_code']); ?></td>
                                            <td><?= htmlspecialchars($course['course_title']); ?></td>

                                            <td>
                                                <span class="badge bg-primary">
                                                    <?= $course['unit']; ?>
                                                </span>
                                            </td>

                                            <td>
                                                <span class="badge <?= $course['course_type'] == 'core' ? 'bg-success' : 'bg-warning' ?>">
                                                    <?= ucfirst($course['course_type']); ?>
                                                </span>
                                            </td>

                                        </tr>
                                    <?php endforeach; ?>
                                    

                                <?php else: ?>

                                    tr>
                                            <td colspan="6" class="text-center text-muted">
                                                <?php if (empty($levelId) || empty($semesterID)): ?>
                                                    Missing level or semester configuration. <?= $levelId." - ".$semesterID  ?>
                                                <?php else: ?>
                                                    No courses have been assigned to your department yet. <?= $levelId." - ".$semesterID  ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            <div class="py-4">
                                                <i class="ph ph-book-open fs-2"></i><br><br>
                                                No courses available for your level and semester.
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
                                Units: <strong id="selectedUnits">0</strong> / 24
                            </div>
                        </div>

                        <div class="col-md-6 text-right">
                            <button type="button" class="btn btn-warning" id="previewBtn">
                                Preview Registration
                            </button>
                        </div>

                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<!-- ===================== -->
<!-- MODAL -->
<!-- ===================== -->
<div class="modal fade" id="previewModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
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
            <div class="modal-header">
                <h5>Preview</h5>
            </div>

            <div class="modal-body">
                <div id="previewContent"></div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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
    let maxUnits = 24;

    function calculateUnits() {
        let total = 0;

        document.querySelectorAll('.core-unit').forEach(el => {
            total += parseInt(el.dataset.unit) || 0;
        });

        document.querySelectorAll('.course-checkbox:checked').forEach(el => {
            total += parseInt(el.dataset.unit) || 0;
        });

        document.getElementById('selectedUnits').innerText = total;

        document.getElementById('previewBtn').disabled = total > maxUnits;
    }

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('course-checkbox')) {
            calculateUnits();
        }
    });

    document.addEventListener('DOMContentLoaded', calculateUnits);

    /* PREVIEW */
    document.getElementById('previewBtn').addEventListener('click', function() {

        let html = '<table class="table table-bordered"><tr><th>Code</th><th>Title</th><th>Unit</th></tr>';

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

        html += `<tr><th colspan="2">Total</th><th>${total}</th></tr></table>`;

        document.getElementById('previewContent').innerHTML = html;

        new bootstrap.Modal(document.getElementById('previewModal')).show();
    });
</script>