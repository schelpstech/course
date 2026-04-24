<?php
require_once '../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$data = [];

$institutions = $model->getRows("institutions", ['order_by' => 'id DESC']);

    // ✅ SAFETY CHECK (CRITICAL FIX)
    if (!is_array($institutions) || empty($institutions)) {
        echo json_encode($response);
        exit;
    }

foreach ($institutions as $inst) {

    $isActive = $inst['is_active'] ? true : false;

    $statusBadge = '<span class="badge '.($isActive ? 'bg-success' : 'bg-danger').'">'
        .($isActive ? 'Active' : 'Disabled').
    '</span>';

    $toggleBtn = '<button class="btn btn-sm toggleBtn '.($isActive ? 'btn-success' : 'btn-danger').'" 
        data-id="'.$inst['id'].'">
        '.($isActive ? 'Disable' : 'Enable').'
    </button>';

    $data[] = [
        'logo' => '<img src="../uploads/logo/'.$inst['inst_logo'].'" 
                    style="width:40px;height:40px;border-radius:50%;">',

        'name' => htmlspecialchars($inst['name']),
        'email' => htmlspecialchars($inst['inst_email']),
        'address' => htmlspecialchars($inst['inst_address']),

        'status' => $statusBadge,

        'actions' => '
            <button class="btn btn-sm btn-primary editBtn"
                data-id="'.$inst['id'].'"
                data-name="'.htmlspecialchars($inst['name']).'"
                data-email="'.htmlspecialchars($inst['inst_email']).'"
                data-address="'.htmlspecialchars($inst['inst_address']).'"
                data-slogan="'.htmlspecialchars($inst['code']).'">
                Edit
            </button><br><br>

            <button class="btn btn-sm btn-danger deleteBtn"
                data-id="'.$inst['id'].'">
                Delete
            </button><br><br>

            '.$toggleBtn.'
        '
    ];
}

echo json_encode(['data' => $data]);
exit;