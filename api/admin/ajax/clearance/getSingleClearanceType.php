<?php

require_once '../../../../start.inc.php';

header('Content-Type: application/json');

$utility->requireAdmin();

$id = $_POST['id'] ?? 0;

if (empty($id)) {

    exit(json_encode([
        'status' => 'error',
        'message' => 'Invalid clearance type ID'
    ]));
}

try {

    $stmt = $db->prepare("
        SELECT *
        FROM clearance_types
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$id]);

    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {

        exit(json_encode([
            'status' => 'error',
            'message' => 'Clearance type not found'
        ]));
    }

    echo json_encode([
        'status' => 'success',
        'data' => $record
    ]);

} catch (Exception $e) {

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}