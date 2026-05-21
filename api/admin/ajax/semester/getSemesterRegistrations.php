<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();

// -------------------------
// DataTables params
// -------------------------
$draw   = (int)($_GET['draw'] ?? 1);
$start  = (int)($_GET['start'] ?? 0);
$length = (int)($_GET['length'] ?? 10);
$search = trim($_GET['search']['value'] ?? '');

// -------------------------
// Filters
// -------------------------
$session_id     = (int)($_GET['session_id'] ?? 0);
$semester_id    = (int)($_GET['semester_id'] ?? 0);
$institution_id = (int)($_GET['institution_id'] ?? 0);
$department_id  = (int)($_GET['department_id'] ?? 0);
$level_id       = (int)($_GET['level_id'] ?? 0);
$status         = trim($_GET['status'] ?? '');

// -------------------------
// BASE SQL (NO WHERE HERE)
// -------------------------
$baseSql = "
FROM users u
LEFT JOIN students s ON s.student_id = u.id
LEFT JOIN institutions i ON i.id = s.institution_id
LEFT JOIN department d ON d.id = s.department_id
LEFT JOIN programmes p ON p.id = s.programme_id
LEFT JOIN levels l ON l.id = s.level_id
LEFT JOIN semesterregistration sr 
    ON sr.student_id = u.id
    AND sr.session_id = :session_id
    AND sr.semester_id = :semester_id
";

// -------------------------
// FILTERS
// -------------------------
$filters = ["u.role = 'student'"];

$params = [
    'session_id'  => $session_id,
    'semester_id' => $semester_id
];

// Institution
if ($institution_id) {
    $filters[] = "s.institution_id = :institution_id";
    $params['institution_id'] = $institution_id;
}

// Department
if ($department_id) {
    $filters[] = "s.department_id = :department_id";
    $params['department_id'] = $department_id;
}

// Level
if ($level_id) {
    $filters[] = "s.level_id = :level_id";
    $params['level_id'] = $level_id;
}

// Status filter
if (!empty($status)) {
    switch ($status) {
        case 'completed':
            $filters[] = "sr.courses_registered = 1";
            break;

        case 'awaiting_registration':
            $filters[] = "sr.course_fee_paid = 1 AND sr.courses_registered = 0";
            break;

        case 'awaiting_fee':
            $filters[] = "sr.payment_confirmed = 1 AND sr.course_fee_paid = 0";
            break;

        case 'awaiting_confirmation':
            $filters[] = "sr.receipt_uploaded = 1 AND sr.payment_confirmed = 0";
            break;

        case 'not_started':
            $filters[] = "(sr.receipt_uploaded IS NULL OR sr.receipt_uploaded = 0)";
            break;
    }
}

if (!empty($search)) {
    $filters[] = "(
        u.name LIKE :search
        OR u.email LIKE :search
        OR s.matric_no LIKE :search
        OR i.name LIKE :search
        OR d.name LIKE :search
        OR l.code LIKE :search

        OR (
            CASE 
                WHEN sr.courses_registered = 1 THEN 'Completed'
                WHEN sr.course_fee_paid = 1 THEN 'Awaiting Registration'
                WHEN sr.payment_confirmed = 1 THEN 'Awaiting Course Fee'
                WHEN sr.receipt_uploaded = 1 THEN 'Awaiting Confirmation'
                ELSE 'Not Started'
            END
        ) LIKE :search
    )";

    $params['search'] = "%$search%";
}

// -------------------------
// FINAL WHERE
// -------------------------
$where = "WHERE " . implode(" AND ", $filters);

// -------------------------
// TOTAL (ALL STUDENTS)
// -------------------------
$totalResult = $model->query("
    SELECT COUNT(*) as total
    FROM users u
    WHERE u.role = 'student'
");

$total = $totalResult[0]['total'] ?? 0;

// -------------------------
// FILTERED COUNT
// -------------------------
$filteredResult = $model->query("
    SELECT COUNT(*) as total
    $baseSql
    $where
", $params);

$filtered = $filteredResult[0]['total'] ?? 0;

// -------------------------
// DATA QUERY
// -------------------------
$dataSql = "
SELECT 
    u.id AS student_id,
    u.name,
    u.email,
    s.matric_no,

    i.name AS institution,
    d.name AS department,
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
$where

ORDER BY u.name ASC
LIMIT $start, $length
";

// Execute
$data = $model->query($dataSql, $params) ?? [];

// -------------------------
// RESPONSE
// -------------------------
echo json_encode([
    "draw" => $draw,
    "recordsTotal" => $total,
    "recordsFiltered" => $filtered,
    "data" => $data
]);