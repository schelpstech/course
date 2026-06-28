<?php
require_once dirname(__DIR__, 4) . '/start.inc.php';

header('Content-Type: application/json');
$utility->requireAdmin();
$rbac->requireAny(['allocate_courses', 'allocate_dept_courses']);

try {
    $lecturers = $model->query("
        SELECT
            l.id,
            CONCAT(
                TRIM(CONCAT(COALESCE(NULLIF(a.title, ''), NULLIF(l.title, ''), ''), ' ', a.fullname)),
                CASE
                    WHEN l.staff_no IS NOT NULL AND l.staff_no <> '' THEN CONCAT(' - ', l.staff_no)
                    ELSE ''
                END,
                CASE
                    WHEN d.name IS NOT NULL THEN CONCAT(' - ', d.name)
                    ELSE ''
                END,
                CASE
                    WHEN i.name IS NOT NULL THEN CONCAT(' (', i.name, ')')
                    ELSE ''
                END
            ) AS name
        FROM lecturers l
        JOIN admins a ON a.id = l.admin_id
        LEFT JOIN department d ON d.id = l.department_id
        LEFT JOIN programmes p ON p.id = d.programme_id
        LEFT JOIN institutions i ON i.id = COALESCE(l.institution_id, p.institution_id)
        WHERE l.status = 1
        AND a.ix_active = 1
        ORDER BY a.fullname ASC
    ") ?: [];

    echo json_encode(['status' => true, 'data' => $lecturers]);
} catch (Throwable $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage(), 'data' => []]);
}
exit;
