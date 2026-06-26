<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();

$response = [
    'status' => false,
    'message' => 'Unable to save scores.',
    'csrf_token' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request.');
    }

    if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'lecturer_score_save')) {
        throw new Exception('Invalid or expired request.');
    }

    $allocationId = (int)($_POST['allocation_id'] ?? 0);
    $component = strtolower($_POST['component'] ?? '');
    $scores = $_POST['scores'] ?? [];

    if (!is_array($scores)) {
        $scores = [];
    }

    $resultService->saveScores($allocationId, $component, $scores, false);

    $response['status'] = true;
    $response['message'] = strtoupper($component) . ' draft saved successfully.';
} catch (Throwable $e) {
    $response['message'] = $e->getMessage();
}

$response['csrf_token'] = $utility->generateCsrf('lecturer_score_save');
echo json_encode($response);
exit;
