<?php
include '../config/db.php';

$message = "";
$toastColor = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // ✅ 1. Check in pending_users table
    $stmt = $conn->prepare("SELECT * FROM pending_users WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // ✅ 2. Check if already verified (exists in userdata)
        $check = $conn->prepare("SELECT * FROM userdata WHERE tip_email = ?");
        $check->bind_param("s", $user['tip_email']);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {
            // Already verified — block duplicate verification
            $message = "Your account has already been verified. Please log in.";
            $toastColor = "#ffc107";
        } else {
            // ✅ 3. Move user to main userdata table
            $insert = $conn->prepare("INSERT INTO userdata 
                (first_name, last_name, tip_email, student_number, password, department, role, is_verified, status)
                VALUES (?, ?, ?, ?, ?, ?, 'student', 1, 'active')");
            $insert->bind_param("ssssss", 
                $user['first_name'], 
                $user['last_name'], 
                $user['tip_email'], 
                $user['student_number'], 
                $user['password'], 
                $user['department']
            );

            if ($insert->execute()) {
                // ✅ 4. Delete from pending_users
                $delete = $conn->prepare("DELETE FROM pending_users WHERE id = ?");
                $delete->bind_param("i", $user['id']);
                $delete->execute();
                $delete->close();

                $message = "✅ Email verified successfully! You can now log in.";
                $toastColor = "#28a745";
            } else {
                $message = "Something went wrong while verifying your account.";
                $toastColor = "#dc3545";
            }
            $insert->close();
        }

        $check->close();
    } else {
        // Token not found or already deleted
        $message = "Invalid or expired verification link.";
        $toastColor = "#dc3545";
    }

    $stmt->close();
} else {
    $message = "No verification token provided.";
    $toastColor = "#ffc107";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification | ONE-TiP</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body {
            display: flex; justify-content: center; align-items: center;
            height: 100vh; background-color: #f4f6f9;
            font-family: "Poppins", sans-serif;
        }
        .toast {
            background-color: <?= $toastColor ?>;
            color: #fff; padding: 18px 25px; border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            text-align: center; font-size: 16px;
            animation: pop 0.4s ease;
        }
        @keyframes pop { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body>
    <div class="toast"><?= htmlspecialchars($message) ?></div>
    <?php if (strpos($message, 'successfully') !== false): ?>
        <script>
            setTimeout(() => {
                window.location.href = "../loginreg/login.php";
            }, 2500);
        </script>
    <?php endif; ?>
</body>
</html>
