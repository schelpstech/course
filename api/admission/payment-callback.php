<?php
require_once __DIR__ . '/bootstrap.php';

try {
    $reference = $_GET['reference'] ?? '';
    if ($reference === '') {
        throw new Exception('Missing payment reference.');
    }

    $verification = $paystack->verifyTransaction($reference);
    $result = $admission->applyPaymentVerification($reference, $verification);
    $redirect = ($result['payment_type'] ?? '') === 'acceptance_fee'
        ? '../../admission/dashboard.php'
        : '../../admission/form.php';

    $_SESSION['toast'] = [
        'type' => $result['success'] ? 'success' : 'error',
        'message' => $result['success']
            ? 'Payment confirmed successfully.'
            : 'Payment could not be confirmed.'
    ];
} catch (Throwable $e) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => $e->getMessage()];
    $redirect = '../../admission/dashboard.php';
}

header('Location: ' . $redirect);
exit;
