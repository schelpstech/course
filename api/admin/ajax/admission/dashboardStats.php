<?php
require_once __DIR__ . '/bootstrap.php';

admission_admin_json([
    'status' => true,
    'data' => $admission->dashboardStats()
]);
