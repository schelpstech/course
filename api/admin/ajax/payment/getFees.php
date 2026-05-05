<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();

$data = $model->getRows('school_fee_settings', [
    'select' => "
        school_fee_settings.*,
        semesters.name AS semester_name,
        academic_sessions.name AS session_name,
        department.code AS department_name,
        levels.code AS level_name
    ",
    'join' => [
        'semesters' => 'ON semesters.id = school_fee_settings.semester_id',
        'academic_sessions' => 'ON academic_sessions.id = semesters.session_id',
        'department' => 'ON department.id = school_fee_settings.department_id',
        'levels' => 'ON levels.id = school_fee_settings.level_id'
    ],
    'order_by' => 'school_fee_settings.id DESC'
]);

echo json_encode([
    'data' => $data ?: []
]);

exit;