<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('create_result_config');

$rows = $model->query("
    SELECT rc.*, s.name AS session_name, sem.name AS semester_name
    FROM result_config rc
    JOIN academic_sessions s ON s.id = rc.academic_session_id
    JOIN semesters sem ON sem.id = rc.semester_id
    ORDER BY rc.id DESC
") ?: [];

$data = [];

foreach ($rows as $row) {
    $entry = [];
    $entry[] = (int)$row['ca_entry_enabled'] === 1 ? 'CA' : '';
    $entry[] = (int)$row['exam_entry_enabled'] === 1 ? 'Exam' : '';
    $entry = array_filter($entry);

    $statusClass = match ($row['status']) {
        'active' => 'success',
        'inactive' => 'secondary',
        default => 'warning'
    };

    $data[] = [
        'session' => htmlspecialchars($row['session_name']),
        'semester' => htmlspecialchars($row['semester_name']),
        'ca' => number_format((float)$row['ca_max_score'], 2),
        'exam' => number_format((float)$row['exam_max_score'], 2),
        'total' => number_format((float)$row['total_max_score'], 2),
        'entry' => htmlspecialchars($entry ? implode(' + ', $entry) : 'Disabled'),
        'publication' => (int)$row['result_publication_enabled'] === 1
            ? '<span class="badge bg-success">Enabled</span>'
            : '<span class="badge bg-secondary">Disabled</span>',
        'deadline' => !empty($row['submission_deadline']) ? date('d M Y, h:i A', strtotime($row['submission_deadline'])) : 'Not set',
        'status' => '<span class="badge bg-' . $statusClass . '">' . ucfirst($row['status']) . '</span>',
        'actions' => '
            <button type="button" class="btn btn-primary btn-sm editResultConfig" data-id="' . (int)$row['id'] . '">
                <i class="ph ph-pencil-simple"></i>
            </button>
        '
    ];
}

echo json_encode(['data' => $data]);
exit;
