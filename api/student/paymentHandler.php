<?php
require_once '../../start.inc.php';
require_once '../query.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../controller/router.php?pageid=" . $utility->secureEncode('studentDashboard'));
    exit;
}

// CSRF
if (!$utility->validateRequest($_POST['csrf_token'] ?? '', 'courseformpayment')) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Unauthorized access'
    ];
    header("Location: ../../controller/router.php?pageid=" . $utility->secureEncode('studentDashboard'));
    exit;
}

// ==============================
// CHECK EXISTING SUCCESSFUL PAYMENT
// ==============================
$existingPayment = $model->getRows('payments', [
    'where' => [
        'student_id' => $_SESSION['user_id'],
        'semester_id' => $activeSemester['id'],
        'payment_type' => 'course_reg',
        'status' => 'successful'
    ],
    'return_type' => 'single'
]);

if ($existingPayment) {
    $_SESSION['toast'] = [
        'type' => 'info',
        'message' => 'Course Registration Fee already paid for this semester'
    ];

    header("Location: ../../controller/router.php?pageid=" . $utility->secureEncode('studentDashboard'));
    exit;
}

// ==============================
// CHECK EXISTING PENDING PAYMENT
// ==============================
$pendingPayment = $model->getRows('payments', [
    'where' => [
        'student_id' => $_SESSION['user_id'],
        'semester_id' => $activeSemester['id'],
        'payment_type' => 'course_reg',
        'status' => 'pending'
    ],
    'return_type' => 'single'
]);

// ==============================
// CALCULATE FEES
// ==============================
$fees = $model->getRows('fees', [
    'where' => [
        'session_id' => $activeSession['id'],
        'semester_id' => $activeSemester['id']
    ]
]);

$subtotal = 0;
foreach ($fees as $fee) {
    $subtotal += $fee['amount'];
}

$amount = $subtotal;
$email = $userData['email'] ?? '';

// ==============================
// HANDLE PAYMENT CREATION / REUSE
// ==============================
if ($pendingPayment) {

    // 🔁 Reuse existing reference
    $reference = $pendingPayment['paymentReference'];

    // 🕒 Update timestamp
    $model->update('payments', [
        'payment_date' => date('Y-m-d'),
        'created_at'   => date('Y-m-d H:i:s'),
        'amount_paid'  => $subtotal // optional: keep amount in sync
    ], [
        'id' => $pendingPayment['id']
    ]);

    // Optional log
    $utility->logActivityUsers(
        'Reused pending payment reference: ' . $reference,
        $_SESSION['user_email'] ?? 'SYSTEM'
    );

} else {

    // 🆕 Create new payment
    $reference = 'PAY-' . strtoupper(bin2hex(random_bytes(8)));

    $paydata = [
        'student_id' => $_SESSION['user_id'],
        'paymentReference' => $reference,
        'semester_id' => $activeSemester['id'],
        'amount_paid' => $subtotal,
        'payment_type' => "course_reg",
        'payment_mode' => 'online',
        'payment_date' => date('Y-m-d'),
        'payment_proof' => null,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ];

    $insert = $model->insert_data('payments', $paydata);

    if (!$insert) {
        $_SESSION['toast'] = [
            'type' => 'error',
            'message' => 'Failed to initiate payment'
        ];
        header("Location: ../../controller/router.php?pageid=" . $utility->secureEncode('paycourseform'));
        exit;
    }

    // Optional log
    $utility->logActivityUsers(
        'Created new payment reference: ' . $reference,
        $_SESSION['user_email'] ?? 'SYSTEM'
    );
}

// ==============================
// INITIALIZE PAYSTACK
// ==============================
$callback_url = "http://localhost/course/api/student/paymentCallback.php";

$metadata = [
    "student_id" => $_SESSION['user_id'],
    "session_id" => $activeSession['id'],
    "semester_id" => $activeSemester['id']
];

try {
    $response = $paystack->initializePayment($email, $amount, $callback_url, $reference, $metadata);

    if (!$response || !$response['status']) {
        throw new Exception("Payment initialization failed");
    }

    header("Location: $response[data][authorization_url]");
    exit;

} catch (Exception $e) {

    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => $e->getMessage()
    ];

    header("Location: ../../controller/router.php?pageid=" . $utility->secureEncode('paycourseform'));
    exit;
}