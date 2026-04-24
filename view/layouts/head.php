<!DOCTYPE html>
<html lang="en">

<head>

    <!-- Dynamic Title -->
    <title><?= isset($pageId) ? ucfirst($pageId) : 'Dashboard'; ?> | Course Registration Portal</title>

    <!-- META -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Course Registration and Management Portal" />
    <meta name="keywords" content="Course Registration, Student Portal, University Management System" />
    <meta name="author" content="Owutech Solutions" />
    <meta name="theme-color" content="#1e293b" />

    <!-- Favicon -->
    <link rel="icon" href="../assets/images/favicon.svg" type="image/svg+xml" />

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&display=swap" rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="../assets/css/plugins/phosphor-icons.css" />
    <link rel="stylesheet" href="../assets/css/plugins/tabler-icons.min.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/css/style.css" id="main-style-link" />
    <link rel="stylesheet" href="../assets/css/style-preset.css" />
    <link rel="stylesheet" href="../assets/css/style-portal.css" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" data-pc-theme="light">

<!-- Loader -->
<div class="loader-bg">
    <div class="loader-track">
        <div class="loader-fill"></div>
    </div>
</div>