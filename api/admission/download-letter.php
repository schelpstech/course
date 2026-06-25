<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

try {
    $applicationId = (int) ($_GET['application_id'] ?? 0);
    $isAdminRequest = !empty($_SESSION['admin_id']) && $applicationId;

    if ($isAdminRequest) {
        $full = $admission->getFullApplication($applicationId);
    } else {
        $application = admission_current_application($admission);
        $full = $admission->getFullApplication((int) $application['id']);
    }

    if (!$full) {
        throw new Exception('Application not found.');
    }

    $allowedStatuses = $isAdminRequest ? ['Offered Admission', 'Accepted'] : ['Accepted'];
    if (!in_array($full['form_status'], $allowedStatuses, true)) {
        throw new Exception('Admission letter is available after the admission offer has been accepted.');
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
    $institutionName = $a['institution_name'] ?: 'Admission Office';
    $institutionAddress = trim((string) ($a['inst_address'] ?? ''));
    $institutionEmail = trim((string) ($a['inst_email'] ?? ''));
    $matricNo = $a['matric_no'] ?: 'Pending Student Migration';
    $logo = admission_letter_logo_src($a['inst_logo'] ?? '');
    $logoHtml = $logo ? '<img class="logo" src="' . e($logo) . '" alt="Institution Logo">' : '<div class="logo-placeholder"></div>';
    $qrHtml = $qr ? '<img class="qr" src="' . $qr . '" alt="QR Code">' : '';

    return '<!doctype html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                @page { margin: 36px 42px; }
                body { font-family: DejaVu Sans, Arial, sans-serif; color:#111827; font-size:12.5px; line-height:1.65; }
                .header { border-bottom:3px solid #0f172a; padding-bottom:14px; margin-bottom:22px; }
                .header-table { width:100%; border-collapse:collapse; }
                .header-table td { vertical-align:middle; }
                .logo { width:78px; height:78px; object-fit:contain; }
                .logo-placeholder { width:78px; height:78px; border:1px solid #d1d5db; border-radius:8px; }
                h1 { margin:0; font-size:22px; text-transform:uppercase; letter-spacing:0; color:#0f172a; }
                .institution-meta { margin-top:4px; color:#374151; font-size:11px; }
                .meta { width:100%; margin:18px 0 20px; border-collapse:collapse; }
                .meta td { padding:4px 0; }
                .letter-title { text-align:center; font-weight:bold; font-size:16px; text-decoration:underline; margin:20px 0; text-transform:uppercase; }
                .details { width:100%; border-collapse:collapse; margin:18px 0; }
                .details td { border:1px solid #e5e7eb; padding:8px 10px; }
                .details small { color:#6b7280; display:block; }
                .conditions { margin:16px 0; padding-left:18px; }
                .qr { width:90px; height:90px; }
                .signature { margin-top:46px; }
                .verification { margin-top:18px; display:inline-block; }
                .footer { position:fixed; bottom:20px; left:0; right:0; border-top:1px solid #d1d5db; padding-top:8px; font-size:10px; color:#374151; text-align:center; }
            </style>
        </head>
        <body>
            <div class="header">
                <table class="header-table">
                    <tr>
                        <td style="width:90px;">' . $logoHtml . '</td>
                        <td style="text-align:center;">
                            <h1>' . e($institutionName) . '</h1>
                            <div class="institution-meta">' . e($institutionAddress) . '</div>
                            <div class="institution-meta">' . e($institutionEmail) . '</div>
                        </td>
                        <td style="width:90px;"></td>
                    </tr>
                </table>
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

            <table class="details">
                <tr>
                    <td><small>Candidate Name</small><strong>' . e($name) . '</strong></td>
                    <td><small>Matric Number</small><strong>' . e($matricNo) . '</strong></td>
                </tr>
                <tr>
                    <td><small>Programme</small><strong>' . e($a['programme_name'] ?? '') . '</strong></td>
                    <td><small>Department</small><strong>' . e($a['department_name'] ?? '') . '</strong></td>
                </tr>
                <tr>
                    <td><small>Mode of Entry</small><strong>' . e($a['mode_of_entry'] ?? '') . '</strong></td>
                    <td><small>Academic Session</small><strong>' . e($a['academic_session_name'] ?? '') . '</strong></td>
                </tr>
            </table>

            <p>
                This offer is subject to verification of your uploaded credentials, presentation of
                original documents, payment of all prescribed fees, and compliance with the academic
                regulations of the institution.
            </p>

            <p><strong>Conditions of Admission</strong></p>
            <ol class="conditions">
                <li>You must present original credentials during screening and registration.</li>
                <li>You must comply with all departmental and institutional registration requirements.</li>
                <li>This provisional offer may be withdrawn if any submitted information is found to be false.</li>
            </ol>

            <div class="verification">
                <strong>Verification QR</strong><br>' . $qrHtml . '
            </div>

            <div class="signature">
                <p>______________________________<br><strong>Admission Officer</strong><br>' . e($institutionName) . '</p>
            </div>

            <div class="footer">
                This admission letter can be verified through the QR code printed above. Letter No: ' . e($a['letter_no'] ?? '') . '
            </div>
        </body>
        </html>';
}

function admission_letter_logo_src($path): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }

    $normalized = str_replace('\\', '/', $path);
    $candidates = [
        __DIR__ . '/../../' . ltrim($normalized, '/'),
        __DIR__ . '/../../uploads/logo/' . basename($normalized)
    ];

    foreach ($candidates as $candidate) {
        $absolute = realpath($candidate);
        if (!$absolute || !is_file($absolute)) {
            continue;
        }

        $extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        $mime = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ][$extension] ?? 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($absolute));
    }

    return '';
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
