<?php
// ✅ DON'T start session yet - we'll start it based on role
require '../config/db.php';

$error = "";
$ban_reason = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Check user credentials
    $stmt = $conn->prepare("SELECT id, username, password, role, status, ban_reason FROM userdata WHERE tip_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // ✅ Check if user is banned
        if ($user['status'] === 'banned') {
            $ban_reason = !empty($user['ban_reason']) ? $user['ban_reason'] : 'violation of terms of service';
            $error = "banned";
        }
        // ✅ Check if user is suspended
        elseif ($user['status'] === 'suspended') {
            $ban_reason = !empty($user['ban_reason']) ? $user['ban_reason'] : 'account suspension';
            $error = "suspended";
        }
        // Verify password if account is active
        elseif (password_verify($password, $user['password'])) {
            // ✅ START SESSION WITH ROLE-BASED NAME
            if ($user['role'] === 'admin') {
                session_name('ADMIN_SESSION');
            } else {
                session_name('USER_SESSION');
            }
            
            session_start();
            session_regenerate_id(true); // Prevent session fixation
            
            // ✅ Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['tip_email'] = $email;
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../users/dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ONE-TiP</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
    /* Toast notification styles */
    .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        background: #dc3545;
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        display: none;
        animation: slideIn 0.3s ease;
        max-width: 400px;
    }

    .toast.show {
        display: block;
    }

    .toast.banned,
    .toast.suspended {
        background: #721c24;
        border-left: 4px solid #f8d7da;
    }

    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .toast-close {
        float: right;
        cursor: pointer;
        margin-left: 10px;
        font-size: 20px;
        line-height: 20px;
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
                <p class="subtitle">Login into your account</p>

                <form id="loginForm" class="login-form" method="POST">
                    <div class="input-group">
                        <label for="email">TiP Email :</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="input-group">
                        <label for="password">Password :</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <a href="../auth/forgot_password.php" class="forgot-link">Forgot password?</a>
                    <button type="submit" class="btn-primary" id="loginBtn">Login now</button>
                </form>

                <div class="divider"><span>Or</span></div>
                <a href="registration.php" class="btn-secondary">Signup now</a>
            </div>
        </div>
    </div>

    <!-- ✅ Toast Notification -->
    <?php if (!empty($message)): ?>
    <div id="toast" style="
        background-color: <?= $toastColor ?>;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        padding: 16px 28px;
        border-radius: 12px;
        color: #fff;
        font-family: 'Poppins', sans-serif;
        font-size: 16px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        z-index: 9999;
        text-align: center;
        animation: fadeInOut 3s ease forwards;
    ">
        <?= htmlspecialchars($message) ?>
    </div>

    <style>
    @keyframes fadeInOut {
      0% { opacity: 0; transform: translate(-50%, -55%); }
      10%, 90% { opacity: 1; transform: translate(-50%, -50%); }
      100% { opacity: 0; transform: translate(-50%, -45%); }
    }
    </style>

    <script>
    setTimeout(() => {
      const toast = document.getElementById('toast');
      if (toast) toast.remove();

      // ✅ Auto redirect if login successful
      <?php if (isset($_SESSION['redirect_target']) && strpos($message, 'successful') !== false): ?>
        window.location.href = "<?= $_SESSION['redirect_target'] ?>";
        <?php unset($_SESSION['redirect_target']); ?>
      <?php endif; ?>
    }, 2000);
    </script>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <?php if ($error === 'banned'): ?>
            <!-- Ban toast message -->
            <div class="toast banned show" id="banToast">
                <span class="toast-close" onclick="this.parentElement.style.display='none'">&times;</span>
                <strong>❌ Account Banned</strong>
                <p>Your account has been banned due to: <strong><?= htmlspecialchars($ban_reason) ?></strong></p>
                <p>Please contact the Moderator for assistance.</p>
            </div>
        <?php elseif ($error === 'suspended'): ?>
            <!-- Suspend toast message -->
            <div class="toast suspended show" id="suspendToast">
                <span class="toast-close" onclick="this.parentElement.style.display='none'">&times;</span>
                <strong>⚠️ Account Suspended</strong>
                <p>Your account has been suspended due to: <strong><?= htmlspecialchars($ban_reason) ?></strong></p>
                <p>Please contact the Moderator for assistance.</p>
            </div>
        <?php else: ?>
            <!-- Regular error message -->
            <div class="toast show" id="errorToast">
                <span class="toast-close" onclick="this.parentElement.style.display='none'">&times;</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <script>
    // Auto-hide toast after 8 seconds
    setTimeout(() => {
        const toast = document.getElementById('banToast') || 
                     document.getElementById('suspendToast') || 
                     document.getElementById('errorToast');
        if (toast) {
            toast.style.display = 'none';
        }
    }, 8000);
    </script>

</body>
</html>
