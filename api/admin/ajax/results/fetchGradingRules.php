<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_grading_rules');

$rows = $model->query("
    SELECT gr.*, i.name AS institution_name
    FROM grading_rules gr
    JOIN institutions i ON i.id = gr.institution_id
    ORDER BY i.name ASC, gr.min_score DESC
") ?: [];

$data = [];

foreach ($rows as $row) {
    $data[] = [
        'institution' => htmlspecialchars($row['institution_name']),
        'range' => number_format((float)$row['min_score'], 2) . ' - ' . number_format((float)$row['max_score'], 2),
        'grade' => htmlspecialchars($row['letter_grade']),
        'point' => number_format((float)$row['grade_point'], 2),
        'remark' => htmlspecialchars($row['remark'] ?? ''),
        'status' => (int)$row['status'] === 1
            ? '<span class="badge bg-success">Active</span>'
            : '<span class="badge bg-danger">Disabled</span>',
        'actions' => '
            <button type="button" class="btn btn-primary btn-sm editGradingRule" data-id="' . (int)$row['id'] . '">
                <i class="ph ph-pencil-simple"></i>
            </button>
        '
    ];
}

echo json_encode(['data' => $data]);
exit;
