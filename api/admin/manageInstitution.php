<?php

require_once '../../start.inc.php';
require_once '../adminQuery.php';

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Invalid action'];

try {

    $action = $_POST['action'] ?? '';

    switch ($action) {

        case 'create':

            $name    = trim($_POST['name'] ?? '');
            $email   = trim($_POST['email'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $slogan  = trim($_POST['slogan'] ?? '');

            if (!$name || !$email || !$address) {
                throw new Exception('All required fields must be filled');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email');
            }

            $logoName = null;

            if (!empty($_FILES['logo']['name'])) {

                $uploadDir = "../../uploads/logo/";

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileTmp  = $_FILES['logo']['tmp_name'];
                $fileName = time() . '_' . basename($_FILES['logo']['name']);
                $target   = $uploadDir . $fileName;

                $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];

                if (!in_array($_FILES['logo']['type'], $allowed)) {
                    throw new Exception('Invalid image format');
                }

                if (!move_uploaded_file($fileTmp, $target)) {
                    throw new Exception('Failed to upload logo');
                }

                $logoName = $fileName;
            }

            $id = $model->insert_data('institutions', [
                'name' => $name,
                'inst_email' => $email,
                'inst_address' => $address,
                'code' => $slogan,
                'inst_logo' => $logoName,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $response = [
                'status' => 'success',
                'message' => 'Institution created successfully',
                'id' => $id
            ];

            break;

        case 'update':

            $model->update('institutions', [
                'name' => $_POST['name'],
                'inst_email' => $_POST['email'],
                'inst_address' => $_POST['address'],
                'code' => $_POST['slogan']
            ], ['id' => $_POST['id']]);

            $response = ['status' => 'success', 'message' => 'Updated successfully'];
            break;

        case 'delete':

            $model->delete('institutions', ['id' => $_POST['id']]);

            $response = ['status' => 'success', 'message' => 'Deleted successfully'];
            break;

        case 'toggle':

            $inst = $model->getById('institutions', $_POST['id']);

            if (!$inst) {
                throw new Exception('Record not found');
            }

            $new = $inst['is_active'] ? 0 : 1;

            $model->update('institutions', [
                'is_active' => $new
            ], ['id' => $_POST['id']]);

            $response = ['status' => 'success', 'message' => 'Status updated'];
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
exit;