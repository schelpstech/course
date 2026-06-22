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

        <a href="#applicationSection">
            <i class="bi bi-file-earmark-text"></i>
            <span>My Application</span>
        </a>

        <a href="#payments">
            <i class="bi bi-credit-card"></i>
            <span>Payments</span>
        </a>

        <a href="#documents">
            <i class="bi bi-folder2-open"></i>
            <span>Documents</span>
        </a>

        <a href="#">
            <i class="bi bi-chat-dots"></i>
            <span>Messages</span>
        </a>

        <a href="#">
            <i class="bi bi-person"></i>
            <span>Profile</span>
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

        <a href="#" class="btn btn-light w-100">
            Contact Support
        </a>

    </div>

</aside>

<!-- MOBILE SIDEBAR -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar">

    <div class="offcanvas-header">

        <h5>Admission Portal</h5>

        <button type="button"
            class="btn-close"
            data-bs-dismiss="offcanvas">
        </button>

    </div>

    <div class="offcanvas-body">

        <nav class="sidebar-menu">

            <a href="dashboard.php">
                <i class="bi bi-grid"></i>
                Dashboard
            </a>

            <a href="#applicationSection">
                <i class="bi bi-file-earmark-text"></i>
                My Application
            </a>

            <a href="#payments">
                <i class="bi bi-credit-card"></i>
                Payments
            </a>

            <a href="#documents">
                <i class="bi bi-folder2-open"></i>
                Documents
            </a>

        </nav>

    </div>

</div>
