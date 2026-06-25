<?php
require_once __DIR__ . '/bootstrap.php';

$rows = $admission->applications([
    'status' => $_GET['status'] ?? '',
    'session_id' => $_GET['session_id'] ?? '',
    'institution_id' => $_GET['institution_id'] ?? '',
    'programme_id' => $_GET['programme_id'] ?? '',
    'department_id' => $_GET['department_id'] ?? '',
    'gender' => $_GET['gender'] ?? '',
    'state' => $_GET['state'] ?? '',
    'mode_of_entry' => $_GET['mode_of_entry'] ?? '',
    'payment_status' => $_GET['payment_status'] ?? '',
    'search' => $_GET['search'] ?? ''
]);

$data = [];
foreach ($rows as $row) {
    $status = htmlspecialchars($row['form_status'], ENT_QUOTES, 'UTF-8');
    $name = $row['applicant_name'] ?: 'Incomplete Profile';
    $payment = '<span class="badge bg-light text-dark">Application ' . htmlspecialchars($row['application_payment_status'] ?? 'unpaid', ENT_QUOTES, 'UTF-8') . '</span>';
    if (in_array($row['form_status'], ['Offered Admission', 'Accepted'], true)) {
        $payment .= '<br>' . (($row['acceptance_payment_status'] ?? '') === 'paid'
            ? '<span class="badge bg-success">Acceptance Paid</span>'
            : '<span class="badge bg-warning text-dark">Acceptance Outstanding</span>');
    }

    $applicationId = (int) $row['id'];
    $canMigrate = $row['form_status'] === 'Accepted' && (($row['acceptance_payment_status'] ?? '') === 'paid');
    $migrationAction = $canMigrate
        ? '<li><button class="dropdown-item migrateAdmissionApplicant" type="button" data-id="' . $applicationId . '">Migrate to Student</button></li>'
        : '<li><button class="dropdown-item disabled" type="button" disabled>Migrate to Student</button></li>';
    $actions = '
        <div class="dropdown admission-row-actions">
            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                Actions
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><button class="dropdown-item viewAdmissionApplication" type="button" data-id="' . $applicationId . '">View Full Record</button></li>
                <li><hr class="dropdown-divider"></li>
                <li><button class="dropdown-item admissionAction" type="button" data-id="' . $applicationId . '" data-action="pending">Mark Pending Review</button></li>
                <li><button class="dropdown-item admissionAction" type="button" data-id="' . $applicationId . '" data-action="review">Move Under Review</button></li>
                <li><button class="dropdown-item admissionAction" type="button" data-id="' . $applicationId . '" data-action="recommend">Recommend</button></li>
                <li><button class="dropdown-item admissionAction" type="button" data-id="' . $applicationId . '" data-action="approve">Offer Admission</button></li>
                <li><button class="dropdown-item admissionAction" type="button" data-id="' . $applicationId . '" data-action="accept">Mark Accepted</button></li>
                <li><button class="dropdown-item admissionAction" type="button" data-id="' . $applicationId . '" data-action="reverse">Reverse Decision</button></li>
                <li><button class="dropdown-item admissionAction" type="button" data-id="' . $applicationId . '" data-action="allow_edit">Allow Applicant Edit</button></li>
                <li><button class="dropdown-item text-danger admissionAction" type="button" data-id="' . $applicationId . '" data-action="reject">Reject</button></li>
                <li><button class="dropdown-item admissionAction" type="button" data-id="' . $applicationId . '" data-action="remark">Add Remark</button></li>
                <li><hr class="dropdown-divider"></li>
                ' . $migrationAction . '
                <li><a class="dropdown-item" target="_blank" href="../api/admission/download-slip.php?application_id=' . $applicationId . '">Download Slip</a></li>
                <li><a class="dropdown-item" target="_blank" href="../api/admission/download-letter.php?application_id=' . $applicationId . '">Download Letter</a></li>
            </ul>
        </div>
    ';

    $data[] = [
        'applicant' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '<br><small>' . htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') . '</small>',
        'numbers' => htmlspecialchars($row['application_no'], ENT_QUOTES, 'UTF-8') . '<br><small>' . htmlspecialchars($row['registration_no'] ?: 'No registration no', ENT_QUOTES, 'UTF-8') . '</small>',
        'programme' => htmlspecialchars(($row['institution_name'] ?: '-') . ' / ' . ($row['programme_name'] ?: '-') . ' / ' . ($row['department_name'] ?: '-'), ENT_QUOTES, 'UTF-8')
            . '<br><small>' . htmlspecialchars($row['mode_of_entry'] ?: 'Mode not selected', ENT_QUOTES, 'UTF-8') . '</small>',
        'session' => htmlspecialchars($row['session_name'], ENT_QUOTES, 'UTF-8'),
        'status' => '<span class="badge bg-info">' . $status . '</span><br>' . $payment,
        'submitted' => htmlspecialchars($row['submitted_at'] ?: $row['created_at'], ENT_QUOTES, 'UTF-8'),
        'actions' => $actions
    ];
}

admission_admin_json(['data' => $data]);
