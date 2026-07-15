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
$clearanceWorkflow = trim((string)($_GET['clearance_workflow'] ?? ''));

// -------------------------
// Build WHERE conditions
// -------------------------
$conditions = [];
$params = [];

if ($session) {
    $conditions[] = 'cr.session = :session_id';
    $params['session_id'] = $session;
}

if ($semester) {
    $conditions[] = 'cr.semester = :semester_id';
    $params['semester_id'] = $semester;
}

if ($department) {
    $conditions[] = 's.department_id = :department_id';
    $params['department_id'] = $department;
}

if ($level) {
    $conditions[] = 's.level_id = :level_id';
    $params['level_id'] = $level;
}

if ($clearanceWorkflow === 'awaiting_clearance') {
    $conditions[] = "cr.approval_status = 'approved'";
    $conditions[] = "COALESCE(sc.clearance_status, 'pending') <> 'approved'";
} elseif ($clearanceWorkflow === 'cleared') {
    $conditions[] = "cr.approval_status = 'approved'";
    $conditions[] = "COALESCE(sc.clearance_status, 'pending') = 'approved'";
}

// -------------------------
// Search filter
// -------------------------
if (!empty($search)) {
    $conditions[] = "(
        s.first_name LIKE :search_first_name OR
        s.last_name LIKE :search_last_name OR
        s.other_name LIKE :search_other_name OR
        s.matric_no LIKE :search_matric_no OR
        cr.approval_status LIKE :search_approval_status OR
        d.code LIKE :search_department OR
        l.code LIKE :search_level
    )";

    $searchValue = '%' . $search . '%';
    $params['search_first_name'] = $searchValue;
    $params['search_last_name'] = $searchValue;
    $params['search_other_name'] = $searchValue;
    $params['search_matric_no'] = $searchValue;
    $params['search_approval_status'] = $searchValue;
    $params['search_department'] = $searchValue;
    $params['search_level'] = $searchValue;
}

$where = empty($conditions)
    ? ''
    : 'WHERE ' . implode(' AND ', $conditions);

$clearanceJoins = "
    LEFT JOIN clearance_types ct
        ON ct.institution_id = s.institution_id
        AND ct.code = 'COURSE REGISTRATION'
        AND ct.status = 1
    LEFT JOIN student_clearances sc
        ON sc.semester_registration_id = cr.semester_registration_id
        AND sc.clearance_type_id = ct.id
";

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
    SELECT COUNT(DISTINCT cr.course_regID) as total
    FROM course_registered cr
    JOIN students s ON s.student_id = cr.student_id
    JOIN department d ON d.id = s.department_id
    JOIN levels l ON l.id = s.level_id
    $clearanceJoins
    $where
", $params)[0]['total'] ?? 0;

// -------------------------
// MAIN DATA QUERY
// -------------------------
$dataQuery = $model->query("
    SELECT

        cr.course_regID,
        cr.semester_registration_id,

        cr.approval_status,
        cr.total_units,
        cr.created_at,

        s.first_name,
        s.last_name,
        s.other_name,
        s.matric_no,

        d.code AS department,
        l.code AS level,

        COALESCE(sc.clearance_status,'pending')
            AS clearance_status,

        (
            SELECT COUNT(*)
            FROM registered_course rc
            WHERE rc.course_regID = cr.course_regID
        ) AS courses_count

    FROM course_registered cr

    JOIN students s
        ON s.student_id = cr.student_id

    JOIN department d
        ON d.id = s.department_id

    JOIN levels l
        ON l.id = s.level_id

    $clearanceJoins

    $where

    ORDER BY cr.created_at DESC

    LIMIT $start, $length
", $params);

// -------------------------
// FORMAT RESPONSE
// -------------------------
$data = [];

foreach ($dataQuery as $row) {
    $data[] = [

        "course_regID" => $row['course_regID'],

        "semester_registration_id" =>
        $row['semester_registration_id'],

        "name" =>
        trim(
            "{$row['first_name']} {$row['other_name']} {$row['last_name']}"
        ),

        "matric_no" => $row['matric_no'],

        "department" => $row['department'],

        "level" => $row['level'],

        "courses_count" => $row['courses_count'],

        "status" =>
        ucfirst($row['approval_status']),

        "clearance_status" =>
        $row['clearance_status']
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
