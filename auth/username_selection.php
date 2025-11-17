<?php
session_start();
include '../config/db.php';

$message = "";
$toastColor = "";

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../loginreg/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $user_id = $_SESSION['user_id']; // Get current user ID from session

    // Check if username already exists
    $checkusername = $conn->prepare("SELECT id FROM userdata WHERE username = ?");
    $checkusername->bind_param("s", $username);
    $checkusername->execute();
    $checkusername->store_result();

    if ($checkusername->num_rows > 0) {
        $message = "Username already exists.";
        $toastColor = "#dc3545"; // Red
    } else {
        // ✅ Update current user's record instead of inserting new one
        $stmt = $conn->prepare("UPDATE userdata SET username = ? WHERE id = ?");
        $stmt->bind_param("si", $username, $user_id);

        if ($stmt->execute()) {
            $message = "Username successfully set!";
            $toastColor = "#28a745"; // Green

            header("refresh:2;url=../users/dashboard.php");
        } else {
            $message = "Error saving username. Try again.";
            $toastColor = "#dc3545";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ONE-TiP - Choose Username</title>
    <link rel="stylesheet" href="../assets/style.css">

    <style>
        .status-message {
            margin-top: 5px;
            font-size: 14px;
            height: 18px;
        }
        .status-message.valid {
            color: #28a745; /* Green */
        }
        .status-message.invalid {
            color: #dc3545; /* Red */
        }
        .username-rules {
            font-size: 13px;
            color: #555;
            margin-top: 10px;
        }
        .username-rules ul {
            padding-left: 20px;
        }
        .username-rules li {
            margin-bottom: 5px;
        }
        .btn-primary:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        .toast {
            visibility: hidden;
            min-width: 250px;
            margin-left: -125px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 2px;
            padding: 16px;
            position: fixed;
            z-index: 1;
            left: 50%;
            bottom: 30px;
            font-size: 17px;
        }
        .toast.show {
            visibility: visible;
            -webkit-animation: fadein 0.5s, fadeout 0.5s 3.5s;
            animation: fadein 0.5s, fadeout 0.5s 3.5s;
        }
        @-webkit-keyframes fadein {
            from {bottom: 0; opacity: 0;} 
            to {bottom: 30px; opacity: 1;}
        }
        @keyframes fadein {
            from {bottom: 0; opacity: 0;}
            to {bottom: 30px; opacity: 1;}
        }
        @-webkit-keyframes fadeout {
            from {bottom: 30px; opacity: 1;} 
            to {bottom: 0; opacity: 0;}
        }
        @keyframes fadeout {
            from {bottom: 30px; opacity: 1;}
            to {bottom: 0; opacity: 0;}
        }
        .bck-link {
            display: inline-block;
            margin-top: 15px;
            text-decoration: underline;
            color: #007bff;
            text
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
                <p class="subtitle">You passed the test!</p>
                
                <h2>Choose your username</h2>
                <p class="description">This will be your unique identifier. Choose something memorable!</p>
                
                <form id="usernameForm" class="username-form"  method="POST">
                    <input type="hidden" name="action" value="set_username">
                    <input type="hidden" name="user_id" id="userId" value="">
                    <input type="hidden" name="csrf_token" id="csrfToken" value="">
                    
                    <div class="input-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="Enter your username" required 
                               pattern="[a-zA-Z0-9_]{3,20}" minlength="3" maxlength="20">
                        <div id="usernameStatus" class="status-message"></div>
                    </div>
                    
                    <div class="username-rules">
                        <h4>Username rules:</h4>
                        <ul>
                            <li>3-20 characters</li>
                            <li>Letters, numbers, and underscores only</li>
                            <li>Must be unique</li>
                            <li>No rude or explicit language</li>
                            <li>That's it!</li>
                        </ul>
                    </div>
                    
                    <button type="submit" class="btn-primary" id="completeSetupBtn">Complete setup with @username</button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../assets/script/username.js"></script>
</body>
</html>