<?php
// ✅ Use role-specific session name
session_name('USER_SESSION');
session_start();
include '../config/db.php';

// ✅ Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    session_destroy();
    header("Location: ../loginreg/login.php");
    exit();
}

// ✅ Check session timeout
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 7200)) {
    session_unset();
    session_destroy();
    header("Location: ../loginreg/login.php?timeout=1");
    exit();
}

$_SESSION['login_time'] = time();

$user_id = $_SESSION['user_id'];

// Fetch current user data
$queryUser = "SELECT * FROM userdata WHERE id = ?";
$stmt = $conn->prepare($queryUser);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// ✅ Refresh user data to get latest profile photo
$stmt = $conn->prepare($queryUser);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$message = "";
$messageType = "";

// ✅ Check for success/error messages
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "✅ Profile updated successfully!";
    $messageType = "success";
} elseif (isset($_GET['error']) && $_GET['error'] == 1) {
    $message = "❌ Error updating profile. Please try again.";
    $messageType = "error";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ONE-TiP - Edit Profile</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/dashboard.css">
    <style>
        .edit-profile-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .profile-photo-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .current-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #ffc107;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .current-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .photo-upload {
            text-align: center;
        }
        
        .upload-label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #ffc107;
            color: #333;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .upload-label:hover {
            background: #e0a800;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .input-group {
            margin-bottom: 1.5rem;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .input-group input,
        .input-group select,
        .input-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: 'Poppins', sans-serif;
            transition: border-color 0.3s;
        }
        
        .input-group input:focus,
        .input-group select:focus,
        .input-group textarea:focus {
            outline: none;
            border-color: #ffc107;
        }
        
        .input-group input[readonly] {
            background: #f5f5f5;
            cursor: not-allowed;
        }
        
        .input-group small {
            display: block;
            margin-top: 0.25rem;
            color: #666;
            font-size: 0.85rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid #f0f0f0;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .edit-profile-container {
                margin: 1rem;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="edit-profile-container">
        <div class="profile-header">
            <h1>Edit Profile</h1>
            <p style="color: #666;">Keep your information up to date</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <form id="editProfileForm" class="edit-profile-form" method="POST" action="update-profile.php" enctype="multipart/form-data">
            
            <div class="profile-photo-section">
                <div class="current-photo">
                    <img src="<?= !empty($user['profile_photo']) && file_exists($user['profile_photo']) ? htmlspecialchars($user['profile_photo']) : '../assets/Images/profile-icon.png' ?>" 
                         alt="Profile Photo" id="currentProfilePhoto">
                </div>
                <div class="photo-upload">
                    <label for="profilePhoto" class="upload-label">
                        <img src="../assets/Images/folder-icon.svg" alt="Upload" style="width: 20px; height: 20px; filter: brightness(0) saturate(100%) invert(20%);">
                        Change Photo
                    </label>
                    <input type="file" id="profilePhoto" name="profile_photo" accept="image/*" style="display: none;">
                </div>
            </div>
            
            <div class="form-row">
                <div class="input-group">
                    <label for="editFirstName">First Name *</label>
                    <input type="text" id="editFirstName" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required maxlength="50">
                </div>
                <div class="input-group">
                    <label for="editLastName">Last Name *</label>
                    <input type="text" id="editLastName" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required maxlength="50">
                </div>
            </div>
            
            <div class="input-group">
                <label for="editUsername">Username</label>
                <input type="text" id="editUsername" name="username" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                <small>Username cannot be changed after registration</small>
            </div>
            
            <div class="input-group">
                <label for="editEmail">Email Address</label>
                <input type="email" id="editEmail" name="email" value="<?= htmlspecialchars($user['tip_email']) ?>" readonly>
                <small>Email cannot be changed. Contact support if needed.</small>
            </div>
            
            <div class="input-group">
                <label for="editBio">Bio</label>
                <textarea id="editBio" name="bio" placeholder="Tell others about yourself..." rows="4" maxlength="500"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                <small id="bioCount"><?= strlen($user['bio'] ?? '') ?>/500 characters</small>
            </div>
            
            <div class="form-row">
                <div class="input-group">
                    <label for="editDepartment">Department *</label>
                    <select id="editDepartment" name="department" required>
                        <option value="CAS" <?= $user['department'] === 'CAS' ? 'selected' : '' ?>>College of Arts and Sciences</option>
                        <option value="CEA" <?= $user['department'] === 'CEA' ? 'selected' : '' ?>>College of Engineering and Architecture</option>
                        <option value="CCS" <?= $user['department'] === 'CCS' ? 'selected' : '' ?>>College of Computer Science</option>
                        <option value="CBE" <?= $user['department'] === 'CBE' ? 'selected' : '' ?>>College of Business Education</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="editCampus">Campus</label>
                    <select id="editCampus" name="campus">
                        <option value="Arlegui" <?= ($user['campus'] ?? 'Arlegui') === 'Arlegui' ? 'selected' : '' ?>>Arlegui Campus</option>
                        <option value="Casal" <?= ($user['campus'] ?? 'Arlegui') === 'Casal' ? 'selected' : '' ?>>Casal Campus</option>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="dashboard.php" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary" id="saveProfileBtn">Save Changes</button>
            </div>
        </form>
    </div>
    
    <script>
        // Character counter for bio
        const bioTextarea = document.getElementById('editBio');
        const bioCount = document.getElementById('bioCount');
        
        bioTextarea.addEventListener('input', function() {
            const length = this.value.length;
            bioCount.textContent = `${length}/500 characters`;
        });
        
        // Profile photo preview
        const profilePhotoInput = document.getElementById('profilePhoto');
        const currentProfilePhoto = document.getElementById('currentProfilePhoto');
        
        profilePhotoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    currentProfilePhoto.src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
