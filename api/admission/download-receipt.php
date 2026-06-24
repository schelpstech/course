<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../../classes/fpdf/fpdf.php';

$applicantId = (int)($_SESSION['admission_applicant_id'] ?? 0);

if (!$applicantId) {
    http_response_code(403);
    exit('Access denied');
}

$paymentId = (int)($_GET['id'] ?? 0);

if ($paymentId <= 0) {
    exit('Invalid payment selected.');
}

/*
|--------------------------------------------------------------------------
| FETCH PAYMENT
|--------------------------------------------------------------------------
*/
$stmt = $db->prepare("
    SELECT
        p.*,
        a.application_no,
        a.registration_no,
        ap.email,
        ap.phone,
        acs.name AS academic_session_name
    FROM admission_payments p

    INNER JOIN admission_applications a
        ON a.id = p.application_id

    INNER JOIN applicants ap
        ON ap.id = p.applicant_id

    INNER JOIN admission_sessions s
        ON s.id = p.admission_session_id

    INNER JOIN academic_sessions acs
        ON  acs.id = s.session_id

    WHERE
        p.id = ?
        AND p.applicant_id = ?
");

$stmt->execute([
    $paymentId,
    $applicantId
]);

$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    exit('Receipt not found.');
}

/*
|--------------------------------------------------------------------------
| ONLY PAID TRANSACTIONS
|--------------------------------------------------------------------------
*/
if ($payment['status'] !== 'paid') {
    exit('Receipt is only available for successful payments.');
}

/*
|--------------------------------------------------------------------------
| PDF RECEIPT
|--------------------------------------------------------------------------
*/
$pdf = new FPDF();
$pdf->AddPage();

$logo = __DIR__ . '/../../assets/images/logo.png';

if (file_exists($logo)) {
    $pdf->Image($logo, 10, 10, 22);
}

/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/
$pdf->SetFont('Arial', 'B', 18);
$pdf->Cell(0, 10, 'ADMISSION PAYMENT RECEIPT', 0, 1, 'C');

$pdf->Ln(4);

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'Official Payment Confirmation', 0, 1, 'C');

$pdf->Ln(10);

/*
|--------------------------------------------------------------------------
| RECEIPT INFORMATION
|--------------------------------------------------------------------------
*/
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Transaction Information', 0, 1);

$pdf->SetFont('Arial', '', 11);

$pdf->Cell(60, 8, 'Invoice Number');
$pdf->Cell(0, 8, ': ' . $payment['invoice_no'], 0, 1);

$pdf->Cell(60, 8, 'Application Number');
$pdf->Cell(0, 8, ': ' . $payment['application_no'], 0, 1);

$pdf->Cell(60, 8, 'Registration Number');
$pdf->Cell(
    0,
    8,
    ': ' . ($payment['registration_no'] ?: 'Pending'),
    0,
    1
);

$pdf->Cell(60, 8, 'Academic Session');
$pdf->Cell(
    0,
    8,
    ': ' . $payment['academic_session_name'],
    0,
    1
);

$pdf->Cell(60, 8, 'Payment Type');
$pdf->Cell(
    0,
    8,
    ': ' . ucwords(str_replace('_', ' ', $payment['payment_type'])),
    0,
    1
);

$pdf->Cell(60, 8, 'Reference');
$pdf->Cell(
    0,
    8,
    ': ' . ($payment['reference'] ?: 'N/A'),
    0,
    1
);

$pdf->Cell(60, 8, 'Payment Date');
$pdf->Cell(
    0,
    8,
    ': ' . date(
        'd M Y h:i A',
        strtotime($payment['paid_at'])
    ),
    0,
    1
);

$pdf->Ln(6);

/*
|--------------------------------------------------------------------------
| APPLICANT DETAILS
|--------------------------------------------------------------------------
*/
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Applicant Information', 0, 1);

$pdf->SetFont('Arial', '', 11);

$pdf->Cell(60, 8, 'Email Address');
$pdf->Cell(0, 8, ': ' . $payment['email'], 0, 1);

$pdf->Cell(60, 8, 'Phone Number');
$pdf->Cell(0, 8, ': ' . $payment['phone'], 0, 1);

$pdf->Ln(6);

/*
|--------------------------------------------------------------------------
| PAYMENT SUMMARY
|--------------------------------------------------------------------------
*/
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Payment Summary', 0, 1);

$pdf->SetFont('Arial', '', 11);

$pdf->Cell(60, 8, 'Amount Paid');
$pdf->Cell(
    0,
    8,
    ': NGN ' . number_format((float)$payment['amount'], 2),
    0,
    1
);

$pdf->Cell(60, 8, 'Status');
$pdf->Cell(0, 8, ': PAID', 0, 1);

$pdf->Ln(10);

/*
|--------------------------------------------------------------------------
| RECEIPT BOX
|--------------------------------------------------------------------------
*/
$pdf->SetFillColor(240, 248, 255);

$pdf->SetFont('Arial', 'B', 12);

$pdf->Cell(
    190,
    12,
    'PAYMENT SUCCESSFULLY RECEIVED',
    1,
    1,
    'C',
    true
);

$pdf->Ln(8);

/*
|--------------------------------------------------------------------------
| DECLARATION
|--------------------------------------------------------------------------
*/
$pdf->SetFont('Arial', '', 10);

$pdf->MultiCell(
    0,
    6,
    "This receipt confirms that payment has been successfully received and processed for the admission application. Please retain this document for future reference."
);

$pdf->Ln(15);

/*
|--------------------------------------------------------------------------
| SIGNATURE AREA
|--------------------------------------------------------------------------
*/
$pdf->Cell(80, 8, '________________________', 0, 0);
$pdf->Cell(30);
$pdf->Cell(80, 8, '________________________', 0, 1);

$pdf->Cell(80, 8, 'Applicant', 0, 0);
$pdf->Cell(30);
$pdf->Cell(80, 8, 'Admissions Office', 0, 1);

$pdf->Ln(15);

/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/
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
    1,
    'C'
);

/*
|--------------------------------------------------------------------------
| DOWNLOAD
|--------------------------------------------------------------------------
*/
$pdf->Output(
    'D',
    'Receipt-' . $payment['invoice_no'] . '.pdf'
);

exit;