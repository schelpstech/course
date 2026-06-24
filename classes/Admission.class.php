<?php

class Admission
{
    private PDO $db;
    private model $model;
    private utility $utility;
    private ?QRCodeGenerator $qrcode;
    private ?mailservice $mailer;
    private array $grades = ['A1', 'B2', 'B3', 'C4', 'C5', 'C6', 'D7', 'E8', 'F9', 'ABS', 'AR'];
    private array $documentTypes = ['passport', 'birth_certificate', 'olevel_result', 'jamb_result_slip', 'previous_certificate', 'other'];

    public function __construct(PDO $db, model $model, utility $utility, ?QRCodeGenerator $qrcode = null, ?mailservice $mailer = null)
    {
        $this->db = $db;
        $this->model = $model;
        $this->utility = $utility;
        $this->qrcode = $qrcode;
        $this->mailer = $mailer;
    }

    public function csrfToken(): string
    {
        if (empty($_SESSION['admission_csrf'])) {
            $_SESSION['admission_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['admission_csrf'];
    }

    public function verifyCsrf(?string $token): bool
    {
        return !empty($token)
            && !empty($_SESSION['admission_csrf'])
            && hash_equals($_SESSION['admission_csrf'], $token);
    }

    public function activeSession(): ?array
    {
        $sql = "
            SELECT ads.*, acs.name AS academic_session_name
            FROM admission_sessions ads
            INNER JOIN academic_sessions acs ON acs.id = ads.session_id
            WHERE ads.status = 'active'
              AND CURDATE() BETWEEN ads.start_date AND ads.end_date
            LIMIT 1
        ";

        $row = $this->fetchOne($sql);
        return $row ?: null;
    }

    public function getAdmissionSessionById(int $id): ?array
    {
        $row = $this->fetchOne(
            "SELECT ads.*, acs.name AS academic_session_name
             FROM admission_sessions ads
             INNER JOIN academic_sessions acs ON acs.id = ads.session_id
             WHERE ads.id = :id",
            ['id' => $id]
        );

        return $row ?: null;
    }

    public function requestRegistrationOtp(string $email): array
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Enter a valid email address.');
        }

        if (!$this->activeSession()) {
            throw new Exception('No active admission session is available.');
        }

        if ($this->fetchOne("SELECT id FROM applicants WHERE email = :email", ['email' => $email])) {
            throw new Exception('An applicant account already exists for this email.');
        }

        $recent = $this->fetchOne(
            "SELECT created_at FROM admission_otps
             WHERE email = :email AND purpose = 'registration'
             ORDER BY id DESC LIMIT 1",
            ['email' => $email]
        );

        if ($recent && strtotime($recent['created_at']) > time() - 60) {
            throw new Exception('Please wait before requesting another OTP.');
        }

        $otp = (string) random_int(100000, 999999);
        $this->execute(
            "INSERT INTO admission_otps (email, otp_hash, purpose, expires_at)
             VALUES (:email, :otp_hash, 'registration', DATE_ADD(NOW(), INTERVAL 10 MINUTE))",
            [
                'email' => $email,
                'otp_hash' => password_hash($otp, PASSWORD_DEFAULT)
            ]
        );

        $sent = false;
        if ($this->mailer) {
            $sent = $this->mailer->sendEmail(
                $email,
                'Admission Portal OTP',
                $this->otpEmailHtml($otp),
                "Your admission portal OTP is {$otp}. It expires in 10 minutes."
            );
        }
        
        return [
            'sent' => $sent,
            'message' => $sent
                ? 'OTP sent to your email.'
                : 'OTP was generated, but the email service could not send it. Check mail settings.'
        ];
    }

    public function verifyRegistrationOtp(string $email, string $otp): void
    {
        $email = strtolower(trim($email));
        $otp = trim($otp);

        $row = $this->fetchOne(
            "SELECT *
             FROM admission_otps
             WHERE email = :email
               AND purpose = 'registration'
               AND verified_at IS NULL
               AND expires_at >= NOW()
             ORDER BY id DESC
             LIMIT 1",
            ['email' => $email]
        );

        if (!$row || !password_verify($otp, $row['otp_hash'])) {
            throw new Exception('Invalid or expired OTP.');
        }

        $this->execute(
            "UPDATE admission_otps SET verified_at = NOW() WHERE id = :id",
            ['id' => $row['id']]
        );
    }

    public function createApplicant(string $email, string $phone, string $password): array
    {
        $email = strtolower(trim($email));
        $phone = trim($phone);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Enter a valid email address.');
        }

