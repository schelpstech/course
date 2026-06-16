<?php

require_once '../../../../start.inc.php';

header('Content-Type: application/json');

$utility->requireAdmin();

try {

    $sql = "
        SELECT
            ct.*,
            i.name AS institution_name
        FROM clearance_types ct
        INNER JOIN institutions i
            ON i.id = ct.institution_id
        ORDER BY i.name ASC, ct.name ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute();

    echo json_encode([
        'status' => 'success',
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch (Exception $e) {

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}