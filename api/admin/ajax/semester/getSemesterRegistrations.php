<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();

// -------------------------
// DataTables parameters
// -------------------------
$draw   = $_GET['draw'] ?? 1;
$start  = $_GET['start'] ?? 0;
$length = $_GET['length'] ?? 10;
$search = $_GET['search']['value'] ?? '';

$session_id  = $_GET['session_id'] ?? null;
$semester_id = $_GET['semester_id'] ?? null;

// -------------------------
// Validate filters
// -------------------------
if (empty($session_id) || empty($semester_id)) {
    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => []
    ]);
    exit;
}

// -------------------------
// BASE QUERY (FROM + JOINS)
// -------------------------
$baseSql = "
FROM users u

LEFT JOIN students s 
    ON s.student_id = u.id

LEFT JOIN institutions i 
    ON i.id = s.institution_id

LEFT JOIN programmes p 
    ON p.id = s.programme_id

LEFT JOIN levels l 
    ON l.id = s.level_id

LEFT JOIN semesterregistration sr 
    ON sr.student_id = u.id
    AND sr.session_id = :session_id
    AND sr.semester_id = :semester_id

WHERE u.role = 'student'
";

// -------------------------
// SEARCH FILTER
// -------------------------
$searchSql = "";
$params = [
    'session_id' => $session_id,
    'semester_id' => $semester_id
];

if (!empty($search)) {
    $searchSql = "
        AND (
            u.name LIKE :search
            OR u.email LIKE :search
            OR s.matric_no LIKE :search
        )
    ";
    $params['search'] = "%$search%";
}

// -------------------------
// TOTAL RECORDS (ALL STUDENTS)
// -------------------------
$total = $model->query("
    SELECT COUNT(*) as total 
    FROM users u 
    WHERE u.role = 'student'
")[0]['total'];

// -------------------------
// FILTERED COUNT
// -------------------------
$filtered = $model->query("
    SELECT COUNT(*) as total
    $baseSql
    $searchSql
", $params)[0]['total'];

// -------------------------
// DATA QUERY
// -------------------------
$dataSql = "
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

$baseSql
$searchSql

ORDER BY u.name ASC
LIMIT $start, $length
";

// -------------------------
// EXECUTE DATA QUERY
// -------------------------
$data = $model->query($dataSql, $params);

// -------------------------
// RESPONSE (DataTables format)
// -------------------------
echo json_encode([
    "draw" => intval($draw),
    "recordsTotal" => intval($total),
    "recordsFiltered" => intval($filtered),
    "data" => $data
]);