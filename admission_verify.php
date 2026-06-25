<?php
require_once './start.inc.php';

$admission = new Admission($db, $model, $utility, $qrcode, $mailservice);

$record = null;
$error = '';

try {
    $record = $admission->verifyPublicRecord(
        $_GET['application_no'] ?? '',
        $_GET['registration_no'] ?? '',
        $_GET['signature'] ?? ''
    );

    if (!$record) {
        $error = 'The verification link is invalid or the application could not be found.';
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admission Verification | Course Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/style-preset.css">
    <style>
        body { min-height:100vh; display:grid; place-items:center; background:#f5f7fb; }
        .verify-card { width:min(720px, 92vw); background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:28px; box-shadow:0 16px 45px rgba(15,23,42,.08); }
        .status-pill { display:inline-block; border-radius:999px; padding:6px 12px; font-size:12px; font-weight:700; background:#dcfce7; color:#166534; }
        .error-pill { background:#fee2e2; color:#991b1b; }
        table { width:100%; border-collapse:collapse; margin-top:18px; }
        th,td { padding:11px 10px; border-bottom:1px solid #edf2f7; text-align:left; }
        th { width:34%; color:#475569; }
    </style>
</head>
<body>
    <main class="verify-card">
        <h2 class="mb-1">Admission Verification</h2>
        <?php if ($record): ?>
            <span class="status-pill">Verified Record</span>
            <table>
                <tr><th>Applicant</th><td><?= h($record['applicant_name']) ?></td></tr>
                <tr><th>Application No</th><td><?= h($record['application_no']) ?></td></tr>
                <tr><th>Registration No</th><td><?= h($record['registration_no']) ?></td></tr>
                <?php if (!empty($record['matric_no'])): ?>
                    <tr><th>Matric No</th><td><?= h($record['matric_no']) ?></td></tr>
                <?php endif; ?>
                <tr><th>Session</th><td><?= h($record['session_name']) ?></td></tr>
                <tr><th>Institution</th><td><?= h($record['institution_name']) ?></td></tr>
                <tr><th>Programme</th><td><?= h($record['programme_name']) ?></td></tr>
                <tr><th>Department</th><td><?= h($record['department_name']) ?></td></tr>
                <tr><th>Status</th><td><?= h($record['form_status']) ?></td></tr>
            </table>
        <?php else: ?>
            <span class="status-pill error-pill">Verification Failed</span>
            <p class="mt-3 mb-0"><?= h($error) ?></p>
        <?php endif; ?>
    </main>
</body>
</html>
