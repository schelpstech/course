<?php
require_once '../../start.inc.php';

header('Content-Type: application/json');

$response = [
    'status' => false,
    'message' => 'Unable to mark announcement as read.',
    'csrf_token' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request.');
    }

    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        throw new Exception('Student login required.');
    }

    if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'announcement_read')) {
        throw new Exception('Invalid or expired request.');
    }

    $announcementId = (int)($_POST['announcement_id'] ?? 0);

    if ($announcementId < 1) {
        throw new Exception('Invalid announcement.');
    }

    $announcementService->markRead($announcementId, (int)$_SESSION['user_id']);

    $response['status'] = true;
    $response['message'] = 'Announcement marked as read.';
} catch (Throwable $e) {
    $response['message'] = $e->getMessage();
}

$response['csrf_token'] = $utility->generateCsrf('announcement_read');
echo json_encode($response);
exit;
