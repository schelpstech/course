    </div>
    </div>

    <!-- FOOTER -->
    <footer class="pc-footer">
        <div class="footer-wrapper container-fluid">
            <div class="row align-items-center">

                <div class="col-md-6 my-1">
                    <p class="m-0">
                        © <?= date('Y'); ?> Course Portal • All rights reserved
                    </p>
                </div>

                <div class="col-md-6 my-1 text-md-end">
                    <ul class="list-inline footer-link mb-0">
                        <li class="list-inline-item"><a href="https://owutech-edu.org">Owutech Portal</a></li>
                        <li class="list-inline-item"><a href="https://forms.gle/zqeYzXokThnPfotaA" target="_blank">Need Support? Click here</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- ========================= -->
    <!-- CORE JS (ORDER MATTERS) -->
    <!-- ========================= -->

    <!-- jQuery (ONLY ONCE) -->
    <script src="../assets/js/jquery-3.6.1.min.js"></script>

    <!-- Bootstrap 5 -->
    <script src="../assets/js/plugins/popper.min.js"></script>
    <script src="../assets/js/plugins/bootstrap.min.js"></script>

    <!-- Core Scripts -->
    <script src="../assets/js/plugins/simplebar.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/theme.js"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

    <!-- OPTIONAL: Buttons Extension -->
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

    <script src="../assets/js/ajax/sessionChecker.js"></script>

    <!-- ========================= -->
    <!-- PAGE-SPECIFIC SCRIPTS -->
    <!-- ========================= -->


    <?php if (!empty($pageId) && in_array($pageId, ['institutions'])): ?>
        <script src="../assets/js/ajax/institution.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['programs'])): ?>
        <script src="../assets/js/ajax/programmes.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['departments'])): ?>
        <script src="../assets/js/ajax/department.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['manageLevels'])): ?>
        <script src="../assets/js/ajax/level.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['academicSessions'])): ?>
        <script src="../assets/js/ajax/academicsession.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['manageSemesters'])): ?>
        <script src="../assets/js/ajax/semesterMgr.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['courses'])): ?>
        <script src="../assets/js/ajax/courseMgr.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['staffUsers'])): ?>
        <script src="../assets/js/ajax/staffAdmin.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['rolesPermissions'])): ?>
        <script src="../assets/js/ajax/rolePermission.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['courseAllocations'])): ?>
        <script src="../assets/js/ajax/courseAllocation.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['resultConfig'])): ?>
        <script src="../assets/js/ajax/resultConfig.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['gradingRules'])): ?>
        <script src="../assets/js/ajax/gradingRules.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['lecturerDashboard'])): ?>
        <script src="../assets/js/ajax/lecturerDashboard.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['lecturerScoresheet'])): ?>
        <script src="../assets/js/ajax/lecturerScoresheet.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['departmentDashboard', 'departmentStudents', 'departmentCourseForms', 'departmentCourses', 'departmentModeration'])): ?>
        <script src="../assets/js/ajax/departmentPortal.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['students'])): ?>
        <script src="../assets/js/ajax/studentMgr.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['payment_config'])): ?>
        <script src="../assets/js/ajax/paymentconfig.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['payment_assign'])): ?>
        <script src="../assets/js/ajax/paymentassign.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['payment_remark'])): ?>
        <script src="../assets/js/ajax/paymentreview.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['student-trail'])): ?>
        <script src="../assets/js/ajax/studentauditlog.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['semregistrationStatus'])): ?>
        <script src="../assets/js/ajax/semregstatus.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['internetPaymentReview'])): ?>
        <script src="../assets/js/ajax/paymentInternetReview.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['courseformMgr'])): ?>
        <script src="../assets/js/ajax/courseformmgr.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['manage_clearance'])): ?>
        <script src="../assets/js/ajax/clearanceMgr.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['payment_clearance'])): ?>
        <script src="../assets/js/ajax/paymentClearanceHandler.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && $pageId === 'admissionDashboard'): ?>
        <script src="../assets/js/plugins/apexcharts.min.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['admissionDashboard', 'admissionSessions', 'admissionApplications', 'admissionCriteria'])): ?>
        <script src="../assets/js/ajax/admissionAdmin.js"></script>
    <?php endif; ?>
    <?php if (!empty($pageId) && in_array($pageId, ['payment_clearance', 'manage_clearance', 'courseformMgr', 'internetPaymentReview', 'semregistrationStatus', 'payment_remark', 'payment_assign', 'payment_config', 'audit-trail', 'student-trail', 'institutions', 'programs', 'departments', 'students', 'manageLevels', 'academicSessions', 'manageSemesters', 'courses', 'staffUsers', 'rolesPermissions', 'courseAllocations', 'resultConfig', 'gradingRules', 'lecturerDashboard', 'lecturerScoresheet', 'departmentStudents', 'departmentCourseForms', 'departmentCourses', 'departmentModeration'])): ?>

        <!-- REQUIRED FOR EXPORT -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

        <script>
            $(document).ready(function() {

                $('.dataTable').each(function() {

                    if ($.fn.DataTable.isDataTable(this)) {
                        return;
                    }

                    $(this).DataTable({
                        dom: 'Bfrtip',
                        pageLength: 10,
                        deferRender: true,
                        processing: true,

                        buttons: [{
                                extend: 'copyHtml5',
                                exportOptions: {
                                    columns: ':visible'
                                }
                            },
                            {
                                extend: 'excelHtml5',
                                exportOptions: {
                                    columns: ':visible'
                                }
                            },
                            {
                                extend: 'csvHtml5',
                                exportOptions: {
                                    columns: ':visible'
                                }
                            },
                            {
                                extend: 'pdfHtml5',
                                exportOptions: {
                                    columns: ':visible'
                                }
                            },
                            'colvis'
                        ]
                    });

                });

            });
        </script>
    <?php endif; ?>

    <!-- ========================= -->
    <!-- TOAST (CLEAN VERSION) -->
    <!-- ========================= -->

    <?php if (!empty($_SESSION['toast'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: '<?= $_SESSION['toast']['type']; ?>',
                    title: '<?= $_SESSION['toast']['message']; ?>',
                    showConfirmButton: false,
                    timer: 4000
                });
            });
        </script>
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>

    </body>

    </html>
