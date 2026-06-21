<?php
require_once __DIR__ . '/bootstrap.php';

$rows = $admission->applications([
    'status' => $_GET['status'] ?? '',
    'session_id' => $_GET['session_id'] ?? ''
]);

$data = [];
foreach ($rows as $row) {
    $status = htmlspecialchars($row['form_status'], ENT_QUOTES, 'UTF-8');
    $name = $row['applicant_name'] ?: 'Incomplete Profile';
    $payment = ($row['acceptance_payment_status'] ?? '') === 'paid'
        ? '<span class="badge bg-success">Acceptance Paid</span>'
        : '<span class="badge bg-warning text-dark">Acceptance Outstanding</span>';

    $actions = '
        <button class="btn btn-sm btn-outline-primary admissionAction" data-id="' . (int) $row['id'] . '" data-action="review">Review</button>
        <button class="btn btn-sm btn-outline-success admissionAction" data-id="' . (int) $row['id'] . '" data-action="recommend">Recommend</button>
        <button class="btn btn-sm btn-success admissionAction" data-id="' . (int) $row['id'] . '" data-action="approve">Approve</button>
        <button class="btn btn-sm btn-danger admissionAction" data-id="' . (int) $row['id'] . '" data-action="reject">Reject</button>
        <a class="btn btn-sm btn-outline-secondary mt-1" target="_blank" href="../api/admission/download-slip.php?application_id=' . (int) $row['id'] . '">Slip</a>
        <a class="btn btn-sm btn-outline-secondary mt-1" target="_blank" href="../api/admission/download-letter.php?application_id=' . (int) $row['id'] . '">Letter</a>
    ';

    $data[] = [
        'applicant' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '<br><small>' . htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') . '</small>',
        'numbers' => htmlspecialchars($row['application_no'], ENT_QUOTES, 'UTF-8') . '<br><small>' . htmlspecialchars($row['registration_no'] ?: 'No registration no', ENT_QUOTES, 'UTF-8') . '</small>',
        'programme' => htmlspecialchars(($row['institution_name'] ?: '-') . ' / ' . ($row['programme_name'] ?: '-') . ' / ' . ($row['department_name'] ?: '-'), ENT_QUOTES, 'UTF-8'),
        'session' => htmlspecialchars($row['session_name'], ENT_QUOTES, 'UTF-8'),
        'status' => '<span class="badge bg-info">' . $status . '</span><br>' . $payment,
        'submitted' => htmlspecialchars($row['submitted_at'] ?: $row['created_at'], ENT_QUOTES, 'UTF-8'),
        'actions' => $actions
    ];
}

admission_admin_json(['data' => $data]);
