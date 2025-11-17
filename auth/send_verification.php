<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; 
include '../config/db.php'; // ensures $conn exists

function sendVerification($conn, $first_name, $last_name, $tip_email, $token) {
    // Update token in database
    $stmt = $conn->prepare("UPDATE userdata SET email_verification_token = ? WHERE tip_email = ?");
    $stmt->bind_param("ss", $token, $tip_email);
    $stmt->execute();
    $stmt->close();

    // Send email with PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'onetip.mnl@gmail.com'; // your Gmail
        $mail->Password = 'squm pdhx ondu iwbc';   // app password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('onetip.mnl@gmail.com', 'ONE-TiP Marketplace');
        $mail->addAddress($tip_email, $first_name . ' ' . $last_name);
        $mail->isHTML(true);
        $mail->Subject = 'Verify your ONE-TiP Account';
        $mail->Body = "
            <h3>Hi $first_name,</h3>
            <p>Thanks for signing up! Please verify your email by clicking the link below:</p>
            <p><a href='http://localhost/0neTip/auth/verify.php?token=$token' style='background:#4CAF50;color:white;padding:10px 15px;text-decoration:none;border-radius:6px;'>Verify My Email</a></p>
        ";
        $mail->send();

        return true;
    } catch (Exception $e) {
        return "❌ Email could not be sent. Error: {$mail->ErrorInfo}";
    }
}
