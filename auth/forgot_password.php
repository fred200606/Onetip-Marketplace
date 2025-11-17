<?php
session_start();
include '../config/db.php'; // Database connection
require '../vendor/autoload.php'; // For PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";
$toastColor = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = trim($_POST['email_or_username']);

    if (empty($input)) {
        $message = "Please enter your email or username.";
        $toastColor = "#dc3545";
    } else {
        // Determine if user entered email or username
        if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
            $query = "SELECT id, tip_email FROM userdata WHERE tip_email = ?";
        } else {
            $query = "SELECT id, tip_email FROM userdata WHERE username = ?";
        }

        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $input);
        $stmt->execute();
        $result = $stmt->get_result();

        // If user exists
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $email = $user['tip_email'];

            // Generate token and expiration time
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

            // Update token in database
            $update = $conn->prepare("UPDATE userdata SET reset_token = ?, reset_expires = ? WHERE tip_email = ?");
            $update->bind_param("sss", $token, $expires, $email);
            $update->execute();

            // Create reset link
            $resetLink = "http://localhost/0neTip/loginreg/reset_password.php?token=$token";

            // Email content
            $subject = "ONE-TiP Password Reset Request";
            $body = "
                <p>Hello,</p>
                <p>We received a request to reset your password for your ONE-TiP account.</p>
                <p>Click the link below to reset your password:</p>
                <p><a href='$resetLink' style='background:#4CAF50;color:white;padding:10px 15px;text-decoration:none;border-radius:6px;'>Reset My Password</a></p>
                <p>This link will expire in 1 hour.</p>
                <p>If you didn’t request this, you can safely ignore this email.</p>
            ";

            // Send reset email via PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'onetip.mnl@gmail.com'; // your Gmail
                $mail->Password = 'rdrn mian lyju ygwe';    // Gmail app password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('onetip.mnl@gmail.com', 'ONE-TiP Marketplace');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $body;

                $mail->send();

                $message = "✅ Password reset link sent! Please check your TiP email.";
                $toastColor = "#28a745"; // Green
            } catch (Exception $e) {
                $message = "❌ Failed to send email. Error: {$mail->ErrorInfo}";
                $toastColor = "#dc3545"; // Red
            }

        } else {
            $message = "No account found with that email or username.";
            $toastColor = "#dc3545"; // Red
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ONE-TiP - Forgot Password</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <img src="../assets/Images/TIPImage-Login.jpg" alt="School Building" class="background-image">
        </div>

        <div class="right-panel">
            <div class="form-container">
                <div class="logo">
                    <img src="../assets/Images/LOGO-LONG.png" alt="ONE-TiP" class="logo-img">
                </div>

                <p class="subtitle">Forgot your password?</p>

                <div class="icon-circle password-reset-icon">
                    <img src="../assets/Images/password-svgrepo-com.svg" alt="Password Reset" class="icon-img">
                </div>

                <h2>Reset your password</h2>
                <p class="description">No worries! Enter your TiP email or username and we'll send you a reset link.</p>

                <form id="forgotPasswordForm" class="forgot-password-form" method="POST">
                    <div class="input-group">
                        <label for="emailOrUsername">Email or Username:</label>
                        <input type="text" id="emailOrUsername" name="email_or_username" 
                               placeholder="Enter your email or username" required>
                    </div>
                    <button type="submit" class="btn-primary" id="sendResetBtn">Send Reset Link</button>
                </form>

                <a href="../loginreg/login.php" class="btn-link">Back to login page</a>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('forgotPasswordForm');
        const input = document.getElementById('emailOrUsername');
        const btn = document.getElementById('sendResetBtn');

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const value = input.value.trim();
            if (!value) {
                alert('Please enter your email or username.');
                return;
            }

            btn.textContent = 'Sending...';
            btn.disabled = true;

            setTimeout(() => form.submit(), 1000);
        });
    });
    </script>

    <?php if (!empty($message)): ?>
        <div id="toast" style="
            background-color: <?= $toastColor ?>;
            position: fixed;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            padding: 14px 24px;
            border-radius: 10px;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            z-index: 9999;
            text-align: center;
        ">
            <?= htmlspecialchars($message) ?>
        </div>
        <script>
            setTimeout(() => document.getElementById('toast').remove(), 4000);
        </script>

        <script src="../assets/forgot_password.js"></script>

    <?php endif; ?>
</body>
</html>
