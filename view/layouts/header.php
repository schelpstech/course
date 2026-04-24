<?php



// Get email safely
$user_email = '';

if ($isAdmin && isset($admin['email'])) {
    $user_email = $admin['email'];
} elseif ($isStudent && isset($student['email'])) {
    $user_email = $student['email'];
}

// Current page
$currentPage = $pageId ?? 'dashboard';

?>

<header class="pc-header">
    <div class="header-wrapper">

        <!-- LEFT CONTROLS -->
        <div class="me-auto pc-mob-drp">
            <ul class="list-unstyled">
                <li class="pc-h-item pc-sidebar-collapse">
                    <a href="#" class="pc-head-link ms-0" id="sidebar-hide">
                        <i class="ph ph-list"></i>
                    </a>
                </li>
                <li class="pc-h-item pc-sidebar-popup">
                    <a href="#" class="pc-head-link ms-0" id="mobile-collapse">
                        <i class="ph ph-list"></i>
                    </a>
                </li>
            </ul>
        </div>

        <!-- RIGHT CONTROLS -->
        <div class="ms-auto">
            <ul class="list-unstyled">

                <!-- THEME SWITCH -->
                <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle arrow-none me-0"
                        data-bs-toggle="dropdown"
                        href="#">

                        <i class="ph ph-sun-dim"></i>
                    </a>

                    <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
                        <a href="#!" class="dropdown-item" onclick="layout_change('dark')">
                            <i class="ph ph-moon"></i> Dark
                        </a>
                        <a href="#!" class="dropdown-item" onclick="layout_change('light')">
                            <i class="ph ph-sun"></i> Light
                        </a>
                        <a href="#!" class="dropdown-item" onclick="layout_change_default()">
                            <i class="ph ph-cpu"></i> Default
                        </a>
                    </div>
                </li>

                <!-- NOTIFICATIONS -->
                <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle arrow-none me-0"
                        data-bs-toggle="dropdown"
                        href="#">
                        <i class="ph ph-bell"></i>
                        <span class="badge bg-success pc-h-badge">3</span>
                    </a>

                    <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
                        <div class="dropdown-header">
                            <h5 class="m-0">Notifications</h5>
                        </div>

                        <div class="dropdown-body p-2">
                            <p class="text-muted small">No new notifications</p>
                        </div>
                    </div>
                </li>

                <!-- USER MENU -->
                <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle arrow-none me-0"
                        data-bs-toggle="dropdown"
                        href="#">

                        <i class="ph ph-user-circle"></i>
                    </a>

                    <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">

                        <div class="dropdown-item text-muted">
                            <?= htmlspecialchars($user_email) ?>
                        </div>

                        <div class="dropdown-divider"></div>

                        <a href="#!" class="dropdown-item">
                            <i class="ph ph-gear"></i> Settings
                        </a>

                        <a href="#!" class="dropdown-item">
                            <i class="ph ph-lifebuoy"></i> Support
                        </a>

                        <a href="../api/logout.php" class="dropdown-item text-danger">
                            <i class="ph ph-sign-out"></i> Logout
                        </a>

                    </div>
                </li>

            </ul>
        </div>

    </div>
</header>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">

                <div class="page-header-title">
                    <h5 class="mb-0">
                        <?= $isAdmin ? 'Admin Dashboard' : 'Student Dashboard' ?>
                    </h5>
                </div>

                <ul class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>

                    <li class="breadcrumb-item">
                        <?= $isAdmin ? 'Admin' : 'Student' ?>
                    </li>

                    <li class="breadcrumb-item">
                        <?= htmlspecialchars($currentPage) ?>
                    </li>
                </ul>

            </div>
        </div>
        <!-- [ breadcrumb ] end -->