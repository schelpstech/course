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

    if (!in_array($full['form_status'], ['Offered Admission', 'Accepted'], true)) {
        throw new Exception('Admission letter is available after admission offer.');
    }

    $letter = $admission->ensureAdmissionLetter((int) $full['id']);
    $full['letter_no'] = $letter['letter_no'];
    $full['issued_at'] = $letter['issued_at'];

    $qr = $admission->qrDataUri($full);
    $html = admission_letter_html($full, $qr);

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4');
    $dompdf->render();
    $dompdf->stream('admission-letter-' . $full['application_no'] . '.pdf', ['Attachment' => false]);
} catch (Throwable $e) {
    http_response_code(422);
    echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}

function admission_letter_html(array $a, string $qr): string
{
    $name = trim(($a['surname'] ?? '') . ' ' . ($a['first_name'] ?? '') . ' ' . ($a['other_name'] ?? ''));
    $date = !empty($a['issued_at']) ? date('F j, Y', strtotime($a['issued_at'])) : date('F j, Y');
    $qrHtml = $qr ? '<img class="qr" src="' . $qr . '" alt="QR Code">' : '';

    return '<!doctype html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: DejaVu Sans, Arial, sans-serif; color:#111827; font-size:13px; line-height:1.65; }
                .header { text-align:center; border-bottom:2px solid #111827; padding-bottom:16px; margin-bottom:28px; }
                h1 { margin:0; font-size:23px; text-transform:uppercase; }
                h2 { margin:6px 0 0; font-size:16px; color:#4b5563; }
                .meta { width:100%; margin-bottom:22px; }
                .meta td { padding:3px 0; }
                .letter-title { text-align:center; font-weight:bold; font-size:17px; text-decoration:underline; margin:22px 0; }
                .qr { width:92px; height:92px; }
                .signature { margin-top:50px; }
                .footer { position:fixed; bottom:24px; left:0; right:0; border-top:1px solid #d1d5db; padding-top:8px; font-size:11px; color:#374151; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Admission Letter</h1>
                <h2>Online Admission Portal</h2>
            </div>

            <table class="meta">
                <tr><td><strong>Letter No:</strong> ' . e($a['letter_no'] ?? '') . '</td><td style="text-align:right"><strong>Date:</strong> ' . e($date) . '</td></tr>
                <tr><td><strong>Application No:</strong> ' . e($a['application_no'] ?? '') . '</td><td style="text-align:right"><strong>Registration No:</strong> ' . e($a['registration_no'] ?? '') . '</td></tr>
            </table>

            <p>Dear <strong>' . e($name) . '</strong>,</p>
            <div class="letter-title">Offer of Provisional Admission</div>
            <p>
                I am pleased to inform you that you have been offered provisional admission into
                <strong>' . e($a['department_name'] ?? '') . '</strong>,
                <strong>' . e($a['programme_name'] ?? '') . '</strong>,
                <strong>' . e($a['institution_name'] ?? '') . '</strong>
                for the <strong>' . e($a['academic_session_name'] ?? '') . '</strong> academic session.
            </p>
            <p>
                This offer is subject to verification of your uploaded credentials and presentation
                of original documents during screening. You are required to pay the acceptance fee
                through the admission portal to complete acceptance of this offer.
            </p>
            <p><strong>Registration Number:</strong> ' . e($a['registration_no'] ?? '') . '</p>

            <div style="margin-top:20px;">
                <strong>Verification QR</strong><br>' . $qrHtml . '
            </div>

            <div class="signature">
                <p>______________________________<br>Admission Officer</p>
            </div>

            <div class="footer">
                This letter can be verified through the QR code printed above.
            </div>
        </body>
        </html>';
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
