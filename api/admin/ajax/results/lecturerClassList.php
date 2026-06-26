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
    $students = $resultService->registeredStudents($allocation);
    $data = [];

    foreach ($students as $student) {
        $passport = !empty($student['passport'])
            ? '../' . ltrim($student['passport'], '/')
            : '../assets/images/user/avatar-1.jpg';
        $gender = $student['gender'] === '1' ? 'Male' : ($student['gender'] === '2' ? 'Female' : 'N/A');

        $data[] = [
            'passport' => '<img src="' . htmlspecialchars($passport) . '" style="width:38px;height:38px;border-radius:50%;object-fit:cover;">',
            'matric_no' => htmlspecialchars($student['matric_no']),
            'name' => htmlspecialchars(trim($student['first_name'] . ' ' . $student['other_name'] . ' ' . $student['last_name'])),
            'gender' => htmlspecialchars($gender),
            'programme' => htmlspecialchars($student['programme_name']),
            'department' => htmlspecialchars($student['department_name']),
            'level' => htmlspecialchars($student['level_name']),
            'registration_status' => '<span class="badge bg-primary">' . ucfirst($student['approval_status']) . '</span>'
        ];
    }

    echo json_encode(['status' => true, 'data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage(), 'data' => []]);
}
exit;
