<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();

$data = $model->getRows('institution_payment_terms', [
    'select' => "
        institution_payment_terms.*,
        institutions.name AS institution_name
    ",
    'join' => [
        'institutions' => 'ON institutions.id = institution_payment_terms.institution_id'
    ],
    'order_by' => 'institution_payment_terms.id DESC'
]);

echo json_encode(['data' => $data]);