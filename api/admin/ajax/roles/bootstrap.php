<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requirePermission('manage_roles');

echo json_encode([
    'status' => true,
    'permissions' => $rbac->getPermissionsGrouped()
]);
exit;
