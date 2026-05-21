<?php
require_once '../../../../start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();

// DataTables params
$draw = $_GET['draw'] ?? 1;
$start = $_GET['start'] ?? 0;
$length = $_GET['length'] ?? 10;
$searchValue = $_GET['search']['value'] ?? '';

// Filters
$session = $_GET['session_id'] ?? '';
$semester = $_GET['semester_id'] ?? '';

$where = "WHERE 1=1";

// Apply filters
if ($session) {
    $where .= " AND cr.session = '$session'";
}

if ($semester) {
    $where .= " AND cr.semester = '$semester'";
}

// Search
if (!empty($searchValue)) {
    $where .= " AND (
        s.first_name LIKE '%$searchValue%' OR
        s.matric_no LIKE '%$searchValue%'
    )";
}

// Total records
$totalQuery = $model->query("
    SELECT COUNT(*) as total
    FROM course_registered cr
    JOIN students s ON s.student_id = cr.student_id
    $where
");

$totalRecords = $totalQuery[0]['total'] ?? 0;

// Fetch paginated data
$dataQuery = $model->query("
    SELECT 
        cr.course_regID,
        cr.approval_status,
        cr.total_units,
        s.first_name,
        s.last_name,
        s.other_name,
        s.matric_no,
        s.department_id,
        s.level_id,
        d.code as department,
        l.code as level
    FROM course_registered cr
    JOIN students s ON s.student_id = cr.student_id
    JOIN department d ON d.id = s.department_id
    JOIN levels l ON l.id = s.level_id
    $where
    ORDER BY cr.created_at DESC
    LIMIT $start, $length
");

$data = [];

foreach ($dataQuery as $row) {

    // count number of courses
    $courseCount = $model->query("
        SELECT COUNT(*) as total 
        FROM registered_course 
        WHERE course_regID = {$row['course_regID']}
    ")[0]['total'] ?? 0;

    $data[] = [
        "course_regID" => $row['course_regID'],
        "name" => trim("{$row['first_name']} {$row['other_name']} {$row['last_name']}"),
        "matric_no" => $row['matric_no'],
        "department" => $row['department'],
        "level" => $row['level'],
        "courses_count" => $courseCount,
        "status" => ucfirst($row['approval_status'])
    ];
}

// Response
echo json_encode([
    "draw" => intval($draw),
    "recordsTotal" => intval($totalRecords),
    "recordsFiltered" => intval($totalRecords),
    "data" => $data
]);
