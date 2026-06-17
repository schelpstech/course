<?php

require_once '../../../../start.inc.php';

header('Content-Type: application/json');

$utility->requireAdmin();

$semester_registration_id =
    (int)($_POST['semester_registration_id'] ?? 0);

if (!$semester_registration_id) {

    exit(json_encode([
        'status' => 'error',
        'message' => 'Invalid request'
    ]));
}

try {

    /**
     * -----------------------------------
     * GET COURSE REGISTRATION RECORD
     * -----------------------------------
     */
    $courseReg = $model->query("
        SELECT

            cr.course_regID,
            cr.approval_status,

            s.institution_id,

            CONCAT(
                s.first_name,' ',
                IFNULL(s.other_name,''),' ',
                s.last_name
            ) AS student_name

        FROM course_registered cr

        INNER JOIN students s
            ON s.student_id = cr.student_id

        WHERE cr.semester_registration_id =
            '{$semester_registration_id}'

        LIMIT 1
    ");

    if (!$courseReg) {

        exit(json_encode([
            'status' => 'error',
            'message' => 'Course registration record not found'
        ]));
    }

    $courseReg = $courseReg[0];

    /**
     * -----------------------------------
     * COURSE FORM MUST BE APPROVED
     * -----------------------------------
     */
    if ($courseReg['approval_status'] !== 'approved') {

        exit(json_encode([
            'status' => 'error',
            'message' =>
            'Course registration has not been approved'
        ]));
    }

    /**
     * -----------------------------------
     * GET COURSE_REG CLEARANCE TYPE
     * -----------------------------------
     */
    $clearanceType = $model->query("
        SELECT id

        FROM clearance_types

        WHERE institution_id =
            '{$courseReg['institution_id']}'

        AND code = 'COURSE REGISTRATION'

        AND status = 1

        LIMIT 1
    ");

    if (!$clearanceType) {

        exit(json_encode([
            'status' => 'error',
            'message' =>
            'COURSE REGISTRATION clearance type not configured'
        ]));
    }

    $clearance_type_id =
        $clearanceType[0]['id'];

    /**
     * -----------------------------------
     * CHECK IF ALREADY CLEARED
     * -----------------------------------
     */
    $existing = $model->query("
        SELECT id

        FROM student_clearances

        WHERE semester_registration_id =
            '{$semester_registration_id}'

        AND clearance_type_id =
            '{$clearance_type_id}'

        AND status = 'approved'

        LIMIT 1
    ");

    if ($existing) {

        exit(json_encode([
            'status' => 'error',
            'message' =>
            'Course clearance already approved'
        ]));
    }

    /**
     * -----------------------------------
     * APPROVE CLEARANCE
     * -----------------------------------
     */
    $userId = $_SESSION['admin_id'];

    $insert = $model->insert_data(
        'student_clearances',
        [
            'semester_registration_id' => $semester_registration_id,
            'clearance_type_id'        => $clearance_type_id,
            'clearance_status'                   => 'approved',
            'remark'                   => 'Course Registration Completed',
            'approved_by'              => $userId,
            'approved_at'              => date('Y-m-d H:i:s')
        ]
    );



    if (!$insert) {

        exit(json_encode([
            'status' => 'error',
            'message' =>
            'Failed to approve course clearance'
        ]));
    }

    /**
     * -----------------------------------
     * LOG ACTIVITY
     * -----------------------------------
     */
    $utility->logActivity(
        "Course Clearance Approved for "
            . $courseReg['student_name']
            . " (Semester Registration ID: "
            . $semester_registration_id
            . ")"
    );

    echo json_encode([
        'status' => 'success',
        'message' =>
        'Course clearance approved successfully'
    ]);
} catch (Exception $e) {

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
