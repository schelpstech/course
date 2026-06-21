<?php
require_once __DIR__ . '/bootstrap.php';

unset($_SESSION['admission_applicant_id'], $_SESSION['admission_application_no']);
$_SESSION['toast'] = ['type' => 'success', 'message' => 'Logged out successfully.'];

header('Location: ../../admission.php');
exit;
