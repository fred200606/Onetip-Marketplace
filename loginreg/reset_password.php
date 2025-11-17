<?php
include '../config/db.php';
$message = "";
$toastColor = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT tip_email, reset_expires FROM userdata WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($tip_email, $reset_expires);
        $stmt->fetch();

        if (strtotime($reset_expires) > time()) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $newPassword = $_POST['password'];
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                $update = $conn->prepare("UPDATE userdata SET password = ?, reset_token = NULL, reset_expires = NULL WHERE tip_email = ?");
                $update->bind_param("ss", $hashedPassword, $tip_email);
                $update->execute();

                $message = "Password successfully updated.";
                $toastColor = "#28a745";

                echo "<script>
                    setTimeout(function(){
                        window.location.href='../loginreg/login.php';
                    }, 1000);
                </script>";
            }
        } else {
            $message = "Reset link has expired.";
            $toastColor = "#dc3545";
        }
    } else {
        $message = "Invalid or expired token.";
        $toastColor = "#dc3545";
    }
} else {
    $message = "No token provided.";
    $toastColor = "#dc3545";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - ONE-TiP</title>
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

                <p class="subtitle">Reset your password?</p>

                <div class="icon-circle password-reset-icon">
                    <img src="../assets/Images/password-svgrepo-com.svg" alt="Password Reset" class="icon-img">
                </div>

                <h2>Reset your Password</h2>
                <p class="description">Make sure to create a strong password!</p>

                <form id="forgotPasswordForm" class="forgot-password-form" method="POST">
                    <div class="input-group">
                        <label for="password">New Password: </label>
                        <input type="password" id="password" name="password" 
                               placeholder="Enter New Password" required>
                    </div>
                    <button type="submit" class="btn-primary" id="newpassword">Reset Password</button>
                </form>

                <a href="../loginreg/login.php" class="btn-link">Back to login page</a>
            </div>
        </div>
    </div>
   
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
    <?php endif; ?>
</body>
</html>
