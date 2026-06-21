<?php
require_once __DIR__ . '/bootstrap.php';

admission_require_post();
admission_require_csrf($admission);
$application = admission_current_application($admission);

try {
    $type = $_POST['payment_type'] ?? 'application_fee';
    $url = $admission->initializePaystackPayment((int) $application['id'], $type, $paystack);

    admission_json([
        'status' => true,
        'message' => 'Redirecting to Paystack.',
        'authorization_url' => $url
    ]);
} catch (Throwable $e) {
    admission_json(['status' => false, 'message' => $e->getMessage()], 422);
}
