<?php
// ✅ Use role-specific session name FIRST
session_name('USER_SESSION');
session_start();
include "../config/db.php";

header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ✅ Check if user is logged in with correct role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['status' => 'error', 'message' => '❌ Unauthorized: Please log in as a student.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// ✅ Debug: Verify user_id
error_log("ServiceOffer.php - User ID from session: " . $user_id);
error_log("ServiceOffer.php - Session data: " . print_r($_SESSION, true));

// ====== Check request method ======
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => '❌ Invalid request method.']);
    exit;
}

// ====== Sanitize and validate inputs ======
$serviceTitle = trim($_POST['serviceTitle'] ?? '');
$startingPrice = trim($_POST['startingPrice'] ?? '');
$serviceCategory = trim($_POST['serviceCategory'] ?? '');
$serviceDuration = trim($_POST['serviceDuration'] ?? '');
$serviceDescription = trim($_POST['serviceDescription'] ?? '');
$providerBio = trim($_POST['providerBio'] ?? '');
$contactFacebook = trim($_POST['contactFacebook'] ?? '');
$contactEmail = trim($_POST['contactEmail'] ?? '');
$contactChat = trim($_POST['contactChat'] ?? '');
$contactMeetup = trim($_POST['contactMeetup'] ?? '');

if (empty($serviceTitle) || empty($startingPrice)) {
    echo json_encode(['status' => 'error', 'message' => '❌ Service title and price are required.']);
    exit;
}

if (!is_numeric($startingPrice) || $startingPrice <= 0) {
    echo json_encode(['status' => 'error', 'message' => '❌ Price must be a positive number.']);
    exit;
}

// ====== Handle Service Image Upload ======
$serviceImgPaths = [];
if (!empty($_FILES['serviceImages']['name'][0])) {
    $targetDir = "../uploads/pending_services/";
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

    foreach ($_FILES['serviceImages']['name'] as $key => $name) {
        if ($_FILES['serviceImages']['error'][$key] === UPLOAD_ERR_OK) {
            $fileName = uniqid() . '_' . basename($name);
            $targetFile = $targetDir . $fileName;
            $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($imageFileType, $allowedTypes) && getimagesize($_FILES['serviceImages']['tmp_name'][$key])) {
                if (move_uploaded_file($_FILES['serviceImages']['tmp_name'][$key], $targetFile)) {
                    $serviceImgPaths[] = $targetFile;
                }
            }
        }
    }
}
$serviceImages = implode(',', $serviceImgPaths);

// ====== Insert Service Offer ======
$status = "pending";
$submitted_at = date('Y-m-d H:i:s');

$sql = "INSERT INTO pending_services 
        (user_id, serviceTitle, serviceCategory, startingPrice, serviceDescription, serviceDuration, 
         serviceImages, providerBio, contactFacebook, contactEmail, contactChat, contactMeetup, status, submitted_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => '❌ DB Prepare Error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "issdssssssssss",
    $user_id,
    $serviceTitle,
    $serviceCategory,
    $startingPrice,
    $serviceDescription,
    $serviceDuration,
    $serviceImages,
    $providerBio,
    $contactFacebook,
    $contactEmail,
    $contactChat,
    $contactMeetup,
    $status,
    $submitted_at
);

// ✅ Debug: Log the user_id being inserted
error_log("ServiceOffer.php - Inserting with user_id: " . $user_id);

if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'error', 'message' => '❌ DB Insert Error: ' . mysqli_error($conn)]);
    exit;
}

$new_service_id = mysqli_insert_id($conn);

// ====== Handle Portfolio Items (if any) ======
if (!empty($_POST['portfolio_title'])) {
    $titles = $_POST['portfolio_title'];
    $descriptions = $_POST['portfolio_description'] ?? [];
    $images = $_POST['portfolio_image'] ?? [];
    
    $portfolioSQL = "INSERT INTO service_portfolio (pending_service_id, title, description, image_url) VALUES (?, ?, ?, ?)";
    $portfolioStmt = mysqli_prepare($conn, $portfolioSQL);
    
    foreach ($titles as $index => $title) {
        if (!empty($title)) {
            $desc = $descriptions[$index] ?? '';
            $img = $images[$index] ?? '';
            
            mysqli_stmt_bind_param($portfolioStmt, "isss", $new_service_id, $title, $desc, $img);
            mysqli_stmt_execute($portfolioStmt);
        }
    }
    
    mysqli_stmt_close($portfolioStmt);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode([
    'status' => 'success',
    'message' => "✅ Your service has been submitted and is pending approval!",
    'service_id' => $new_service_id
]);
?>
