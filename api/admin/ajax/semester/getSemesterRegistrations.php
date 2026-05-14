<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin(); // 🔐  FIREWALL

$session_id  = $_GET['session_id'] ?? null;
$semester_id = $_GET['semester_id'] ?? null;

// 🚨 Force selection (important)
if (empty($session_id) || empty($semester_id)) {
    echo json_encode(["data" => []]);
    exit;
}

$sql = "
SELECT 
    u.id AS student_id,
    u.name,
    u.email,
    s.matric_no,

    s.institution_id,
    s.programme_id,
    s.level_id,

    i.name AS institution,
    p.code AS program,
    l.code AS level,

    COALESCE(sr.receipt_uploaded, 0) AS receipt_uploaded,
    COALESCE(sr.payment_confirmed, 0) AS payment_confirmed,
    COALESCE(sr.course_fee_paid, 0) AS course_fee_paid,
    COALESCE(sr.courses_registered, 0) AS courses_registered,

    CASE 
        WHEN sr.courses_registered = 1 THEN 'Completed'
        WHEN sr.course_fee_paid = 1 THEN 'Awaiting Registration'
        WHEN sr.payment_confirmed = 1 THEN 'Awaiting Course Fee'
        WHEN sr.receipt_uploaded = 1 THEN 'Awaiting Confirmation'
        ELSE 'Not Started'
    END AS status

FROM users u

LEFT JOIN students s ON s.student_id = u.id
LEFT JOIN institutions i ON i.id = s.institution_id
LEFT JOIN programmes p ON p.id = s.programme_id
LEFT JOIN levels l ON l.id = s.level_id

LEFT JOIN semesterregistration sr 
    ON sr.student_id = u.id
    AND sr.session_id = :session_id
    AND sr.semester_id = :semester_id

WHERE u.role = 'student'

ORDER BY u.name ASC
";

$params = [
    'session_id' => $session_id,
    'semester_id' => $semester_id
];

$data = $model->query($sql, $params);

echo json_encode(["data" => $data]);