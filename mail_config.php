<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

function sendOtpEmail($recipientEmail, $otpCode) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'javaricexml@gmail.com';
        $mail->Password   = 'lrou qevo vibd krao';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('yourgmail@gmail.com', 'eDonate');
        $mail->addAddress($recipientEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Your eDonate OTP Code';
        $mail->Body    = "
            <h3>Email Verification</h3>
            <p>Your OTP code is:</p>
            <h2>$otpCode</h2>
            <p>This code is valid for 10 minutes.</p>
            <p>Do not share this code with anyone.</p>
        ";

        return $mail->send();
    } catch (Exception $e) {
    return $mail->ErrorInfo;
    }
}
?>