<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();

$data = $model->getRows('school_fee_settings', [
    'select' => "
        school_fee_settings.*,
        academic_sessions.name AS session_name,
        department.name AS department_name,
        levels.name AS level_name
    ",
    'join' => [
        'academic_sessions' => 'ON academic_sessions.id = school_fee_settings.session_id',
        'department' => 'ON department.id = school_fee_settings.department_id',
        'levels' => 'ON levels.id = school_fee_settings.level_id'
    ],
    'order_by' => 'school_fee_settings.id DESC'
]);

echo json_encode([
    'data' => $data ?: []
]);

exit;