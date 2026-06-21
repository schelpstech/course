<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

try {
    $applicationId = (int) ($_GET['application_id'] ?? 0);

    if (!empty($_SESSION['admin_id']) && $applicationId) {
        $full = $admission->getFullApplication($applicationId);
    } else {
        $application = admission_current_application($admission);
        $full = $admission->getFullApplication((int) $application['id']);
    }

    if (!$full) {
        throw new Exception('Application not found.');
    }

    if (empty($full['registration_no'])) {
        throw new Exception('Application slip is available after final submission.');
    }

    $passport = admission_document_data_uri($full, 'passport');
    $qr = $admission->qrDataUri($full);
    $html = admission_slip_html($full, $passport, $qr);

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4');
    $dompdf->render();
    $dompdf->stream('application-slip-' . $full['application_no'] . '.pdf', ['Attachment' => false]);
} catch (Throwable $e) {
    http_response_code(422);
    echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}

function admission_document_data_uri(array $application, string $type): string
{
    foreach ($application['documents'] ?? [] as $doc) {
        if (($doc['document_type'] ?? '') !== $type) {
            continue;
        }

        $path = __DIR__ . '/../../' . $doc['file_path'];
        if (!is_file($path)) {
            return '';
        }

        return 'data:' . $doc['mime_type'] . ';base64,' . base64_encode(file_get_contents($path));
    }

    return '';
}

function admission_slip_html(array $a, string $passport, string $qr): string
{
    $name = trim(($a['surname'] ?? '') . ' ' . ($a['first_name'] ?? '') . ' ' . ($a['other_name'] ?? ''));
    $rows = [
        'Application Number' => $a['application_no'] ?? '',
        'Registration Number' => $a['registration_no'] ?? '',
        'Admission Session' => $a['academic_session_name'] ?? '',
        'Full Name' => $name,
        'Gender' => $a['gender'] ?? '',
        'Date of Birth' => $a['date_of_birth'] ?? '',
        'Phone' => $a['contact_phone'] ?: ($a['applicant_phone'] ?? ''),
        'Email' => $a['contact_email'] ?: ($a['applicant_email'] ?? ''),
        'Institution' => $a['institution_name'] ?? '',
        'Programme' => $a['programme_name'] ?? '',
        'Department' => $a['department_name'] ?? '',
        'Mode of Entry' => $a['mode_of_entry'] ?? '',
        'Status' => $a['form_status'] ?? ''
    ];

    $table = '';
    foreach ($rows as $label => $value) {
        $table .= '<tr><th>' . e($label) . '</th><td>' . e($value) . '</td></tr>';
    }

    $passportHtml = $passport
        ? '<img class="passport" src="' . $passport . '" alt="Passport">'
        : '<div class="passport placeholder">Passport</div>';
    $qrHtml = $qr
        ? '<img class="qr" src="' . $qr . '" alt="QR Code">'
        : '';

    return '<!doctype html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: DejaVu Sans, Arial, sans-serif; color:#111827; font-size:12px; }
                .header { border-bottom:2px solid #1f2937; padding-bottom:14px; margin-bottom:18px; }
                h1 { margin:0; font-size:22px; text-transform:uppercase; }
                h2 { margin:5px 0 0; font-size:15px; color:#4b5563; }
                .top { display:table; width:100%; }
                .left,.right { display:table-cell; vertical-align:top; }
                .right { text-align:right; width:130px; }
                .passport { width:105px; height:125px; object-fit:cover; border:1px solid #d1d5db; }
                .placeholder { display:inline-block; line-height:125px; text-align:center; color:#9ca3af; }
                table { width:100%; border-collapse:collapse; margin-top:14px; }
                th,td { border:1px solid #d1d5db; padding:8px; text-align:left; }
                th { width:32%; background:#f3f4f6; }
                .qr { width:92px; height:92px; }
                .footer { position:fixed; bottom:24px; left:0; right:0; border-top:1px solid #d1d5db; padding-top:8px; font-size:11px; color:#374151; }
            </style>
        </head>
        <body>
            <div class="header top">
                <div class="left">
                    <h1>Application Slip</h1>
                    <h2>Online Admission Portal</h2>
                </div>
                <div class="right">' . $passportHtml . '</div>
            </div>
            <table>' . $table . '</table>
            <div style="margin-top:18px;">
                <strong>Verification QR</strong><br>' . $qrHtml . '
            </div>
            <div class="footer">
                Applicant must present originals and hard copies of all uploaded credentials during screening.
            </div>
        </body>
        </html>';
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
