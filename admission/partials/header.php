<?php
require_once './../start.inc.php';
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
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>Admission Portal | Owutech College of Health, Management & Technology</title>

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- SEO -->
    <meta name="description" content="Apply for admission into Owutech College of Health, Management & Technology. Create an account, complete your application, upload required documents, make secure online payments, and monitor your admission status in real time.">

    <meta name="keywords" content="Owutech, Admission Portal, College Admission, Health Sciences, Technology, Online Application, Nigeria, Admission Form, Student Portal">

    <meta name="author" content="Owutech College of Health, Management & Technology">

    <meta name="robots" content="index, follow">

    <meta name="googlebot" content="index, follow">

    <link rel="canonical" href="https://owutech-edu.org/admission/">

    <!-- Theme -->
    <meta name="theme-color" content="#0d6efd">
    <meta name="color-scheme" content="light">

    <!-- Favicons -->
    <link rel="icon" type="image/png" sizes="32x32" href="https://owutech-edu.org/assets/images/logo.png">
    <link rel="apple-touch-icon" href="https://owutech-edu.org/assets/images/logo.png">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Admission Portal | Owutech College of Health, Management & Technology">
    <meta property="og:description" content="Apply online, pay securely, complete your admission application and monitor your admission progress.">
    <meta property="og:image" content="https://owutech-edu.org/assets/images/logo.png">
    <meta property="og:url" content="https://owutech-edu.org/admission/">
    <meta property="og:site_name" content="Owutech College of Health, Management & Technology">

    <!-- Twitter/X -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Admission Portal | Owutech College of Health, Management & Technology">
    <meta name="twitter:description" content="Apply online, upload documents, pay admission fees and track your admission process.">
    <meta name="twitter:image" content="https://owutech-edu.org/assets/images/logo.png">

    <!-- Mobile -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Owutech Admission">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <!-- Preconnect -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">

    <!-- Styles -->
    <link rel="stylesheet" href="../assets/css/admission.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Structured Data -->
    <script type="application/ld+json">
    {
      "@context":"https://schema.org",
      "@type":"CollegeOrUniversity",
      "name":"Owutech College of Health, Management & Technology",
      "url":"https://owutech-edu.org",
      "logo":"https://owutech-edu.org/assets/images/logo.png",
      "sameAs":[
        "https://facebook.com/",
        "https://instagram.com/",
        "https://x.com/"
      ]
    }
    </script>

</head>