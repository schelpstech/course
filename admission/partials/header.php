<?php
require_once '../start.inc.php';
$csrf = $admission->csrfToken();

$activeSession = $admission->activeSession();

$applicantId = (int)($_SESSION['admission_applicant_id'] ?? 0);

if ($applicantId) {

    $application = $admission->getApplicationForApplicant($applicantId);

    if ($application) {

        $full = $admission->getFullApplication((int)$application['id']);

        $completion = $admission->completion((int)$application['id']);
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admission Portal | Course Portal</title>
    <link rel="icon" href="../assets/images/logo.png" type="image/png">

    <link rel="stylesheet" href="../assets/css/admission.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>