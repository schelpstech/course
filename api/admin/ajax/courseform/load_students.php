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
$session    = (int)($_GET['session_id'] ?? 0);
$semester   = (int)($_GET['semester_id'] ?? 0);
$department = (int)($_GET['department_id'] ?? 0);
$level      = (int)($_GET['level_id'] ?? 0);

// -------------------------
// Build WHERE conditions
// -------------------------
$where = "WHERE 1=1";

if ($session) {
    $where .= " AND cr.session = $session";
}

if ($semester) {
    $where .= " AND cr.semester = $semester";
}

if ($department) {
    $where .= " AND s.department_id = $department";
}

if ($level) {
    $where .= " AND s.level_id = $level";
}

// -------------------------
// Search filter
// -------------------------
if (!empty($search)) {
    $where .= " AND (
        s.first_name LIKE '%$search%' OR
        s.last_name LIKE '%$search%' OR
        s.other_name LIKE '%$search%' OR
        s.matric_no LIKE '%$search%' OR
        cr.approval_status LIKE '%$search%' OR
        d.code LIKE '%$search%' OR
        l.code LIKE '%$search%'
    )";
}

// -------------------------
// TOTAL RECORDS (no filters)
// -------------------------
$totalRecords = $model->query("
    SELECT COUNT(*) as total
    FROM course_registered cr
")[0]['total'] ?? 0;

// -------------------------
// FILTERED RECORDS
// -------------------------
$filteredRecords = $model->query("
    SELECT COUNT(*) as total
    FROM course_registered cr
    JOIN students s ON s.student_id = cr.student_id
    JOIN department d ON d.id = s.department_id
    JOIN levels l ON l.id = s.level_id
    $where
")[0]['total'] ?? 0;

// -------------------------
// MAIN DATA QUERY
// -------------------------
$dataQuery = $model->query("
    SELECT 
        cr.course_regID,
        cr.approval_status,
        cr.total_units,
        cr.created_at,

        s.first_name,
        s.last_name,
        s.other_name,
        s.matric_no,

        d.code AS department,
        l.code AS level,

        (
            SELECT COUNT(*) 
            FROM registered_course rc
            WHERE rc.course_regID = cr.course_regID
        ) AS courses_count

    FROM course_registered cr
    JOIN students s ON s.student_id = cr.student_id
    JOIN department d ON d.id = s.department_id
    JOIN levels l ON l.id = s.level_id

    $where
    ORDER BY cr.created_at DESC
    LIMIT $start, $length
");

// -------------------------
// FORMAT RESPONSE
// -------------------------
$data = [];

foreach ($dataQuery as $row) {
    $data[] = [
        "course_regID"   => $row['course_regID'],
        "name"           => trim("{$row['first_name']} {$row['other_name']} {$row['last_name']}"),
        "matric_no"      => $row['matric_no'],
        "department"     => $row['department'],
        "level"          => $row['level'],
        "courses_count"  => $row['courses_count'],
        "status"         => ucfirst($row['approval_status'])
    ];
}

// -------------------------
// RESPONSE
// -------------------------
echo json_encode([
    "draw"            => $draw,
    "recordsTotal"    => $totalRecords,
    "recordsFiltered" => $filteredRecords,
    "data"            => $data
]);