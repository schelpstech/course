<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class MailService
{
    public function sendEmail(string $to, string $subject, string $htmlContent, string $altContent = ''): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'ogmoestconsultants.com'; // e.g., smtp.gmail.com
            $mail->SMTPAuth   = true;
            $mail->Username   = 'noreply@ogmoestconsultants.com';
            $mail->Password   = '&YhzGPLtgtiP';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;


            // Sender
            $mail->setFrom(
                'noreply@ogmoestconsultants.com',
                'OTP - CASS 3 Clearance Portal'
            );

            // Recipient
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlContent;
            $mail->AltBody = $altContent ?: strip_tags($htmlContent);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Mailer Error: ' . $mail->ErrorInfo);
            return false;
        }
    }
}
