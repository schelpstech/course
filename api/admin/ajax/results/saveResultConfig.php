<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('create_result_config');

$response = [
    'status' => false,
    'message' => 'Unable to save result configuration.',
    'csrf_token' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request.');
    }

    if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'result_config_save')) {
        throw new Exception('Invalid or expired request.');
    }

    $id = (int)($_POST['id'] ?? 0);
    $sessionId = (int)($_POST['academic_session_id'] ?? 0);
    $semesterId = (int)($_POST['semester_id'] ?? 0);
    $caMax = (float)($_POST['ca_max_score'] ?? 0);
    $examMax = (float)($_POST['exam_max_score'] ?? 0);
    $totalMax = (float)($_POST['total_max_score'] ?? 0);
    $deadlineInput = trim($_POST['submission_deadline'] ?? '');
    $deadline = $deadlineInput !== '' ? date('Y-m-d H:i:s', strtotime($deadlineInput)) : null;
    $status = $_POST['status'] ?? 'draft';

    if (!$sessionId || !$semesterId) {
        throw new Exception('Academic session and semester are required.');
    }

    if ($caMax < 0 || $examMax < 0 || $totalMax <= 0) {
        throw new Exception('Score limits must be valid positive values.');
    }

    if (abs(($caMax + $examMax) - $totalMax) > 0.01) {
        throw new Exception('CA and Exam marks must add up to the total mark.');
    }

    if (!in_array($status, ['draft', 'active', 'inactive'], true)) {
        throw new Exception('Invalid configuration status.');
    }

    $oldValue = $id ? $model->queryOne("
        SELECT *
        FROM result_config
        WHERE id = :id
        LIMIT 1
    ", ['id' => $id]) : null;

    $data = [
        'academic_session_id' => $sessionId,
        'semester_id' => $semesterId,
        'ca_max_score' => $caMax,
        'exam_max_score' => $examMax,
        'total_max_score' => $totalMax,
        'ca_entry_enabled' => (int)($_POST['ca_entry_enabled'] ?? 0) === 1 ? 1 : 0,
        'exam_entry_enabled' => (int)($_POST['exam_entry_enabled'] ?? 0) === 1 ? 1 : 0,
        'result_publication_enabled' => (int)($_POST['result_publication_enabled'] ?? 0) === 1 ? 1 : 0,
        'gpa_enabled' => (int)($_POST['gpa_enabled'] ?? 1) === 1 ? 1 : 0,
        'editing_enabled' => (int)($_POST['editing_enabled'] ?? 1) === 1 ? 1 : 0,
        'submission_deadline' => $deadline,
        'grace_period' => max(0, (int)($_POST['grace_period'] ?? 0)),
        'remarks' => trim($_POST['remarks'] ?? ''),
        'status' => $status
    ];

    $model->beginTransaction();

    if ($status === 'active') {
        $db->prepare("
            UPDATE result_config
            SET status = 'inactive'
            WHERE academic_session_id = :session_id
            AND semester_id = :semester_id
            AND status = 'active'
            AND id <> :id
        ")->execute([
            'session_id' => $sessionId,
            'semester_id' => $semesterId,
            'id' => $id
        ]);
    }

    if ($id > 0) {
        $model->update('result_config', $data, ['id' => $id]);
        $configId = $id;
    } else {
        $data['created_by'] = $_SESSION['admin_id'] ?? null;
        $configId = (int)$model->insert_data('result_config', $data);
    }

    $rbac->logAudit($id ? 'Result configuration updated' : 'Result configuration created', 'result_config', (string)$configId, $oldValue, $data);
    $model->commit();

    $response['status'] = true;
    $response['message'] = $id ? 'Result configuration updated successfully.' : 'Result configuration created successfully.';
} catch (Throwable $e) {
    $model->rollBack();
    $response['message'] = $e->getMessage();
}

$response['csrf_token'] = $utility->generateCsrf('result_config_save');
echo json_encode($response);
exit;
