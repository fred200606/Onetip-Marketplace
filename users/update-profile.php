<?php
session_name('USER_SESSION');
session_start();
include '../config/db.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: edit-profile.php?error=1");
    exit();
}

$user_id = $_SESSION['user_id'];

// Validate POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: edit-profile.php?error=1");
    exit();
}

// Sanitize inputs
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$bio = trim($_POST['bio'] ?? '');
$department = trim($_POST['department'] ?? '');
$campus = trim($_POST['campus'] ?? 'Arlegui');

// Validate required fields
if (empty($first_name) || empty($last_name) || empty($department)) {
    header("Location: edit-profile.php?error=1");
    exit();
}

// Handle profile photo upload
$profile_photo = null;
if (!empty($_FILES['profile_photo']['name']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $targetDir = "../uploads/profiles/";
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
    
    $fileName = uniqid() . '_' . basename($_FILES['profile_photo']['name']);
    $targetFile = $targetDir . $fileName;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    
    // Validate image
    $check = getimagesize($_FILES['profile_photo']['tmp_name']);
    if ($check !== false && in_array($imageFileType, $allowedTypes)) {
        if ($_FILES['profile_photo']['size'] <= 5000000) { // 5MB max
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetFile)) {
                $profile_photo = $targetFile;
                
                // Delete old profile photo
                $oldPhotoQuery = $conn->prepare("SELECT profile_photo FROM userdata WHERE id = ?");
                $oldPhotoQuery->bind_param("i", $user_id);
                $oldPhotoQuery->execute();
                $oldPhotoResult = $oldPhotoQuery->get_result()->fetch_assoc();
                
                if (!empty($oldPhotoResult['profile_photo']) && file_exists($oldPhotoResult['profile_photo'])) {
                    unlink($oldPhotoResult['profile_photo']);
                }
            }
        }
    }
}

// Update database
if ($profile_photo) {
    $sql = "UPDATE userdata SET first_name = ?, last_name = ?, bio = ?, department = ?, campus = ?, profile_photo = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $first_name, $last_name, $bio, $department, $campus, $profile_photo, $user_id);
} else {
    $sql = "UPDATE userdata SET first_name = ?, last_name = ?, bio = ?, department = ?, campus = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $first_name, $last_name, $bio, $department, $campus, $user_id);
}

if ($stmt->execute()) {
    // Update session data
    $_SESSION['username'] = $first_name . ' ' . $last_name;
    
    // Redirect back to dashboard with success message
    header("Location: dashboard.php?profile_updated=1");
    exit();
} else {
    error_log("Profile update error: " . $stmt->error);
    header("Location: edit-profile.php?error=1");
    exit();
}
?>
