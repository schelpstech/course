<?php
require_once '../../../../start.inc.php';
require_once '../../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$utility->requireAdmin();

function result_file_data_uri(?string $path): string
{
    if (!$path) {
        return '';
    }

    $fullPath = realpath(__DIR__ . '/../../../../' . ltrim($path, '/'));

    if (!$fullPath || !is_file($fullPath)) {
        return '';
    }

    $mime = mime_content_type($fullPath) ?: 'image/png';
    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
}

try {
    $allocationId = (int)($_GET['allocation_id'] ?? 0);
    $allocation = $resultService->assertLecturerAllocationAccess($allocationId, [
        'view_results',
        'download_reports',
        'submit_scores'
    ]);
    $payload = $resultService->scoresheetRows($allocationId);
    $sheet = $payload['sheet'];

    if (!in_array($sheet['ca_status'], ['submitted', 'approved'], true) || !in_array($sheet['exam_status'], ['submitted', 'approved'], true)) {
        throw new Exception('Scoresheet download is available only after CA and Exam scores have been submitted.');
    }

    $logoData = result_file_data_uri('uploads/logo/' . ($allocation['inst_logo'] ?? ''));
    $qrText = rtrim(BASE_URL, '/') . '/verifier.php?sheet=' . $sheet['id'];
    $qrData = isset($qrcode) && $qrcode ? $qrcode->generateQRCode($qrText) : '';

    $rows = '';
    $sn = 1;

    foreach ($payload['students'] as $student) {
        $score = $student['score'] ?? [];
        $rows .= '<tr>
            <td>' . $sn++ . '</td>
            <td>' . htmlspecialchars($student['matric_no']) . '</td>
            <td>' . htmlspecialchars(trim($student['first_name'] . ' ' . $student['other_name'] . ' ' . $student['last_name'])) . '</td>
            <td style="text-align:center;">' . htmlspecialchars((string)($score['ca_score'] ?? '')) . '</td>
            <td style="text-align:center;">' . htmlspecialchars((string)($score['exam_score'] ?? '')) . '</td>
            <td style="text-align:center;">' . htmlspecialchars((string)($score['total_score'] ?? '')) . '</td>
            <td style="text-align:center;">' . htmlspecialchars((string)($score['letter_grade'] ?? '')) . '</td>
            <td>' . htmlspecialchars((string)($score['remark'] ?? '')) . '</td>
        </tr>';
    }

    $html = '
    <html>
    <head>
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #d1d5db; padding: 6px; }
            th { background: #eef2ff; }
            .header { text-align: center; margin-bottom: 16px; }
            .logo { width: 70px; height: 70px; object-fit: contain; }
            .meta td { border: none; padding: 3px 4px; }
            .signatures td { height: 55px; vertical-align: bottom; text-align: center; }
            .qr { width: 80px; height: 80px; }
        </style>
    </head>
    <body>
        <div class="header">
            ' . ($logoData ? '<img class="logo" src="' . $logoData . '">' : '') . '
            <h2>' . htmlspecialchars($allocation['institution_name']) . '</h2>
            <h3>Submitted Scoresheet</h3>
        </div>

        <table class="meta">
            <tr>
                <td><strong>Course:</strong> ' . htmlspecialchars($allocation['course_code'] . ' - ' . $allocation['course_title']) . '</td>
                <td><strong>Lecturer:</strong> ' . htmlspecialchars($allocation['lecturer_name']) . '</td>
            </tr>
            <tr>
                <td><strong>Department:</strong> ' . htmlspecialchars($allocation['department_name']) . '</td>
                <td><strong>Level:</strong> ' . htmlspecialchars($allocation['level_name']) . '</td>
            </tr>
            <tr>
                <td><strong>Academic Session:</strong> ' . htmlspecialchars($allocation['session_name']) . '</td>
                <td><strong>Semester:</strong> ' . htmlspecialchars($allocation['semester_name']) . '</td>
            </tr>
            <tr>
                <td><strong>CA Submitted:</strong> ' . htmlspecialchars((string)$sheet['ca_submitted_at']) . '</td>
                <td><strong>Exam Submitted:</strong> ' . htmlspecialchars((string)$sheet['exam_submitted_at']) . '</td>
            </tr>
        </table>

        <br>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Matric No</th>
                    <th>Student Name</th>
                    <th>CA</th>
                    <th>Exam</th>
                    <th>Total</th>
                    <th>Grade</th>
                    <th>Remark</th>
                </tr>
            </thead>
            <tbody>' . $rows . '</tbody>
        </table>

        <br><br>

        <table class="signatures">
            <tr>
                <td>Lecturer Signature</td>
                <td>HOD Approval</td>
                <td>Registrar / Result Officer</td>
                <td>' . ($qrData ? '<img class="qr" src="' . $qrData . '"><br>' : '') . 'Verification QR</td>
            </tr>
        </table>
    </body>
    </html>';

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $filename = preg_replace('/[^A-Za-z0-9_-]+/', '_', $allocation['course_code'] . '_' . $allocation['session_name'] . '_' . $allocation['semester_name']) . '_scoresheet.pdf';
    $dompdf->stream($filename, ['Attachment' => true]);
} catch (Throwable $e) {
    http_response_code(400);
    echo htmlspecialchars($e->getMessage());
}
exit;
