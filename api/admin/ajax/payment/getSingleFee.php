<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();

$id = $_POST['id'] ?? null;

// 🔒 Validate ID
if (!$id || !is_numeric($id)) {
    exit(json_encode([
        'status' => 'error',
        'message' => 'Invalid ID'
    ]));
}

// Fetch record
$data = $model->getRows('school_fee_settings', [
    'select' => "
        school_fee_settings.*,
        institutions.name AS institution_name,
        institutions.id AS institution_id,
        programmes.id AS programme_id,
        semesters.name AS semester_name,
        academic_sessions.name AS session_name,
        department.code AS department_name,
        levels.code AS level_name
    ",
    'join' => [
        'semesters' => 'ON semesters.id = school_fee_settings.semester_id',
        'academic_sessions' => 'ON academic_sessions.id = semesters.session_id',
        'department' => 'ON department.id = school_fee_settings.department_id',
        'programmes' => 'ON programmes.id = department.programme_id',
        'institutions' => 'ON institutions.id = programmes.institution_id',
        'levels' => 'ON levels.id = school_fee_settings.level_id'
    ],
    'return_type' => 'single'

]);

if (!$data) {
    exit(json_encode([
        'status' => 'error',
        'message' => 'Record not found'
    ]));
}

// Success
echo json_encode([
    'status' => 'success',
    'data' => $data
]);

exit;