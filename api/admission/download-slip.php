<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../classes/fpdf/fpdf.php';
try {

    $applicationId = (int)($_GET['application_id'] ?? 0);

    if (!empty($_SESSION['admin_id']) && $applicationId) {
        $full = $admission->getFullApplication($applicationId);
    } else {
        $application = admission_current_application($admission);
        $full = $admission->getFullApplication((int)$application['id']);
    }

    if (!$full) {
        throw new Exception('Application not found.');
    }

    if (empty($full['registration_no'])) {
        throw new Exception(
            'Application slip is available only after final submission.'
        );
    }

    $passportPath = '';
    foreach ($full['documents'] ?? [] as $doc) {
        if (($doc['document_type'] ?? '') === 'passport') {

            $path = __DIR__ . '/../../' . $doc['file_path'];

            if (file_exists($path)) {
                $passportPath = $path;
            }

            break;
        }
    }

    $qrTempFile = '';

    $qrDataUri = $admission->qrDataUri($full);

    if (!empty($qrDataUri)) {

        $qrBase64 = preg_replace(
            '#^data:image/\w+;base64,#i',
            '',
            $qrDataUri
        );

        $qrTempFile =
            sys_get_temp_dir() .
            '/qr_' .
            uniqid() .
            '.png';

        file_put_contents(
            $qrTempFile,
            base64_decode($qrBase64)
        );
    }

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(10, 10, 10);
    $pdf->AddPage();

    /*
|--------------------------------------------------------------------------
| LOGO
|--------------------------------------------------------------------------
*/
    $logo = __DIR__ . '/../../assets/images/logo.png';

    if (file_exists($logo)) {
        $pdf->Image($logo, 10, 10, 22);
    }

    /*
|--------------------------------------------------------------------------
| PASSPORT (TOP RIGHT)
|--------------------------------------------------------------------------
*/
    $passportX = 165;
    $passportY = 10;

    $pdf->Rect($passportX, $passportY, 30, 30);

    if (!empty($passportPath) && file_exists($passportPath)) {
        $pdf->Image(
            $passportPath,
            $passportX + 1,
            $passportY + 1,
            28,
            29
        );
    }

    /*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/
    $pdf->SetXY(35, 10);

    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(
        125,
        8,
        'ONLINE ADMISSION PORTAL',
        0,
        1,
        'C'
    );

    $pdf->SetX(35);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(
        125,
        6,
        $full['academic_session_name'] ?? '',
        0,
        1,
        'C'
    );

    $pdf->SetX(35);
    $pdf->SetFont('Arial', 'B', 15);
    $pdf->Cell(
        125,
        8,
        'APPLICATION SLIP',
        0,
        1,
        'C'
    );

    $pdf->Ln(6);

    /*
|--------------------------------------------------------------------------
| APPLICANT INFORMATION
|--------------------------------------------------------------------------
*/
    $pdf->SetFillColor(34, 69, 150);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 11);

    $pdf->Cell(
        185,
        8,
        'APPLICANT INFORMATION',
        1,
        1,
        'L',
        true
    );

    $pdf->SetTextColor(0, 0, 0);

    $name = trim(
        ($full['surname'] ?? '') . ' ' .
            ($full['first_name'] ?? '') . ' ' .
            ($full['other_name'] ?? '')
    );

    $rows = [
        'Application Number' => $full['application_no'] ?? '',
        'Registration Number' => $full['registration_no'] ?? '',
        'Full Name' => $name,
        'Gender' => $full['gender'] ?? '',
        'Date of Birth' => $full['date_of_birth'] ?? '',
        'Phone Number' => $full['contact_phone']
            ?: ($full['applicant_phone'] ?? ''),
        'Email Address' => $full['contact_email']
            ?: ($full['applicant_email'] ?? ''),
        'Admission Session' => $full['academic_session_name'] ?? '',
        'Application Status' => $full['form_status'] ?? ''
    ];

    foreach ($rows as $label => $value) {

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(50, 7, $label, 1);

        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(135, 7, $value, 1);

        $pdf->Ln();
    }

    /*
|--------------------------------------------------------------------------
| PROGRAMME INFORMATION
|--------------------------------------------------------------------------
*/
    $pdf->Ln(4);

    $pdf->SetFillColor(34, 69, 150);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 11);

    $pdf->Cell(
        185,
        8,
        'PROGRAMME INFORMATION',
        1,
        1,
        'L',
        true
    );

    $pdf->SetTextColor(0, 0, 0);

    $programmeRows = [
        'Institution' => $full['institution_name'] ?? '',
        'Programme' => $full['programme_name'] ?? '',
        'Department' => $full['department_name'] ?? '',
        'Mode of Entry' => $full['mode_of_entry'] ?? ''
    ];

    foreach ($programmeRows as $label => $value) {

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(50, 7, $label, 1);

        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(135, 7, $value, 1);

        $pdf->Ln();
    }

    /*
|--------------------------------------------------------------------------
| NOTICE + QR CODE
|--------------------------------------------------------------------------
*/
    $pdf->Ln(6);

    $noticeY = $pdf->GetY();

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(
        120,
        6,
        'IMPORTANT NOTICE',
        0,
        1
    );

    $pdf->SetFont('Arial', '', 8);

    $pdf->MultiCell(
        120,
        5,
        'Applicants are required to present this application slip together with original copies of all uploaded credentials during screening and verification. Failure to provide required documents may affect admission consideration.'
    );

    if ($qrTempFile && file_exists($qrTempFile)) {

        $pdf->Image(
            $qrTempFile,
            155,
            $noticeY,
            30,
            30
        );

        unlink($qrTempFile);
    }

    /*
|--------------------------------------------------------------------------
| SIGNATURES
|--------------------------------------------------------------------------
*/
    $pdf->SetY(200);

    $pdf->Cell(70, 6, '______________________');
    $pdf->Cell(45);
    $pdf->Cell(70, 6, '______________________');

    $pdf->Ln(8);

    $pdf->Cell(70, 6, 'Applicant Signature');
    $pdf->Cell(45);
    $pdf->Cell(70, 6, 'Registrar / Admissions Officer');

    /*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/
    $pdf->SetY(260);

    $pdf->SetFont('Arial', 'I', 8);

    $pdf->Cell(
        0,
        5,
        'Generated on ' . date('d M Y h:i:s A'),
        0,
        1,
        'C'
    );

    $pdf->Cell(
        0,
        5,
        'This is a computer-generated receipt and does not require a signature.',
        0,
    );

    $pdf->Output(
        'D',
        'Application-Slip-' .
            $full['application_no'] .
            '.pdf'
    );
} catch (Throwable $e) {

    http_response_code(422);

    echo htmlspecialchars(
        $e->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );
}
