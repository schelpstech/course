<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$semesters = $model->getRows(
    "semesters",
    [
        'select' => "
            semesters.id AS id,
            academic_sessions.id AS session_id,
            semesters.name AS semester_name,
            academic_sessions.name AS academic_sessions_name
        ",
        'join' => [
            'academic_sessions' => 'ON academic_sessions.id = semesters.session_id'
        ],
        'where' => ['academic_sessions.is_active' => 1]
    ]
);

echo json_encode([
    'data' => $semesters
]);

exit;