<?php
// ✅ Use role-specific session name FIRST
session_name('USER_SESSION');
session_start();
include "../config/db.php"; // Database connection

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
error_log("ItemOffer.php - User ID from session: " . $user_id);
error_log("ItemOffer.php - Session data: " . print_r($_SESSION, true));

// ====== Check request method ======
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => '❌ Invalid request method.']);
    exit;
}

// ====== Sanitize and validate inputs ======
$productName        = trim($_POST['productName'] ?? '');
$productPrice       = trim($_POST['productPrice'] ?? '');
$productCategory    = trim($_POST['productCategory'] ?? '');
$productCondition   = trim($_POST['productCondition'] ?? '');
$productDescription = trim($_POST['productDescription'] ?? '');
$sellerBio          = trim($_POST['sellerBio'] ?? '');
$sellerFacebook     = trim($_POST['sellerFacebook'] ?? '');
$sellerEmail        = trim($_POST['sellerEmail'] ?? '');
$sellerChat         = trim($_POST['sellerChat'] ?? '');
$sellerMeetup       = trim($_POST['sellerMeetup'] ?? '');

if (empty($productName) || empty($productPrice)) {
    echo json_encode(['status' => 'error', 'message' => '❌ Product name and price are required.']);
    exit;
}

if (!is_numeric($productPrice) || $productPrice <= 0) {
    echo json_encode(['status' => 'error', 'message' => '❌ Product price must be a positive number.']);
    exit;
}

// ====== Handle Image Upload ======
$productImgPaths = [];
if (!empty($_FILES['productImg']['name'][0])) {
    $targetDir = "../uploads/";
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

    foreach ($_FILES['productImg']['name'] as $key => $name) {
        if ($_FILES['productImg']['error'][$key] === UPLOAD_ERR_OK) {
            $fileName = uniqid() . '_' . basename($name);
            $targetFile = $targetDir . $fileName;
            $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($imageFileType, $allowedTypes) && getimagesize($_FILES['productImg']['tmp_name'][$key])) {
                if (move_uploaded_file($_FILES['productImg']['tmp_name'][$key], $targetFile)) {
                    $productImgPaths[] = $targetFile;
                }
            }
        }
    }
}
$productImg = implode(',', $productImgPaths);

// ====== Prepare Insert Query ======
$status = "pending";
$submitted_at = date('Y-m-d H:i:s');

$sql = "INSERT INTO pending_post 
        (user_id, productName, productPrice, productCategory, productCondition, productDescription, productImg, 
         sellerBio, sellerFacebook, sellerEmail, sellerChat, sellerMeetup, status, submitted_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => '❌ DB Prepare Error: ' . mysqli_error($conn)]);
    exit;
}

// ✅ Corrected bind types
mysqli_stmt_bind_param(
    $stmt,
    "isdsssssssssss",
    $user_id,
    $productName,
    $productPrice,
    $productCategory,
    $productCondition,
    $productDescription,
    $productImg,
    $sellerBio,
    $sellerFacebook,
    $sellerEmail,
    $sellerChat,
    $sellerMeetup,
    $status,
    $submitted_at
);

// ✅ Debug: Log the user_id being inserted
error_log("ItemOffer.php - Inserting with user_id: " . $user_id);

// ====== Execute Query ======
if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'error', 'message' => '❌ DB Insert Error: ' . mysqli_error($conn)]);
    exit;
}

// ====== Get Auto Increment ID ======
$new_id = mysqli_insert_id($conn);
$product_label = "product#" . str_pad($new_id, 5, '0', STR_PAD_LEFT);

mysqli_stmt_close($stmt);
mysqli_close($conn);

// ====== Success Response ======
echo json_encode([
    'status' => 'success',
    'message' => "✅ Your marketplace item has been submitted and is pending approval!",
    'product_id' => $new_id,
    'display_id' => $product_label
]);
?>