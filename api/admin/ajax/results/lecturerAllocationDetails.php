<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();

try {
    $allocationId = (int)($_GET['allocation_id'] ?? 0);
    $allocation = $resultService->assertLecturerAllocationAccess($allocationId, [
        'view_results',
        'enter_ca_scores',
        'enter_exam_scores',
        'submit_scores'
    ]);
    $sheet = $resultService->ensureSheet($allocation);
    $students = $resultService->registeredStudents($allocation);

    echo json_encode([
        'status' => true,
        'allocation' => $allocation,
        'sheet' => [
            'id' => $sheet['id'],
            'ca_status' => $sheet['ca_status'],
            'exam_status' => $sheet['exam_status'],
            'moderation_status' => $sheet['moderation_status'],
            'remarks' => $sheet['remarks']
        ],
        'config' => $sheet['config'],
        'registered_students' => count($students)
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
}
exit;
