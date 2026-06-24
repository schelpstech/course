<?php
require_once __DIR__ . '/bootstrap.php';

try {
    $reference = $_GET['reference'] ?? '';
    if ($reference === '') {
        throw new Exception('Missing payment reference.');
    }

    $verification = $paystack->verifyTransaction($reference);
    $result = $admission->applyPaymentVerification($reference, $verification);

    $_SESSION['toast'] = [
        'type' => $result['success'] ? 'success' : 'error',
        'message' => $result['success']
            ? 'Payment confirmed successfully.'
            : 'Payment could not be confirmed.'
    ];
} catch (Throwable $e) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => $e->getMessage()];
}

header('Location: ../../admission/form.php');
exit;
