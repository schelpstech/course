<?php
require_once __DIR__ . '/../../start.inc.php';

function admission_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function admission_require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        admission_json(['status' => false, 'message' => 'Bad request.'], 405);
    }
}

function admission_require_csrf(Admission $admission): void
{
    if (!$admission->verifyCsrf($_POST['csrf_token'] ?? '')) {
        admission_json(['status' => false, 'message' => 'Invalid or expired request.'], 403);
    }
}

function admission_require_applicant(): int
{
    if (empty($_SESSION['admission_applicant_id'])) {
        admission_json(['status' => false, 'message' => 'Applicant login required.'], 401);
    }

    return (int) $_SESSION['admission_applicant_id'];
}

function admission_current_application(Admission $admission): array
{
    $applicantId = admission_require_applicant();
    $application = $admission->getApplicationForApplicant($applicantId);

    if (!$application) {
        admission_json(['status' => false, 'message' => 'No admission application found.'], 404);
    }

    return $application;
}
