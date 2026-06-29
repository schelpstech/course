<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();

try {
    $allocationId = (int)($_GET['allocation_id'] ?? 0);
    $component = strtolower($_GET['component'] ?? 'ca');

    if (!in_array($component, ['ca', 'exam'], true)) {
        throw new Exception('Invalid scoresheet component.');
    }

    $permissions = $component === 'ca'
        ? ['enter_ca_scores', 'submit_scores']
        : ['enter_exam_scores', 'submit_scores'];
    $allocation = $resultService->assertLecturerAllocationAccess($allocationId, $permissions);
    $payload = $resultService->scoresheetRows($allocationId);
    $sheet = $payload['sheet'];
    $config = $sheet['config'];
    $statusColumn = $component . '_status';
    $scoreColumn = $component . '_score';
    $maxScore = (float)$config[$component . '_max_score'];
    $entryEnabled = (int)$config[$component . '_entry_enabled'] === 1;
    $deadlinePassed = false;

    if (!empty($config['submission_deadline'])) {
        $deadlinePassed = time() > (strtotime($config['submission_deadline']) + ((int)$config['grace_period'] * 60));
    }

    $readOnly = !$entryEnabled || $deadlinePassed || in_array($sheet[$statusColumn], ['submitted', 'approved'], true);
    $data = [];

    foreach ($payload['students'] as $student) {
        $score = $student['score'] ?? [];
        $data[] = [
            'student_id' => (int)$student['score_student_id'],
            'matric_no' => htmlspecialchars($student['matric_no']),
            'name' => htmlspecialchars(trim($student['first_name'] . ' ' . $student['other_name'] . ' ' . $student['last_name'])),
            'score' => $score[$scoreColumn] ?? '',
            'total_score' => $score['total_score'] ?? '',
            'grade' => $score['letter_grade'] ?? '',
            'remark' => $score['remark'] ?? ''
        ];
    }

    echo json_encode([
        'status' => true,
        'component' => $component,
        'max_score' => $maxScore,
        'sheet_status' => $sheet[$statusColumn],
        'moderation_status' => $sheet['moderation_status'],
        'read_only' => $readOnly,
        'entry_enabled' => $entryEnabled,
        'deadline_passed' => $deadlinePassed,
        'data' => $data
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage(),
        'data' => []
    ]);
}
exit;
