<?php
require_once '../../start.inc.php';


//fetch programmes based on institution
if ($_GET['action'] == 'getProgrammes') {

    $institution_id = $_GET['institution_id'] ?? null;

    $data = $model->getRows('programmes', [
        'where' => ['institution_id' => $institution_id]
    ]);

    echo json_encode($data);
} 
elseif ($_GET['action'] == 'getProgrammeMeta') {

    $programme_id = $_GET['programme_id'] ?? null;

    // Fetch Departments
    $departments = $model->getRows('departments', [
        'where' => ['programme_id' => $programme_id]
    ]);

    // Fetch Levels (now dependent on programme)
    $levels = $model->getRows('levels', [
        'where' => ['programme_id' => $programme_id]
    ]);

    echo json_encode([
        'departments' => $departments ?: [],
        'levels' => $levels ?: []
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    exit;
}
