<!-- MOBILE TOGGLE -->
<button class="sidebar-toggle d-lg-none"
    type="button"
    data-bs-toggle="offcanvas"
    data-bs-target="#mobileSidebar">

    <i class="bi bi-list"></i>
</button>

<!-- DESKTOP SIDEBAR -->
<aside class="portal-sidebar d-none d-lg-flex">

    <!-- BRAND -->
    <div class="sidebar-brand">

        <img src="../assets/images/logo.png" alt="Logo">

        <div>
            <h5>Admission Portal</h5>
            <small>
                <?= h($activeSession['academic_session_name']) ?>
            </small>
        </div>

    </div>

    <!-- APPLICANT PROFILE -->
    <div class="sidebar-profile">

        <div class="profile-avatar">
            <?= strtoupper(substr($full['first_name'] ?? 'A', 0, 1)) ?>
        </div>

        <div>
            <h6>
                <?= h(($full['first_name'] ?? '') . ' ' . ($full['last_name'] ?? '')) ?>
            </h6>

            <small>
                Applicant
            </small>
        </div>

    </div>

    <!-- MENU -->
    <nav class="sidebar-menu">

        <a href="dashboard.php" class="active">
            <i class="bi bi-grid"></i>
            <span>Dashboard</span>
        </a>

        <a href="form.php">
            <i class="bi bi-file-earmark-text"></i>
            <span>My Application</span>
        </a>

        <a href="transaction.php">
            <i class="bi bi-credit-card"></i>
            <span>Transaction History</span>
        </a>

    </nav>

    <!-- PROGRESS -->
    <div class="sidebar-progress">

        <div class="d-flex justify-content-between mb-2">
            <small>Completion</small>
            <small><?= (int)($completion['percentage'] ?? 0) ?>%</small>
        </div>

        <div class="progress">
            <div class="progress-bar"
                style="width:<?= (int)($completion['percentage'] ?? 0) ?>%">
            </div>
        </div>

    </div>

    <!-- SUPPORT -->
    <div class="sidebar-help">

        <div class="help-icon">
            <i class="bi bi-headset"></i>
        </div>

        <h6>Need Assistance?</h6>

        <p>
            Contact the admission office for support and enquiries.
        </p>

        <a href="support.php" class="btn btn-light w-100">
            Contact Support
        </a>

    </div>

</aside>

<!-- MOBILE SIDEBAR -->
<div class="offcanvas offcanvas-start mobile-sidebar"
    tabindex="-1"
    id="mobileSidebar">

    <div class="offcanvas-header">

        <div class="d-flex align-items-center gap-3">

            <img src="../assets/images/logo.png"
                width="42"
                height="42"
                alt="Logo">

            <div>
                <h5 class="mb-0">
                    Admission Portal
                </h5>

                <small class="text-muted">
                    <?= h($activeSession['academic_session_name'] ?? '') ?>
                </small>
            </div>

        </div>

        <button type="button"
            class="btn-close"
            data-bs-dismiss="offcanvas">
        </button>

    </div>

    <div class="offcanvas-body">

        <div class="mobile-user-card">

            <div class="avatar">
                <?= strtoupper(substr($full['first_name'] ?? 'A', 0, 1)) ?>
            </div>

            <div>
                <strong>
                    <?= h(($full['first_name'] ?? '') . ' ' . ($full['last_name'] ?? '')) ?>
                </strong>

                <div class="text-muted small">
                    Applicant
                </div>
            </div>

        </div>

        <nav class="sidebar-menu mt-4">

            <a href="dashboard.php" class="active">
                <i class="bi bi-grid"></i>
                Dashboard
            </a>

            <a href="form.php">
                <i class="bi bi-file-earmark-text"></i>
                My Application
            </a>

            <a href="transaction.php">
                <i class="bi bi-credit-card"></i>
                Transaction History
            </a>

        </nav>

        <div class="mobile-support-card mt-4">

            <h6>Need Help?</h6>

            <p class="small text-muted mb-3">
                Contact the admission office.
            </p>

            <a href="#"
                class="btn btn-primary w-100">
                Contact Support
            </a>

        </div>

    </div>

</div>