<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$dir = __DIR__;

while (!file_exists($dir . '/vendor/autoload.php')) {
    $parent = dirname($dir);

    if ($parent === $dir) {
        throw new Exception("vendor/autoload.php not found");
    }

    $dir = $parent;
}

require_once $dir . '/vendor/autoload.php';

class mailservice
{
    public function sendEmail(string $to, string $subject, string $htmlContent, string $altContent = ''): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'owutech-edu.org'; // e.g., smtp.gmail.com
            $mail->SMTPAuth   = true;
            $mail->Username   = 'noreply@owutech-edu.org';
            $mail->Password   = 'D@tBoiWithBigGOD';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;


            // Sender
            $mail->setFrom(
                'noreply@owutech-edu.org',
                'OTP - Password Reset Request'
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


    public function sendOtp(string $to, string $otp): bool
{
    $subject = "Your Password Reset OTP";

    $htmlContent = "
        <div style='font-family: Arial, sans-serif; line-height:1.6'>
            <h2 style='color:#1e293b;'>Password Reset Request</h2>
            <p>You requested to reset your password.</p>

            <p>Your One-Time Password (OTP) is:</p>

            <h1 style='letter-spacing:4px; color:#2563eb;'>$otp</h1>

            <p>This OTP is valid for <strong>10 minutes</strong>.</p>

            <p>If you did not request this, please ignore this email.</p>

            <hr>
            <small>OTP - Password Reset Request</small>
        </div>
    ";

    return $this->sendEmail($to, $subject, $htmlContent);
}
}