        if ($phone === '') {
            throw new Exception('Phone number is required.');
        }

        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters.');
        }

        if ($this->fetchOne("SELECT id FROM applicants WHERE email = :email", ['email' => $email])) {
            throw new Exception('Applicant account already exists.');
        }

        $otp = $this->fetchOne(
            "SELECT id FROM admission_otps
             WHERE email = :email
               AND purpose = 'registration'
               AND verified_at IS NOT NULL
             ORDER BY id DESC LIMIT 1",
            ['email' => $email]
        );

        if (!$otp) {
            throw new Exception('Please verify your email OTP first.');
        }

        $session = $this->activeSession();
        if (!$session) {
            throw new Exception('No active admission session is available.');
        }

        $this->db->beginTransaction();

        try {
            $applicationNo = $this->generateSlashNumber('APP', $this->sessionYear($session), 'applicants', 'application_no');

            $this->execute(
                "INSERT INTO applicants (application_no, email, phone, password_hash, email_verified, otp_verified_at)
                 VALUES (:application_no, :email, :phone, :password_hash, 1, NOW())",
                [
                    'application_no' => $applicationNo,
                    'email' => $email,
                    'phone' => $phone,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT)
                ]
            );

            $applicantId = (int) $this->db->lastInsertId();

            $this->execute(
                "INSERT INTO admission_applications
                    (applicant_id, admission_session_id, application_no, form_status)
                 VALUES (:applicant_id, :session_id, :application_no, 'Awaiting Payment')",
                [
                    'applicant_id' => $applicantId,
                    'session_id' => $session['id'],
                    'application_no' => $applicationNo
                ]
            );

            $applicationId = (int) $this->db->lastInsertId();
            $this->ensurePaymentInvoice($applicationId, 'application_fee');

            $this->db->commit();

            return [
                'applicant_id' => $applicantId,
                'application_id' => $applicationId,
                'application_no' => $applicationNo
            ];
        } catch (Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function loginApplicant(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        $row = $this->fetchOne("SELECT * FROM applicants WHERE email = :email", ['email' => $email]);

        if (!$row || !password_verify($password, $row['password_hash'])) {
            throw new Exception('Invalid login details.');
        }

        if ((int) $row['email_verified'] !== 1) {
            throw new Exception('Please verify your email before login.');
        }

        return $row;
    }

    public function getApplicationForApplicant(int $applicantId): ?array
    {
        $row = $this->fetchOne(
            "SELECT aa.*, ads.application_fee, ads.acceptance_fee, acs.name AS academic_session_name,
                    ap.email AS applicant_email, ap.phone AS applicant_phone
             FROM admission_applications aa
             INNER JOIN admission_sessions ads ON ads.id = aa.admission_session_id
             INNER JOIN academic_sessions acs ON acs.id = ads.session_id
             INNER JOIN applicants ap ON ap.id = aa.applicant_id
             WHERE aa.applicant_id = :applicant_id
             ORDER BY aa.id DESC
             LIMIT 1",
            ['applicant_id' => $applicantId]
        );

        return $row ?: null;
    }

    public function getFullApplication(int $applicationId): ?array
    {
        $application = $this->fetchOne(
            "SELECT aa.*, ads.application_fee, ads.acceptance_fee, acs.name AS academic_session_name,
                    ap.email AS applicant_email, ap.phone AS applicant_phone,
                    bio.surname, bio.first_name, bio.other_name, bio.gender, bio.date_of_birth,
                    bio.nationality, bio.state_of_origin, bio.local_government, bio.religion,
                    contact.address, contact.phone AS contact_phone, contact.email AS contact_email,
                    choice.mode_of_entry, choice.jamb_registration_number, choice.jamb_score,
                    choice.institution_id, choice.programme_id, choice.department_id,
                    inst.name AS institution_name, inst.code AS institution_code,
                    prog.name AS programme_name, prog.code AS programme_code,
                    dept.name AS department_name, dept.code AS department_code,
                    letter.letter_no, letter.issued_at,
                    migration.user_id AS migrated_user_id, migration.student_record_id, migration.matric_no
             FROM admission_applications aa
             INNER JOIN admission_sessions ads ON ads.id = aa.admission_session_id
             INNER JOIN academic_sessions acs ON acs.id = ads.session_id
             INNER JOIN applicants ap ON ap.id = aa.applicant_id
             LEFT JOIN admission_biodata bio ON bio.application_id = aa.id
             LEFT JOIN admission_contact_info contact ON contact.application_id = aa.id
             LEFT JOIN admission_programme_choices choice ON choice.application_id = aa.id
             LEFT JOIN institutions inst ON inst.id = choice.institution_id
             LEFT JOIN programmes prog ON prog.id = choice.programme_id
             LEFT JOIN department dept ON dept.id = choice.department_id
             LEFT JOIN admission_letters letter ON letter.application_id = aa.id
             LEFT JOIN admission_student_migrations migration ON migration.application_id = aa.id
             WHERE aa.id = :application_id",
            ['application_id' => $applicationId]
        );

        if (!$application) {
            return null;
        }

        $application['history'] = $this->fetchAll(
            "SELECT * FROM admission_academic_history WHERE application_id = :id ORDER BY id ASC",
            ['id' => $applicationId]
        );
        $application['sittings'] = $this->getOlevelSittings($applicationId);
        $application['documents'] = $this->fetchAll(
            "SELECT * FROM admission_documents WHERE application_id = :id ORDER BY uploaded_at DESC",
            ['id' => $applicationId]
        );
        $application['payments'] = $this->fetchAll(
            "SELECT * FROM admission_payments WHERE application_id = :id ORDER BY id DESC",
            ['id' => $applicationId]
        );
        $application['screening'] = $this->fetchAll(
            "SELECT asa.*, admins.fullname AS admin_name
             FROM admission_screening_actions asa
             LEFT JOIN admins ON admins.id = asa.admin_id
             WHERE asa.application_id = :id
             ORDER BY asa.id DESC",
            ['id' => $applicationId]
        );

        return $application;
    }

    public function getPayment(int $applicationId, string $type): ?array
    {
        $row = $this->fetchOne(
            "SELECT * FROM admission_payments
             WHERE application_id = :application_id AND payment_type = :payment_type
             ORDER BY id DESC LIMIT 1",
            [
                'application_id' => $applicationId,
                'payment_type' => $type
            ]
        );

        return $row ?: null;
    }

    public function ensurePaymentInvoice(int $applicationId, string $type): array
    {
        if (!in_array($type, ['application_fee', 'acceptance_fee'], true)) {
            throw new Exception('Invalid payment type.');
        }

        $existing = $this->getPayment($applicationId, $type);
        if ($existing) {
            return $existing;
        }

        $application = $this->getApplicationCore($applicationId);
        if (!$application) {
            throw new Exception('Application not found.');
        }

        $session = $this->getAdmissionSessionById((int) $application['admission_session_id']);
        if (!$session) {
            throw new Exception('Admission session not found.');
        }

        $amount = $type === 'application_fee'
            ? (float) $session['application_fee']
            : (float) $session['acceptance_fee'];

        $prefix = $type === 'application_fee' ? 'ADM-APP' : 'ADM-ACC';
        $invoiceNo = $this->generateDashNumber($prefix, $this->sessionYear($session), 'admission_payments', 'invoice_no');

        $this->execute(
            "INSERT INTO admission_payments
                (application_id, applicant_id, admission_session_id, invoice_no, payment_type, amount, status)
             VALUES
                (:application_id, :applicant_id, :session_id, :invoice_no, :payment_type, :amount, 'unpaid')",
            [
                'application_id' => $applicationId,
                'applicant_id' => $application['applicant_id'],
                'session_id' => $application['admission_session_id'],
                'invoice_no' => $invoiceNo,
                'payment_type' => $type,
                'amount' => $amount
            ]
        );

        return $this->getPayment($applicationId, $type);
    }

    public function initializePaystackPayment(int $applicationId, string $type, paystack $paystack): string
    {
        $application = $this->getFullApplication($applicationId);
        if (!$application) {
            throw new Exception('Application not found.');
        }

        if ($type === 'application_fee' && $this->isPaymentPaid($applicationId, 'application_fee')) {
            throw new Exception('Application fee has already been paid.');
        }

        if ($type === 'acceptance_fee' && $application['form_status'] !== 'Offered Admission') {
            throw new Exception('Acceptance fee is available only after admission offer.');
        }

        $payment = $this->ensurePaymentInvoice($applicationId, $type);
        $reference = strtoupper($type === 'application_fee' ? 'ADMAPP-' : 'ADMACC-') . bin2hex(random_bytes(8));

        $this->execute(
            "UPDATE admission_payments
             SET reference = :reference, status = 'pending'
             WHERE id = :id",
            ['reference' => $reference, 'id' => $payment['id']]
        );

        $metadata = [
            'application_id' => $applicationId,
            'applicant_id' => $application['applicant_id'],
            'payment_type' => $type,
            'invoice_no' => $payment['invoice_no']
        ];

        $response = $paystack->initializePayment(
            $application['applicant_email'],
            (int) round(((float) $payment['amount']) * 100),
            $this->baseUrl('api/admission/payment-callback.php'),
            $reference,
            $metadata
        );

        if (empty($response['data']['authorization_url'])) {
            throw new Exception('Unable to start payment.');
        }

        return $response['data']['authorization_url'];
    }

    public function applyPaymentVerification(string $reference, array $verification): array
    {
        $payment = $this->fetchOne(
            "SELECT * FROM admission_payments WHERE reference = :reference LIMIT 1",
            ['reference' => $reference]
        );

        if (!$payment) {
            throw new Exception('Payment reference not found.');
        }

        $status = $verification['data']['status'] ?? '';
        $this->db->beginTransaction();

        try {
            if ($status === 'success') {
                $this->execute(
                    "UPDATE admission_payments
                     SET status = 'paid', paid_at = NOW(), paystack_payload = :payload
                     WHERE id = :id",
                    [
                        'payload' => json_encode($verification),
                        'id' => $payment['id']
                    ]
                );

                if ($payment['payment_type'] === 'application_fee') {
                    $this->execute(
                        "UPDATE admission_applications
                         SET form_status = 'In Progress'
                         WHERE id = :id AND form_status = 'Awaiting Payment'",
                        ['id' => $payment['application_id']]
                    );
                }

                if ($payment['payment_type'] === 'acceptance_fee') {
                    $this->execute(
                        "UPDATE admission_applications
                         SET form_status = 'Accepted'
                         WHERE id = :id AND form_status = 'Offered Admission'",
                        ['id' => $payment['application_id']]
                    );

                    $this->migrateApplicantToStudent((int) $payment['application_id']);
                }
            } else {
                $this->execute(
                    "UPDATE admission_payments
                     SET status = 'failed', paystack_payload = :payload
                     WHERE id = :id",
                    [
                        'payload' => json_encode($verification),
                        'id' => $payment['id']
                    ]
                );
            }

            $this->db->commit();

            return [
                'payment_type' => $payment['payment_type'],
                'success' => $status === 'success',
                'application_id' => (int) $payment['application_id']
            ];
        } catch (Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function assertStepAccess(int $applicationId, string $step): void
    {
        $completion = $this->completion($applicationId);

        if (!$completion['application_fee_paid']) {
            throw new Exception('Pay the application fee before completing the form.');
        }

        $requirements = [
            'bio' => [],
            'contact' => ['bio'],
            'academic' => ['bio', 'contact'],
            'olevel' => ['bio', 'contact', 'academic'],
            'programme' => ['bio', 'contact', 'academic', 'olevel'],
            'documents' => ['bio', 'contact', 'academic', 'olevel', 'programme']
        ];

        foreach ($requirements[$step] ?? [] as $required) {
            if (empty($completion[$required])) {
                throw new Exception('Complete the previous section before continuing.');
            }
        }
    }

    public function saveBioData(int $applicationId, array $data): void
    {
        $this->assertEditable($applicationId);
        $this->assertStepAccess($applicationId, 'bio');

        $payload = [
            'application_id' => $applicationId,
            'surname' => $this->required($data, 'surname'),
            'first_name' => $this->required($data, 'first_name'),
            'other_name' => trim($data['other_name'] ?? ''),
            'gender' => $this->enum($data['gender'] ?? '', ['Male', 'Female', 'Other'], 'gender'),
            'date_of_birth' => $this->required($data, 'date_of_birth'),
            'nationality' => $this->required($data, 'nationality'),
            'state_of_origin' => $this->required($data, 'state_of_origin'),
            'local_government' => $this->required($data, 'local_government'),
            'religion' => trim($data['religion'] ?? '')
        ];

        $this->upsertByApplication('admission_biodata', $payload);
    }

    public function saveContactInfo(int $applicationId, array $data): void
    {
        $this->assertEditable($applicationId);
        $this->assertStepAccess($applicationId, 'contact');

        $email = strtolower($this->required($data, 'email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Enter a valid contact email.');
        }

        $payload = [
            'application_id' => $applicationId,
            'email' => $email,
            'phone' => $this->required($data, 'phone'),
            'address' => $this->required($data, 'address')
        ];

        $this->upsertByApplication('admission_contact_info', $payload);
    }

    public function saveAcademicHistory(int $applicationId, array $rows): void
    {
        $this->assertEditable($applicationId);
        $this->assertStepAccess($applicationId, 'academic');

        $cleanRows = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $institution = trim($row['institution_name'] ?? '');
            $certificate = trim($row['certificate_obtained'] ?? '');
            $location = trim($row['location'] ?? '');
            $startYear = trim((string) ($row['start_year'] ?? ''));
            $endYear = trim((string) ($row['end_year'] ?? ''));

            if ($institution === '' && $certificate === '' && $location === '') {
                continue;
            }

            if ($institution === '' || $certificate === '' || $location === '' || !preg_match('/^\d{4}$/', $startYear)) {
                throw new Exception('Complete all academic history fields.');
            }

            if ($endYear !== '' && !preg_match('/^\d{4}$/', $endYear)) {
                throw new Exception('Enter a valid academic history end year.');
            }

            $cleanRows[] = [
                'institution_name' => $institution,
                'certificate_obtained' => $certificate,
                'location' => $location,
                'start_year' => $startYear,
                'end_year' => $endYear !== '' ? $endYear : null
            ];
        }

        if (!$cleanRows) {
            throw new Exception('Add at least one academic history entry.');
        }

        $this->db->beginTransaction();
        try {
            $this->execute("DELETE FROM admission_academic_history WHERE application_id = :id", ['id' => $applicationId]);
            foreach ($cleanRows as $row) {
                $row['application_id'] = $applicationId;
                $this->insert('admission_academic_history', $row);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function saveOlevelResults(int $applicationId, array $sittings): void
    {
        $this->assertEditable($applicationId);
        $this->assertStepAccess($applicationId, 'olevel');

        $cleanSittings = [];
        foreach ($sittings as $index => $sitting) {
            if (!is_array($sitting)) {
                continue;
            }

            $examType = trim($sitting['exam_type'] ?? '');
            $examYear = trim((string) ($sitting['exam_year'] ?? ''));
            $examNumber = trim($sitting['exam_number'] ?? '');
            $subjects = $sitting['subjects'] ?? [];
            $grades = $sitting['grades'] ?? [];

            if ($examType === '' && $examYear === '' && $examNumber === '') {
                continue;
            }

            if ($examType === '' || !preg_match('/^\d{4}$/', $examYear) || $examNumber === '') {
                throw new Exception('Complete exam details for each O-Level sitting.');
            }

            $results = [];
            foreach ($subjects as $key => $subject) {
                $subject = trim((string) $subject);
                $grade = trim((string) ($grades[$key] ?? ''));

                if ($subject === '' && $grade === '') {
                    continue;
                }

                if ($subject === '' || !in_array($grade, $this->grades, true)) {
                    throw new Exception('Complete every O-Level subject and grade.');
                }

                $results[] = ['subject' => $subject, 'grade' => $grade];
            }

            if (count($results) < 5) {
                throw new Exception('Add at least five subjects for each O-Level sitting.');
            }

            $cleanSittings[] = [
                'sitting_no' => count($cleanSittings) + 1,
                'exam_type' => $examType,
                'exam_year' => $examYear,
                'exam_number' => $examNumber,
                'results' => $results
            ];
        }

        if (!$cleanSittings || count($cleanSittings) > 2) {
            throw new Exception('Provide one or two O-Level sittings.');
        }

        $this->db->beginTransaction();
        try {
            $old = $this->fetchAll(
                "SELECT id FROM admission_olevel_sittings WHERE application_id = :id",
                ['id' => $applicationId]
            );
            foreach ($old as $sitting) {
                $this->execute("DELETE FROM admission_olevel_results WHERE sitting_id = :id", ['id' => $sitting['id']]);
            }
            $this->execute("DELETE FROM admission_olevel_sittings WHERE application_id = :id", ['id' => $applicationId]);

            foreach ($cleanSittings as $sitting) {
                $this->insert('admission_olevel_sittings', [
                    'application_id' => $applicationId,
                    'sitting_no' => $sitting['sitting_no'],
                    'exam_type' => $sitting['exam_type'],
                    'exam_year' => $sitting['exam_year'],
                    'exam_number' => $sitting['exam_number']
                ]);
                $sittingId = (int) $this->db->lastInsertId();

                foreach ($sitting['results'] as $result) {
                    $this->insert('admission_olevel_results', [
                        'sitting_id' => $sittingId,
                        'subject' => $result['subject'],
                        'grade' => $result['grade']
                    ]);
                }
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function saveProgrammeChoice(int $applicationId, array $data): void
    {
        $this->assertEditable($applicationId);
        $this->assertStepAccess($applicationId, 'programme');

        $mode = $this->enum($data['mode_of_entry'] ?? '', ['JAMB UTME', 'Direct Entry', 'Remedial'], 'mode of entry');
        $institutionId = (int) ($data['institution_id'] ?? 0);
        $programmeId = (int) ($data['programme_id'] ?? 0);
        $departmentId = (int) ($data['department_id'] ?? 0);
        $jambReg = trim($data['jamb_registration_number'] ?? '');
        $jambScore = trim((string) ($data['jamb_score'] ?? ''));

        if (!$institutionId || !$programmeId || !$departmentId) {
            throw new Exception('Select institution, programme, and department.');
        }

        if (in_array($mode, ['JAMB UTME', 'Direct Entry'], true)) {
            if ($jambReg === '' || $jambScore === '' || !ctype_digit($jambScore)) {
                throw new Exception('JAMB registration number and score are required.');
            }
        } else {
            $jambReg = null;
            $jambScore = null;
        }

        $valid = $this->fetchOne(
            "SELECT dept.id
             FROM department dept
             INNER JOIN programmes prog ON prog.id = dept.programme_id
             WHERE dept.id = :department_id
               AND prog.id = :programme_id
               AND prog.institution_id = :institution_id",
            [
                'department_id' => $departmentId,
                'programme_id' => $programmeId,
                'institution_id' => $institutionId
            ]
        );

        if (!$valid) {
            throw new Exception('Selected academic structure is invalid.');
        }

        $this->upsertByApplication('admission_programme_choices', [
            'application_id' => $applicationId,
            'mode_of_entry' => $mode,
            'jamb_registration_number' => $jambReg,
            'jamb_score' => $jambScore,
            'institution_id' => $institutionId,
            'programme_id' => $programmeId,
            'department_id' => $departmentId
        ]);
    }

    public function uploadDocument(int $applicationId, string $type, array $file): array
    {
        $this->assertEditable($applicationId);
        $this->assertStepAccess($applicationId, 'documents');

        if (!in_array($type, $this->documentTypes, true)) {
            throw new Exception('Invalid document type.');
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new Exception('Document upload failed.');
        }

        $size = (int) ($file['size'] ?? 0);
        $tmp = $file['tmp_name'] ?? '';
        $originalName = $file['name'] ?? 'document';
        $mime = $this->detectMime($tmp);

        if ($type === 'passport') {
            if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
                throw new Exception('Passport must be JPG or PNG.');
            }

            if ($size > 15 * 1024) {
                throw new Exception('Passport must not exceed 15KB.');
            }
        } else {
            if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
                throw new Exception('Document must be PDF, JPG, or PNG.');
            }

            if ($size > 2 * 1024 * 1024) {
                throw new Exception('Document must not exceed 2MB.');
            }
        }

        $directory = $type === 'passport'
            ? __DIR__ . '/../uploads/admission/passports'
            : __DIR__ . '/../uploads/admission/documents';

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = $this->extensionForMime($mime);
        $filename = $type . '_' . $applicationId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $target = $directory . '/' . $filename;

        if (!move_uploaded_file($tmp, $target)) {
            throw new Exception('Unable to save uploaded document.');
        }

        $relativePath = ($type === 'passport' ? 'uploads/admission/passports/' : 'uploads/admission/documents/') . $filename;
        $existing = $this->fetchOne(
            "SELECT file_path FROM admission_documents WHERE application_id = :id AND document_type = :type",
            ['id' => $applicationId, 'type' => $type]
        );

        $payload = [
            'application_id' => $applicationId,
            'document_type' => $type,
            'file_path' => $relativePath,
            'original_name' => $originalName,
            'mime_type' => $mime,
            'file_size' => $size,
            'validation_status' => 'valid',
            'validation_notes' => $type === 'passport'
                ? 'Passport file type and size validated. Optional AI face/background checks can be added here.'
                : 'Server-side file type and size validated.'
        ];

        $this->upsertDocument($payload);

        if ($existing && !empty($existing['file_path'])) {
            $old = __DIR__ . '/../' . $existing['file_path'];
            if (is_file($old)) {
                @unlink($old);
            }
        }

        return $payload;
    }

    public function completion(int $applicationId): array
    {
        $choice = $this->fetchOne(
            "SELECT * FROM admission_programme_choices WHERE application_id = :id",
            ['id' => $applicationId]
        );

        $docs = $this->fetchAll(
            "SELECT document_type FROM admission_documents
             WHERE application_id = :id AND validation_status IN ('pending','valid')",
            ['id' => $applicationId]
        );

        $uploaded = array_column($docs, 'document_type');
        $requiredDocs = $this->requiredDocuments($choice['mode_of_entry'] ?? null);
        $documentsComplete = !array_diff($requiredDocs, $uploaded);

        $completion = [
            'application_fee_paid' => $this->isPaymentPaid($applicationId, 'application_fee'),
            'bio' => (bool) $this->fetchOne("SELECT id FROM admission_biodata WHERE application_id = :id", ['id' => $applicationId]),
            'contact' => (bool) $this->fetchOne("SELECT id FROM admission_contact_info WHERE application_id = :id", ['id' => $applicationId]),
            'academic' => ((int) $this->scalar("SELECT COUNT(*) FROM admission_academic_history WHERE application_id = :id", ['id' => $applicationId])) > 0,
            'olevel' => ((int) $this->scalar("SELECT COUNT(*) FROM admission_olevel_sittings WHERE application_id = :id", ['id' => $applicationId])) > 0,
            'programme' => (bool) $choice,
            'documents' => $documentsComplete,
            'required_documents' => $requiredDocs,
            'uploaded_documents' => $uploaded
        ];

        $weightedSteps = ['application_fee_paid', 'bio', 'contact', 'academic', 'olevel', 'programme', 'documents'];
        $completedSteps = array_filter($weightedSteps, fn($step) => !empty($completion[$step]));
        $completion['percentage'] = (int) round((count($completedSteps) / count($weightedSteps)) * 100);

        return $completion;
    }

    public function submitApplication(int $applicationId): string
    {
        $application = $this->getApplicationCore($applicationId);
        if (!$application) {
            throw new Exception('Application not found.');
        }

        if (!in_array($application['form_status'], ['In Progress', 'Draft'], true)) {
            throw new Exception('This application cannot be submitted in its current status.');
        }

        $completion = $this->completion($applicationId);
        foreach (['application_fee_paid', 'bio', 'contact', 'academic', 'olevel', 'programme', 'documents'] as $key) {
            if (empty($completion[$key])) {
                throw new Exception('Complete every section before final submission.');
            }
        }

        $session = $this->getAdmissionSessionById((int) $application['admission_session_id']);
        $registrationNo = $application['registration_no']
            ?: $this->generateSlashNumber('ADM', $this->sessionYear($session), 'admission_applications', 'registration_no');

        $this->execute(
            "UPDATE admission_applications
             SET registration_no = :registration_no,
                 form_status = 'Submitted',
                 submitted_at = NOW()
             WHERE id = :id",
            [
                'registration_no' => $registrationNo,
                'id' => $applicationId
            ]
        );

        return $registrationNo;
    }

    public function screeningAction(int $applicationId, int $adminId, string $action, string $remarks = ''): string
    {
        $application = $this->getApplicationCore($applicationId);
        if (!$application) {
            throw new Exception('Application not found.');
        }

        $map = [
            'review' => 'Under Review',
            'recommend' => 'Recommended',
            'approve' => 'Offered Admission',
            'reject' => 'Rejected',
            'remark' => $application['form_status']
        ];

        if (!isset($map[$action])) {
            throw new Exception('Invalid screening action.');
        }

        $from = $application['form_status'];
        $to = $map[$action];

        $this->db->beginTransaction();
        try {
            if ($action !== 'remark') {
                $this->execute(
                    "UPDATE admission_applications SET form_status = :status WHERE id = :id",
                    ['status' => $to, 'id' => $applicationId]
                );
            }

            $this->insert('admission_screening_actions', [
                'application_id' => $applicationId,
                'admin_id' => $adminId,
                'action' => $action,
                'from_status' => $from,
                'to_status' => $to,
                'remarks' => $remarks
            ]);

            if ($action === 'approve') {
                $this->ensureAdmissionLetter($applicationId);
                $this->ensurePaymentInvoice($applicationId, 'acceptance_fee');
            }

            $this->db->commit();
            return $to;
        } catch (Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function ensureAdmissionLetter(int $applicationId): array
    {
        $existing = $this->fetchOne(
            "SELECT * FROM admission_letters WHERE application_id = :id",
            ['id' => $applicationId]
        );

        if ($existing) {
            return $existing;
        }

        $application = $this->getFullApplication($applicationId);
        $letterNo = $this->generateDashNumber('ADM-LTR', $this->sessionYear($application), 'admission_letters', 'letter_no');

        $this->insert('admission_letters', [
            'application_id' => $applicationId,
            'letter_no' => $letterNo
        ]);

        return $this->fetchOne(
            "SELECT * FROM admission_letters WHERE application_id = :id",
            ['id' => $applicationId]
        );
    }

    public function migrateApplicantToStudent(int $applicationId): array
    {
        $existing = $this->fetchOne(
            "SELECT * FROM admission_student_migrations WHERE application_id = :id",
            ['id' => $applicationId]
        );

        if ($existing) {
            return $existing;
        }

        $application = $this->getFullApplication($applicationId);
        if (!$application || !in_array($application['form_status'], ['Accepted', 'Offered Admission'], true)) {
            throw new Exception('Application is not ready for student migration.');
        }

        if (empty($application['department_id'])) {
            throw new Exception('Programme choice is incomplete.');
        }

        $levelId = $this->resolveEntryLevelId((int) $application['department_id'], $application['mode_of_entry'] ?? 'JAMB UTME');
        if (!$levelId) {
            throw new Exception('No active entry level exists for the selected department.');
        }

        $matricNo = $this->generateMatricNumber($application);
        $name = trim(($application['first_name'] ?? '') . ' ' . ($application['other_name'] ?? '') . ' ' . ($application['surname'] ?? ''));

        $user = $this->fetchOne("SELECT id FROM users WHERE email = :email", ['email' => $application['applicant_email']]);
        if ($user) {
            $userId = (int) $user['id'];
            $this->execute(
                "UPDATE users
                 SET name = :name, role = 'student', institution_id = :institution_id, is_active = 1
                 WHERE id = :id",
                [
                    'name' => $name,
                    'institution_id' => $application['institution_id'],
                    'id' => $userId
                ]
            );
        } else {
            $applicant = $this->fetchOne(
                "SELECT password_hash FROM applicants WHERE id = :id",
                ['id' => $application['applicant_id']]
            );

            $this->insert('users', [
                'name' => $name,
                'email' => $application['applicant_email'],
                'password' => $applicant['password_hash'],
                'role' => 'student',
                'institution_id' => $application['institution_id'],
                'is_default_password' => 0,
                'is_active' => 1
            ]);
            $userId = (int) $this->db->lastInsertId();
        }

        $student = $this->fetchOne("SELECT id FROM students WHERE student_id = :id", ['id' => $userId]);
        if ($student) {
            $studentRecordId = (int) $student['id'];
            $this->execute(
                "UPDATE students
                 SET matric_no = :matric_no,
                     first_name = :first_name,
                     last_name = :last_name,
                     other_name = :other_name,
                     gender = :gender,
                     dateofbirth = :dob,
                     phone = :phone,
                     institution_id = :institution_id,
                     programme_id = :programme_id,
                     department_id = :department_id,
                     level_id = :level_id,
                     status = 'active',
                     updateProfile = 1
                 WHERE id = :id",
                [
                    'matric_no' => $matricNo,
                    'first_name' => $application['first_name'],
                    'last_name' => $application['surname'],
                    'other_name' => $application['other_name'],
                    'gender' => $this->studentGender($application['gender']),
                    'dob' => $application['date_of_birth'],
                    'phone' => $application['contact_phone'] ?: $application['applicant_phone'],
                    'institution_id' => $application['institution_id'],
                    'programme_id' => $application['programme_id'],
                    'department_id' => $application['department_id'],
                    'level_id' => $levelId,
                    'id' => $studentRecordId
                ]
            );
        } else {
            $this->insert('students', [
                'student_id' => $userId,
                'matric_no' => $matricNo,
                'first_name' => $application['first_name'],
                'last_name' => $application['surname'],
                'other_name' => $application['other_name'],
                'gender' => $this->studentGender($application['gender']),
                'dateofbirth' => $application['date_of_birth'],
                'phone' => $application['contact_phone'] ?: $application['applicant_phone'],
                'institution_id' => $application['institution_id'],
                'programme_id' => $application['programme_id'],
                'department_id' => $application['department_id'],
                'level_id' => $levelId,
                'status' => 'active',
                'updateProfile' => 1
            ]);
            $studentRecordId = (int) $this->db->lastInsertId();
        }

        $this->insert('admission_student_migrations', [
            'application_id' => $applicationId,
            'user_id' => $userId,
            'student_record_id' => $studentRecordId,
            'matric_no' => $matricNo
        ]);

        return $this->fetchOne(
            "SELECT * FROM admission_student_migrations WHERE application_id = :id",
            ['id' => $applicationId]
        );
    }

    public function applications(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'aa.form_status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['session_id'])) {
            $where[] = 'aa.admission_session_id = :session_id';
            $params['session_id'] = $filters['session_id'];
        }

        $sql = "
            SELECT aa.id, aa.application_no, aa.registration_no, aa.form_status, aa.submitted_at, aa.created_at,
                   ap.email, ap.phone,
                   CONCAT_WS(' ', bio.surname, bio.first_name, bio.other_name) AS applicant_name,
                   acs.name AS session_name,
                   inst.name AS institution_name,
                   prog.name AS programme_name,
                   dept.name AS department_name,
                   app_payment.status AS application_payment_status,
                   acc_payment.status AS acceptance_payment_status
            FROM admission_applications aa
            INNER JOIN applicants ap ON ap.id = aa.applicant_id
            INNER JOIN admission_sessions ads ON ads.id = aa.admission_session_id
            INNER JOIN academic_sessions acs ON acs.id = ads.session_id
            LEFT JOIN admission_biodata bio ON bio.application_id = aa.id
            LEFT JOIN admission_programme_choices choice ON choice.application_id = aa.id
            LEFT JOIN institutions inst ON inst.id = choice.institution_id
            LEFT JOIN programmes prog ON prog.id = choice.programme_id
            LEFT JOIN department dept ON dept.id = choice.department_id
            LEFT JOIN admission_payments app_payment
                ON app_payment.application_id = aa.id AND app_payment.payment_type = 'application_fee'
            LEFT JOIN admission_payments acc_payment
                ON acc_payment.application_id = aa.id AND acc_payment.payment_type = 'acceptance_fee'
        ";

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY aa.id DESC';

        return $this->fetchAll($sql, $params);
    }

    public function dashboardStats(): array
    {
        return [
            'total_applications' => (int) $this->scalar("SELECT COUNT(*) FROM admission_applications"),
            'submitted_applications' => (int) $this->scalar("SELECT COUNT(*) FROM admission_applications WHERE form_status IN ('Submitted','Under Review','Recommended')"),
            'pending_screening' => (int) $this->scalar("SELECT COUNT(*) FROM admission_applications WHERE form_status = 'Submitted'"),
            'admitted_candidates' => (int) $this->scalar("SELECT COUNT(*) FROM admission_applications WHERE form_status IN ('Offered Admission','Accepted')"),
            'acceptance_fee_paid' => (int) $this->scalar("SELECT COUNT(*) FROM admission_payments WHERE payment_type = 'acceptance_fee' AND status = 'paid'"),
            'acceptance_fee_outstanding' => (int) $this->scalar("SELECT COUNT(*) FROM admission_payments WHERE payment_type = 'acceptance_fee' AND status <> 'paid'")
        ];
    }

    public function admissionSessions(): array
    {
        return $this->fetchAll(
            "SELECT ads.*, acs.name AS academic_session_name
             FROM admission_sessions ads
             INNER JOIN academic_sessions acs ON acs.id = ads.session_id
             ORDER BY ads.id DESC"
        );
    }

    public function saveAdmissionSession(array $data): void
    {
        $id = (int) ($data['id'] ?? 0);
        $status = $this->enum($data['status'] ?? 'inactive', ['active', 'inactive'], 'status');
        $payload = [
            'session_id' => (int) ($data['session_id'] ?? 0),
            'application_fee' => (float) ($data['application_fee'] ?? 0),
            'acceptance_fee' => (float) ($data['acceptance_fee'] ?? 0),
            'start_date' => $this->required($data, 'start_date'),
            'end_date' => $this->required($data, 'end_date'),
            'status' => $status
        ];

        if (!$payload['session_id'] || $payload['application_fee'] < 0 || $payload['acceptance_fee'] < 0) {
            throw new Exception('Complete the admission session fees and academic session.');
        }

        if (strtotime($payload['end_date']) < strtotime($payload['start_date'])) {
            throw new Exception('End date cannot be before start date.');
        }

        $this->db->beginTransaction();
        try {
            if ($status === 'active') {
                $this->execute("UPDATE admission_sessions SET status = 'inactive'");
            }

            if ($id) {
                $sets = [];
                foreach ($payload as $key => $value) {
                    $sets[] = "{$key} = :{$key}";
                }
                $payload['id'] = $id;
                $this->execute(
                    "UPDATE admission_sessions SET " . implode(', ', $sets) . " WHERE id = :id",
                    $payload
                );
            } else {
                $this->insert('admission_sessions', $payload);
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function verificationUrl(array $application): string
    {
        $applicationNo = $application['application_no'] ?? '';
        $registrationNo = $application['registration_no'] ?? '';
        $signature = hash_hmac('sha256', $applicationNo . '|' . $registrationNo, APP_KEY);

        return $this->baseUrl(
            'admission_verify.php?application_no=' . rawurlencode($applicationNo)
                . '&registration_no=' . rawurlencode($registrationNo)
                . '&signature=' . $signature
        );
    }

    public function verifyPublicRecord(string $applicationNo, string $registrationNo, string $signature): ?array
    {
        $expected = hash_hmac('sha256', $applicationNo . '|' . $registrationNo, APP_KEY);
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        return $this->fetchOne(
            "SELECT aa.application_no, aa.registration_no, aa.form_status,
                    CONCAT_WS(' ', bio.surname, bio.first_name, bio.other_name) AS applicant_name,
                    acs.name AS session_name, inst.name AS institution_name,
                    prog.name AS programme_name, dept.name AS department_name
             FROM admission_applications aa
             INNER JOIN admission_sessions ads ON ads.id = aa.admission_session_id
             INNER JOIN academic_sessions acs ON acs.id = ads.session_id
             LEFT JOIN admission_biodata bio ON bio.application_id = aa.id
             LEFT JOIN admission_programme_choices choice ON choice.application_id = aa.id
             LEFT JOIN institutions inst ON inst.id = choice.institution_id
             LEFT JOIN programmes prog ON prog.id = choice.programme_id
             LEFT JOIN department dept ON dept.id = choice.department_id
             WHERE aa.application_no = :application_no
               AND aa.registration_no = :registration_no",
            [
                'application_no' => $applicationNo,
                'registration_no' => $registrationNo
            ]
        ) ?: null;
    }

    public function qrDataUri(array $application): string
    {
        if (!$this->qrcode) {
            return '';
        }

        return $this->qrcode->generateQRCode($this->verificationUrl($application));
    }

    public function requiredDocuments(?string $mode): array
    {
        $required = ['passport', 'birth_certificate', 'olevel_result'];

        if ($mode === 'JAMB UTME') {
            $required[] = 'jamb_result_slip';
        }

        if ($mode === 'Direct Entry') {
            $required[] = 'jamb_result_slip';
            $required[] = 'previous_certificate';
        }

        return $required;
    }

    public function baseUrl(string $path = ''): string
    {
        $project = basename(dirname(__DIR__));
        return rtrim(BASE_URL, '/') . '/' . $project . '/' . ltrim($path, '/');
    }

    public function academicSessions(): array
    {
        return $this->fetchAll("SELECT * FROM academic_sessions ORDER BY id DESC");
    }

    public function institutions(): array
    {
        return $this->fetchAll("SELECT * FROM institutions WHERE is_active = 1 ORDER BY name ASC");
    }

    public function programmesByInstitution(int $institutionId): array
    {
        return $this->fetchAll(
            "SELECT * FROM programmes WHERE institution_id = :id AND is_active = 1 ORDER BY name ASC",
            ['id' => $institutionId]
        );
    }

    public function departmentsByProgramme(int $programmeId): array
    {
        return $this->fetchAll(
            "SELECT * FROM department WHERE programme_id = :id AND is_active = 1 ORDER BY name ASC",
            ['id' => $programmeId]
        );
    }

    private function getApplicationCore(int $applicationId): ?array
    {
        $row = $this->fetchOne(
            "SELECT * FROM admission_applications WHERE id = :id",
            ['id' => $applicationId]
        );

        return $row ?: null;
    }

    private function isPaymentPaid(int $applicationId, string $type): bool
    {
        return (bool) $this->fetchOne(
            "SELECT id FROM admission_payments
             WHERE application_id = :id AND payment_type = :type AND status = 'paid'",
            ['id' => $applicationId, 'type' => $type]
        );
    }

    private function assertEditable(int $applicationId): void
    {
        $application = $this->getApplicationCore($applicationId);
        if (!$application) {
            throw new Exception('Application not found.');
        }

        if (!in_array($application['form_status'], ['Draft', 'Awaiting Payment', 'In Progress'], true)) {
            throw new Exception('This application can no longer be edited.');
        }
    }

    private function getOlevelSittings(int $applicationId): array
    {
        $sittings = $this->fetchAll(
            "SELECT * FROM admission_olevel_sittings WHERE application_id = :id ORDER BY sitting_no ASC",
            ['id' => $applicationId]
        );

        foreach ($sittings as &$sitting) {
            $sitting['results'] = $this->fetchAll(
                "SELECT * FROM admission_olevel_results WHERE sitting_id = :id ORDER BY id ASC",
                ['id' => $sitting['id']]
            );
        }

        return $sittings;
    }

    private function upsertByApplication(string $table, array $data): void
    {
        $exists = $this->fetchOne(
            "SELECT id FROM {$table} WHERE application_id = :application_id",
            ['application_id' => $data['application_id']]
        );

        if ($exists) {
            $sets = [];
            foreach ($data as $key => $value) {
                if ($key === 'application_id') {
                    continue;
                }
                $sets[] = "{$key} = :{$key}";
            }
            $this->execute(
                "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE application_id = :application_id",
                $data
            );
        } else {
            $this->insert($table, $data);
        }
    }

    private function upsertDocument(array $data): void
    {
        $exists = $this->fetchOne(
            "SELECT id FROM admission_documents
             WHERE application_id = :application_id AND document_type = :document_type",
            [
                'application_id' => $data['application_id'],
                'document_type' => $data['document_type']
            ]
        );

        if ($exists) {
            $updateData = [
                'id' => $exists['id'],
                'file_path' => $data['file_path'],
                'original_name' => $data['original_name'],
                'mime_type' => $data['mime_type'],
                'file_size' => $data['file_size'],
                'validation_status' => $data['validation_status'],
                'validation_notes' => $data['validation_notes']
            ];
            $this->execute(
                "UPDATE admission_documents
                 SET file_path = :file_path,
                     original_name = :original_name,
                     mime_type = :mime_type,
                     file_size = :file_size,
                     validation_status = :validation_status,
                     validation_notes = :validation_notes,
                     uploaded_at = NOW()
                 WHERE id = :id",
                $updateData
            );
        } else {
            $this->insert('admission_documents', $data);
        }
    }

    private function insert(string $table, array $data): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new Exception('Invalid table.');
        }

        $columns = array_keys($data);
        foreach ($columns as $column) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
                throw new Exception('Invalid column.');
            }
        }

        $sql = "INSERT INTO {$table} (" . implode(',', $columns) . ")
                VALUES (:" . implode(',:', $columns) . ")";
        $this->execute($sql, $data);
    }

    private function required(array $data, string $key): string
    {
        $value = trim((string) ($data[$key] ?? ''));
        if ($value === '') {
            throw new Exception(ucwords(str_replace('_', ' ', $key)) . ' is required.');
        }

        return $value;
    }

    private function enum(string $value, array $allowed, string $label): string
    {
        $value = trim($value);
        if (!in_array($value, $allowed, true)) {
            throw new Exception('Select a valid ' . $label . '.');
        }

        return $value;
    }

    private function generateSlashNumber(string $prefix, string $year, string $table, string $column): string
    {
        $like = "{$prefix}/{$year}/%";
        $last = (int) $this->scalar(
            "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX({$column}, '/', -1) AS UNSIGNED)), 0)
             FROM {$table}
             WHERE {$column} LIKE :like",
            ['like' => $like]
        );

        return sprintf('%s/%s/%05d', $prefix, $year, $last + 1);
    }

    private function generateDashNumber(string $prefix, string $year, string $table, string $column): string
    {
        $like = "{$prefix}-{$year}-%";
        $last = (int) $this->scalar(
            "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX({$column}, '-', -1) AS UNSIGNED)), 0)
             FROM {$table}
             WHERE {$column} LIKE :like",
            ['like' => $like]
        );

        return sprintf('%s-%s-%05d', $prefix, $year, $last + 1);
    }

    private function generateMatricNumber(array $application): string
    {
        $code = $application['institution_code'] ?: substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($application['institution_name'] ?? 'SCH')), 0, 4);
        $code = $code ?: 'SCH';
        $year = $this->sessionYear($application);
        $like = "{$code}/{$year}/%";
        $last = (int) $this->scalar(
            "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(matric_no, '/', -1) AS UNSIGNED)), 0)
             FROM students
             WHERE matric_no LIKE :like",
            ['like' => $like]
        );

        return sprintf('%s/%s/%04d', strtoupper($code), $year, $last + 1);
    }

    private function sessionYear(?array $session): string
    {
        $name = $session['academic_session_name'] ?? $session['session_name'] ?? '';
        if (preg_match('/(20\d{2})/', $name, $match)) {
            return $match[1];
        }

        if (!empty($session['start_date']) && preg_match('/^(20\d{2})/', $session['start_date'], $match)) {
            return $match[1];
        }

        return date('Y');
    }

    private function resolveEntryLevelId(int $departmentId, string $mode): ?int
    {
        $target = $mode === 'Direct Entry' ? '200' : '100';

        $row = $this->fetchOne(
            "SELECT id FROM levels
             WHERE department_id = :department_id
               AND is_active = 1
               AND (code LIKE :target OR name LIKE :target)
             ORDER BY id ASC
             LIMIT 1",
            [
                'department_id' => $departmentId,
                'target' => '%' . $target . '%'
            ]
        );

        if ($row) {
            return (int) $row['id'];
        }

        $row = $this->fetchOne(
            "SELECT id FROM levels
             WHERE department_id = :department_id AND is_active = 1
             ORDER BY id ASC LIMIT 1",
            ['department_id' => $departmentId]
        );

        return $row ? (int) $row['id'] : null;
    }

    private function studentGender(?string $gender): ?string
    {
        return match ($gender) {
            'Male' => '1',
            'Female' => '2',
            default => null
        };
    }

    private function detectMime(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);

        return $mime ?: 'application/octet-stream';
    }

    private function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
            default => 'bin'
        };
    }

    private function otpEmailHtml(string $otp): string
    {
        return "
            <div style=\"font-family:Arial,sans-serif;line-height:1.6;color:#111827\">
                <h2>Admission Portal Email Verification</h2>
                <p>Use the OTP below to continue your admission account registration.</p>
                <h1 style=\"letter-spacing:6px;color:#2563eb\">{$otp}</h1>
                <p>This OTP expires in 10 minutes.</p>
            </div>
        ";
    }

    private function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function scalar(string $sql, array $params = []): mixed
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn();
    }

    private function execute(string $sql, array $params = []): void
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    private function rollBack(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
}
