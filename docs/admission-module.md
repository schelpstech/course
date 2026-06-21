# Admission Module Implementation

## Migration

Run:

```sql
SOURCE database/migrations/2026_06_18_admission_module.sql;
```

The migration creates admission-owned tables for sessions, applicants, OTPs, forms, biodata, contacts, academic history, O-Level sittings/results, programme choices, documents, payments, screening actions, admission letters, and student migrations.

It also extends `admins.role` with `admission` so dedicated admission officers can be created. Existing roles `super` and `registry` can also access admission screening.

## Applicant URLs

- Applicant portal: `admission.php`
- QR verification page: `admission_verify.php`
- Application slip PDF: `api/admission/download-slip.php`
- Admission letter PDF: `api/admission/download-letter.php`

## Admin URLs

Use the existing admin router:

- `admissionDashboard`
- `admissionSessions`
- `admissionApplications`

These routes are linked in the admin sidebar under Admission.

## Applicant Flow

1. Applicant enters email and requests OTP.
2. OTP is stored hashed in `admission_otps` and sent with the existing mail service.
3. Applicant verifies OTP.
4. Applicant adds phone and password.
5. Applicant account is created in `applicants`.
6. Application record and application fee invoice are created.
7. Applicant pays through the existing Paystack wrapper.
8. Successful payment changes application status from `Awaiting Payment` to `In Progress`.
9. Applicant completes the wizard in order:
   - Bio Data
   - Contact Information
   - Academic History
   - O-Level Results
   - Programme Selection
   - Document Uploads
   - Preview
10. Final submission generates `ADM/YYYY/XXXXX`, locks the form, and enables application slip PDF.

## Screening Flow

Admission officers can review submitted applications and move them through:

- `Submitted`
- `Under Review`
- `Recommended`
- `Offered Admission`
- `Rejected`

Every screening action is stored in `admission_screening_actions`.

Approving an application creates an admission letter and an acceptance fee invoice.

## Acceptance And Migration

After an offered applicant pays the acceptance fee:

1. Payment is verified through Paystack.
2. Application status changes to `Accepted`.
3. A matric number is generated as `INSTITUTIONCODE/YYYY/NNNN`.
4. A `users` record is created or updated with role `student`.
5. A `students` record is created or updated.
6. The migration is recorded in `admission_student_migrations`.

The migrated student uses the same email and password chosen during admission registration.

## Document Validation

Server-side validation is implemented in `Admission::uploadDocument()`.

- Passport: JPG/PNG only, maximum 15KB.
- Other documents: PDF/JPG/PNG, maximum 2MB.
- Mandatory documents: passport, birth certificate, O-Level result.
- UTME adds JAMB result slip.
- Direct Entry adds JAMB result slip and previous certificate.

The passport validation method stores a validation note where optional AI face/background checks can be integrated later.

## Payment Integration

Admission payments use `admission_payments` instead of the existing `payments` table because the existing table requires a `student_id`. The module still uses the existing `paystack` class for initialization and verification.

## QR Verification

Application slips and admission letters include a QR code that points to `admission_verify.php` with a signed application/registration payload. The page verifies the HMAC signature using `APP_KEY`.
