<?php
session_name('USER_SESSION');
session_start();
require '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$item_id = intval($_POST['item_id'] ?? 0);
$item_type = $_POST['item_type'] ?? '';
$reason = trim($_POST['reason'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($item_id <= 0 || !in_array($item_type, ['marketplace', 'service']) || empty($reason)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    exit();
}

// Check if reports table exists, create if not
$checkTable = "SHOW TABLES LIKE 'reports'";
$tableExists = $conn->query($checkTable);

if ($tableExists->num_rows === 0) {
    $createTable = "CREATE TABLE `reports` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `item_id` int(11) NOT NULL,
        `item_type` enum('marketplace','service') NOT NULL,
        `item_name` varchar(255) DEFAULT NULL,
        `seller_id` int(11) DEFAULT NULL,
        `seller_email` varchar(255) DEFAULT NULL,
        `reason` varchar(100) NOT NULL,
        `description` text,
        `status` enum('pending','emailed','resolved','dismissed') DEFAULT 'pending',
        `resolved_by` int(11) DEFAULT NULL,
        `resolved_at` timestamp NULL DEFAULT NULL,
        `action_taken` varchar(100) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `item_id` (`item_id`),
        KEY `seller_id` (`seller_id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (!$conn->query($createTable)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create reports table']);
        exit();
    }
}

// Check if user already reported this item
$checkQuery = "SELECT id FROM reports WHERE user_id = ? AND item_id = ? AND item_type = ? AND status = 'pending'";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("iis", $user_id, $item_id, $item_type);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
    echo json_encode(['status' => 'error', 'message' => 'You have already reported this item']);
    exit();
}

// Get item details and seller info
if ($item_type === 'marketplace') {
    $itemQuery = "SELECT m.productName as item_name, m.user_id as seller_id, u.tip_email as seller_email 
                  FROM marketplace_items m 
                  JOIN userdata u ON m.user_id = u.id 
                  WHERE m.item_id = ?";
} else {
    $itemQuery = "SELECT s.serviceTitle as item_name, s.user_id as seller_id, u.tip_email as seller_email 
                  FROM service_offers s 
                  JOIN userdata u ON s.user_id = u.id 
                  WHERE s.id = ?";
}

$stmt = $conn->prepare($itemQuery);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$itemData = $stmt->get_result()->fetch_assoc();

if (!$itemData) {
    echo json_encode(['status' => 'error', 'message' => 'Item not found']);
    exit();
}

// Insert report
$insertQuery = "INSERT INTO reports 
                (user_id, item_id, item_type, item_name, seller_id, seller_email, reason, description, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
$stmt = $conn->prepare($insertQuery);
$stmt->bind_param("iissssss", 
    $user_id, 
    $item_id, 
    $item_type, 
    $itemData['item_name'], 
    $itemData['seller_id'], 
    $itemData['seller_email'], 
    $reason, 
    $description
);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Report submitted successfully. Our team will review it shortly.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to submit report: ' . $stmt->error]);
}
?>
