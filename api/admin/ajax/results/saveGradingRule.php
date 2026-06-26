<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_grading_rules');

$response = [
    'status' => false,
    'message' => 'Unable to save grading rule.',
    'csrf_token' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request.');
    }

    if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'grading_rule_save')) {
        throw new Exception('Invalid or expired request.');
    }

    $id = (int)($_POST['id'] ?? 0);
    $institutionId = (int)($_POST['institution_id'] ?? 0);
    $minScore = (float)($_POST['min_score'] ?? 0);
    $maxScore = (float)($_POST['max_score'] ?? 0);
    $letterGrade = strtoupper(trim($_POST['letter_grade'] ?? ''));
    $gradePoint = (float)($_POST['grade_point'] ?? 0);
    $remark = trim($_POST['remark'] ?? '');
    $status = (int)($_POST['status'] ?? 1) === 1 ? 1 : 0;

    if (!$institutionId) {
        throw new Exception('Institution is required.');
    }

    if ($minScore < 0 || $maxScore < 0 || $minScore > $maxScore) {
        throw new Exception('Enter a valid score range.');
    }

    if ($letterGrade === '') {
        throw new Exception('Letter grade is required.');
    }

    if ($gradePoint < 0) {
        throw new Exception('Grade point cannot be negative.');
    }

    if ($status === 1) {
        $overlap = $model->queryOne("
            SELECT id
            FROM grading_rules
            WHERE institution_id = :institution_id
            AND status = 1
            AND id <> :id
            AND NOT (max_score < :min_score OR min_score > :max_score)
            LIMIT 1
        ", [
            'institution_id' => $institutionId,
            'id' => $id,
            'min_score' => $minScore,
            'max_score' => $maxScore
        ]);

        if ($overlap) {
            throw new Exception('This score range overlaps with an active grading rule.');
        }
    }

    $oldValue = $id ? $model->queryOne("
        SELECT *
        FROM grading_rules
        WHERE id = :id
        LIMIT 1
    ", ['id' => $id]) : null;

    $data = [
        'institution_id' => $institutionId,
        'min_score' => $minScore,
        'max_score' => $maxScore,
        'letter_grade' => $letterGrade,
        'grade_point' => $gradePoint,
        'remark' => $remark,
        'status' => $status
    ];

    if ($id > 0) {
        $model->update('grading_rules', $data, ['id' => $id]);
        $ruleId = $id;
    } else {
        $data['created_by'] = $_SESSION['admin_id'] ?? null;
        $ruleId = (int)$model->insert_data('grading_rules', $data);
    }

    $rbac->logAudit($id ? 'Grading rule updated' : 'Grading rule created', 'grading_rule', (string)$ruleId, $oldValue, $data);

    $response['status'] = true;
    $response['message'] = $id ? 'Grading rule updated successfully.' : 'Grading rule created successfully.';
} catch (Throwable $e) {
    $response['message'] = $e->getMessage();
}

$response['csrf_token'] = $utility->generateCsrf('grading_rule_save');
echo json_encode($response);
exit;
