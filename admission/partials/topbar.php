<body>
    <div class="admission-shell">
      <header class="portal-topbar">
            <div class="container-fluid px-4 py-3 d-flex align-items-center justify-content-between">
                <a href="admission.php" class="d-flex align-items-center gap-3 text-decoration-none text-dark">
                    <img src="../assets/images/logo.png" class="brand-mark" alt="Logo">
                    <div>
                        <h5 class="mb-0">Admission Portal</h5>
                        <small class="text-muted"><?= $activeSession ? h($activeSession['academic_session_name']) : 'No active session' ?></small>
                    </div>
                </a>
                <div class="d-flex align-items-center gap-2">
                    <a href="../index.php" class="btn btn-outline-secondary btn-sm">Student Login</a>
                    <?php if ($applicantId): ?>
                        <a href="../api/admission/logout.php" class="btn btn-danger btn-sm">Logout</a>
                    <?php endif; ?>
                </div>
            </div>
        </header>