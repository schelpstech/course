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
// CLEAN UP OLD PAYMENTS (PENDING + FAILED)
// ==============================
$oldPaymentsCount = $model->getRows('payments', [
    'where' => [
        'student_id' => $_SESSION['user_id'],
        'semester_id' => $activeSemester['id'],
        'payment_type' => 'course_reg',
        'status' => ['pending', 'failed'] // ✅ FIXED
    ],
    'return_type' => 'count'
]);

if ($oldPaymentsCount > 0) {

    $model->delete('payments', [
        'student_id' => $_SESSION['user_id'],
        'semester_id' => $activeSemester['id'],
        'payment_type' => 'course_reg',
        'status' => ['pending', 'failed'] // ✅ FIXED
    ]);
}

// CALCULATE FEES
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

$charges = $subtotal * 0.015; // 1.5% Paystack charge
$total = $subtotal + $charges + 100; // Adding 100 to cover any rounding issues and ensure we don't undercharge
$amount = $total * 100;
$email = $userData['email'] ?? '';

$reference = 'PAY-' . strtoupper(bin2hex(random_bytes(8)));
$callback_url = "http://localhost/course/api/student/paymentCallback.php";

// SAVE PAYMENT (PENDING)
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

$metadata = [
    "student_id" => $_SESSION['user_id'],
    "session_id" => $activeSession['id'],
    "semester_id" => $activeSemester['id']
];
// INITIALIZE PAYSTACK
try {
    $response = $paystack->initializePayment($email, $amount, $callback_url, $reference, $metadata);

    if (!$response || !$response['status']) {
        throw new Exception("Payment initialization failed");
    }

    header("Location: " . $response['data']['authorization_url']);
    exit;
} catch (Exception $e) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => $e->getMessage()
    ];

    header("Location: ../../controller/router.php?pageid=" . $utility->secureEncode('paycourseform'));
    exit;
}
