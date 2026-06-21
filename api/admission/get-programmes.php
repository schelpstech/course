<?php
require_once __DIR__ . '/bootstrap.php';

$institutionId = (int) ($_GET['institution_id'] ?? 0);
admission_json([
    'status' => true,
    'data' => $institutionId ? $admission->programmesByInstitution($institutionId) : []
]);
