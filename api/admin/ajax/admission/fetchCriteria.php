<?php
require_once __DIR__ . '/bootstrap.php';

$rows = $admission->criteriaList([
    'institution_id' => $_GET['institution_id'] ?? '',
    'programme_id' => $_GET['programme_id'] ?? '',
    'status' => $_GET['status'] ?? ''
]);

$data = [];
foreach ($rows as $row) {
    $documents = array_map(
        fn($key) => '<span class="badge bg-light text-dark border me-1 mb-1">' . htmlspecialchars($row['document_labels'][$key] ?? $key, ENT_QUOTES, 'UTF-8') . '</span>',
        $row['required_documents']
    );

    $payload = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
    $statusClass = $row['status'] === 'active' ? 'bg-success' : 'bg-secondary';
    $toggleStatus = $row['status'] === 'active' ? 'inactive' : 'active';

    $data[] = [
        'institution' => htmlspecialchars($row['institution_name'], ENT_QUOTES, 'UTF-8'),
        'programme' => htmlspecialchars($row['programme_name'], ENT_QUOTES, 'UTF-8'),
        'olevel' => 'Credits: <strong>' . (int) $row['minimum_credits'] . '</strong><br>'
            . 'Sittings: <strong>' . (int) $row['maximum_sittings'] . '</strong><br>'
            . '<small>' . htmlspecialchars(implode(', ', $row['compulsory_subjects']) ?: 'No compulsory subjects', ENT_QUOTES, 'UTF-8') . '</small>',
        'jamb' => 'Min Score: <strong>' . (int) $row['minimum_jamb_score'] . '</strong><br>'
            . ($row['jamb_registration_required'] ? '<span class="badge bg-info">JAMB Reg Required</span>' : '<span class="badge bg-light text-dark">JAMB Reg Optional</span>'),
        'documents' => implode(' ', $documents),
        'status' => '<span class="badge ' . $statusClass . '">' . htmlspecialchars(ucfirst($row['status']), ENT_QUOTES, 'UTF-8') . '</span>',
        'actions' => '
            <button class="btn btn-sm btn-outline-primary editAdmissionCriteria" data-criteria="' . $payload . '">Edit</button>
            <button class="btn btn-sm btn-outline-secondary duplicateAdmissionCriteria" data-id="' . (int) $row['id'] . '">Duplicate</button>
            <button class="btn btn-sm btn-outline-warning toggleAdmissionCriteria" data-id="' . (int) $row['id'] . '" data-status="' . $toggleStatus . '">' . htmlspecialchars(ucfirst($toggleStatus), ENT_QUOTES, 'UTF-8') . '</button>
        '
    ];
}

admission_admin_json(['data' => $data]);
