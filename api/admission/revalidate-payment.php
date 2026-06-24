<?php

require_once __DIR__ . '/bootstrap.php';

admission_require_post();

$paymentId = (int)($_POST['payment_id'] ?? 0);

try {

    $stmt = $db->prepare("
        SELECT *
        FROM admission_payments
        WHERE id = ?
    ");

    $stmt->execute([$paymentId]);

    $payment = $stmt->fetch();

    if (!$payment) {
        throw new Exception('Payment not found.');
    }

    if (empty($payment['reference'])) {
        throw new Exception('No payment reference found.');
    }

    $result = $paystack->verifyTransaction(
        $payment['reference']
    );

    if (
        !empty($result['status'])
        &&
        ($result['data']['status'] ?? '') === 'success'
    ) {

        $stmt = $db->prepare("
            UPDATE admission_payments
            SET
                status='paid',
                paid_at=NOW(),
                paystack_payload=?
            WHERE id=?
        ");

        $stmt->execute([
            json_encode($result),
            $paymentId
        ]);

        admission_json([
            'status'=>true,
            'message'=>'Payment successfully confirmed.'
        ]);
    }

    throw new Exception(
        'Transaction not yet confirmed by Paystack.'
    );

} catch(Throwable $e){

    admission_json([
        'status'=>false,
        'message'=>$e->getMessage()
    ],422);
}