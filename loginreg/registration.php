<?php
include '../config/db.php';

$message = "";
$toastColor = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $tip_email = trim($_POST['tip_email']);
    $student_number = trim($_POST['student_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $department = $_POST['department'];

    
    if (!preg_match("/^[a-zA-Z0-9._%+-]+@tip\.edu\.ph$/", $tip_email)) {
        $message = "Only @tip.edu.ph emails are allowed.";
        $toastColor = "#dc3545";
    } elseif (!filter_var($tip_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $toastColor = "#dc3545";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";
        $toastColor = "#dc3545";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $toastColor = "#dc3545";
    } else {
        // ✅ Check for existing email
        $checkEmailStmt = $conn->prepare("SELECT id FROM userdata WHERE tip_email = ?");
        $checkEmailStmt->bind_param("s", $tip_email);
        $checkEmailStmt->execute();
        $checkEmailStmt->store_result();

        if ($checkEmailStmt->num_rows > 0) {
            $message = "Email ID already exists.";
            $toastColor = "#007bff";
        } else {
            $hashedpassword = password_hash($password, PASSWORD_DEFAULT);

            // ✅ Insert user data
            $token = bin2hex(random_bytes(16));
            
            $stmt = $conn->prepare("INSERT INTO pending_users 
                (first_name, last_name, tip_email, student_number, password, department, token) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            
             // Email verification token
            $stmt->bind_param("sssssss", $first_name, $last_name, $tip_email, $student_number, $hashedpassword, $department, $token);

            if ($stmt->execute()) {
                require_once '../auth/send_verification.php'; // include your email sender

                $email_sent = sendVerification($conn, $first_name, $last_name, $tip_email, $token);

                if ($email_sent === true) {
                    $message = "✅ Account created! Please check your TiP email to verify your account.";
                    $toastColor = "#28a745";
                } else {
                    $message = "⚠️ Account created, but verification email could not be sent.<br>" . htmlspecialchars($email_sent);
                    $toastColor = "#ffc107";
                }

                echo "<script>
                    setTimeout(function(){
                        window.location.href='../loginreg/pending_page.php';
                    }, 10000);
                </script>";
            }
   
            else {
                $message = "Error: " . $stmt->error;
                $toastColor = "#dc3545";
            }
            $stmt->close();
        }
        $checkEmailStmt->close();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ONE-TiP - Sign Up</title>
    <link rel="stylesheet" href="../assets/style.css">

    <style>
        /* ✅ Toast Popup Message */
        .toast {
            visibility: hidden;
            min-width: 300px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 10px;
            padding: 16px;
            position: fixed;
            z-index: 9999;
            left: 50%;
            bottom: 30px;
            transform: translateX(-50%);
            font-size: 15px;
            opacity: 0;
            transition: opacity 0.4s ease, bottom 0.4s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            font-family: "Poppins", sans-serif;
        }

        .toast.show {
            visibility: visible;
            opacity: 1;
            bottom: 50px;
        }

        .bck-link {
            display: inline-block;
            margin-top: 15px;
            text-decoration: underline;
            color: #007bff;
            text-align: center;
            font-size: 14px;
        }
    </style>
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
                <p class="subtitle">Sign up for an account</p>
                
                <form id="registerForm" class="register-form" method="POST">
                    <div class="form-row">
                        <div class="input-group half">
                            <label for="firstName">First Name :</label>
                            <input type="text" id="firstName" name="first_name" required>
                        </div>
                        <div class="input-group half">
                            <label for="lastName">Last Name :</label>
                            <input type="text" id="lastName" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="input-group half">
                            <label for="tipEmail">TiP Email Id :</label>
                            <input type="email" id="tipEmail" name="tip_email" required>
                        </div>
                        <div class="input-group half">
                            <label for="studentNo">Student No. :</label>
                            <input type="text" id="studentNo" name="student_number" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="input-group half">
                            <label for="password">Password :</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="input-group half">
                            <label for="confirmPassword">Confirm Password :</label>
                            <input type="password" id="confirmPassword" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label for="department">Department :</label>
                        <select id="department" name="department" required>
                            <option value="">Select Department</option>
                            <option value="ccs">College of Computer Studies</option>
                            <option value="cea">College of Engineering & Architecture</option>
                            <option value="cbe">College of Business Education</option>
                            <option value="coa">College of Arts</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-primary" id="signupBtn">Sign up now</button>
                    <a href="login.php" class="bck-link">Back to login page</a>
                </form>
            </div>
        </div>
    </div>

    <!-- ✅ Toast Popup -->
    <?php if (!empty($message)) : ?>
        <div id="toast" class="toast" style="background-color: <?= $toastColor ?>;">
            <?= htmlspecialchars($message) ?>
        </div>
        <script>
            const toast = document.getElementById('toast');
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 4000);
        </script>
    <?php endif; ?>

    <script src="../assets/script/registration.js"></script>
    
</body>
</html>
