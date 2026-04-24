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
                        <li class="list-inline-item"><a href="https://wa.me/+2347080024171">Support</a></li>
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
    <?php if (!empty($pageId) && in_array($pageId, ['students'])): ?>
        <script src="../assets/js/ajax/studentMgr.js"></script>
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