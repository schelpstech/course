-- Admission Portal Module
-- Apply after the existing school portal schema has been installed.

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE admins
    MODIFY role ENUM('registry','log','bursary','super','admission') NULL;

CREATE TABLE IF NOT EXISTS admission_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    application_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    acceptance_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'inactive',
    active_lock TINYINT GENERATED ALWAYS AS (CASE WHEN status = 'active' THEN 1 ELSE NULL END) STORED,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_admission_sessions_active (active_lock),
    KEY idx_admission_sessions_session (session_id),
    CONSTRAINT fk_admission_sessions_academic
        FOREIGN KEY (session_id) REFERENCES academic_sessions(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS applicants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_no VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    password_hash VARCHAR(255) NOT NULL,
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    otp_verified_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admission_otps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    purpose ENUM('registration','password_reset') NOT NULL DEFAULT 'registration',
    expires_at DATETIME NOT NULL,
    verified_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_admission_otps_email (email, purpose, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admission_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    admission_session_id INT NOT NULL,
    application_no VARCHAR(30) NOT NULL UNIQUE,
    registration_no VARCHAR(30) NULL UNIQUE,
    form_status ENUM(
        'Draft',
        'Awaiting Payment',
        'In Progress',
        'Submitted',
        'Under Review',
        'Recommended',
        'Offered Admission',
        'Rejected',
        'Accepted'
    ) NOT NULL DEFAULT 'Awaiting Payment',
    submitted_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_admission_applications_applicant (applicant_id),
    KEY idx_admission_applications_session (admission_session_id),
    KEY idx_admission_applications_status (form_status),
    CONSTRAINT fk_admission_applications_applicant
        FOREIGN KEY (applicant_id) REFERENCES applicants(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_admission_applications_session
        FOREIGN KEY (admission_session_id) REFERENCES admission_sessions(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admission_biodata (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL UNIQUE,
    surname VARCHAR(100) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    other_name VARCHAR(100) NULL,
    gender ENUM('Male','Female','Other') NOT NULL,
    date_of_birth DATE NOT NULL,
    nationality VARCHAR(100) NOT NULL,
    state_of_origin VARCHAR(100) NOT NULL,
    local_government VARCHAR(100) NOT NULL,
    religion VARCHAR(100) NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_admission_biodata_application
        FOREIGN KEY (application_id) REFERENCES admission_applications(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admission_contact_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    address TEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_admission_contact_application
        FOREIGN KEY (application_id) REFERENCES admission_applications(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admission_academic_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    institution_name VARCHAR(200) NOT NULL,
    certificate_obtained VARCHAR(150) NOT NULL,
    location VARCHAR(150) NOT NULL,
    start_year YEAR NOT NULL,
    end_year YEAR NULL,
    KEY idx_admission_history_application (application_id),
    CONSTRAINT fk_admission_history_application
        FOREIGN KEY (application_id) REFERENCES admission_applications(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admission_olevel_sittings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    sitting_no TINYINT NOT NULL,
    exam_type VARCHAR(50) NOT NULL,
    exam_year YEAR NOT NULL,
    exam_number VARCHAR(80) NOT NULL,
    UNIQUE KEY uq_admission_sitting (application_id, sitting_no),
    CONSTRAINT fk_admission_sittings_application
        FOREIGN KEY (application_id) REFERENCES admission_applications(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admission_olevel_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sitting_id INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    grade ENUM('A1','B2','B3','C4','C5','C6','D7','E8','F9','ABS','AR') NOT NULL,
    KEY idx_admission_results_sitting (sitting_id),
    CONSTRAINT fk_admission_results_sitting
        FOREIGN KEY (sitting_id) REFERENCES admission_olevel_sittings(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admission_programme_choices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL UNIQUE,
    mode_of_entry ENUM('JAMB UTME','Direct Entry','Remedial') NOT NULL,
    jamb_registration_number VARCHAR(80) NULL,
    jamb_score INT NULL,
    institution_id INT NOT NULL,
    programme_id INT NOT NULL,
    department_id INT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_admission_choice_institution (institution_id),
    KEY idx_admission_choice_programme (programme_id),
    KEY idx_admission_choice_department (department_id),
    CONSTRAINT fk_admission_choice_application
        FOREIGN KEY (application_id) REFERENCES admission_applications(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_admission_choice_institution
        FOREIGN KEY (institution_id) REFERENCES institutions(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_admission_choice_programme
        FOREIGN KEY (programme_id) REFERENCES programmes(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_admission_choice_department
        FOREIGN KEY (department_id) REFERENCES department(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admission_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    document_type ENUM('passport','birth_certificate','olevel_result','jamb_result_slip','previous_certificate','other') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    validation_status ENUM('pending','valid','rejected') NOT NULL DEFAULT 'pending',
    validation_notes TEXT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_admission_document_type (application_id, document_type),
    CONSTRAINT fk_admission_documents_application
        FOREIGN KEY (application_id) REFERENCES admission_applications(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admission_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    applicant_id INT NOT NULL,
    admission_session_id INT NOT NULL,
    invoice_no VARCHAR(50) NOT NULL UNIQUE,
    payment_type ENUM('application_fee','acceptance_fee') NOT NULL,
    reference VARCHAR(80) NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('unpaid','pending','paid','failed') NOT NULL DEFAULT 'unpaid',
    paid_at DATETIME NULL,
    paystack_payload JSON NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_admission_payments_application (application_id),
    KEY idx_admission_payments_applicant (applicant_id),
    KEY idx_admission_payments_status (status),
    CONSTRAINT fk_admission_payments_application
        FOREIGN KEY (application_id) REFERENCES admission_applications(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_admission_payments_applicant
        FOREIGN KEY (applicant_id) REFERENCES applicants(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_admission_payments_session
        FOREIGN KEY (admission_session_id) REFERENCES admission_sessions(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admission_screening_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    admin_id INT NOT NULL,
    action ENUM('review','recommend','approve','reject','remark') NOT NULL,
    from_status VARCHAR(50) NOT NULL,
    to_status VARCHAR(50) NOT NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_admission_screening_application (application_id),
    KEY idx_admission_screening_admin (admin_id),
    CONSTRAINT fk_admission_screening_application
        FOREIGN KEY (application_id) REFERENCES admission_applications(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_admission_screening_admin
        FOREIGN KEY (admin_id) REFERENCES admins(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admission_letters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL UNIQUE,
    letter_no VARCHAR(50) NOT NULL UNIQUE,
    issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_admission_letters_application
        FOREIGN KEY (application_id) REFERENCES admission_applications(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admission_student_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL UNIQUE,
    user_id INT NOT NULL,
    student_record_id INT NOT NULL,
    matric_no VARCHAR(50) NOT NULL UNIQUE,
    migrated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_admission_migration_user (user_id),
    CONSTRAINT fk_admission_migration_application
        FOREIGN KEY (application_id) REFERENCES admission_applications(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_admission_migration_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_admission_migration_student
        FOREIGN KEY (student_record_id) REFERENCES students(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
