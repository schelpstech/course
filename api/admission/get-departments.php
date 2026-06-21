<?php
require_once __DIR__ . '/bootstrap.php';

$programmeId = (int) ($_GET['programme_id'] ?? 0);
admission_json([
    'status' => true,
    'data' => $programmeId ? $admission->departmentsByProgramme($programmeId) : []
]);
