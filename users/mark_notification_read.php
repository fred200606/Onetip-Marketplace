<?php
session_name('USER_SESSION');
session_start();
require '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$notif_id = (int)$_POST['notif_id'];

// Mark as read
$updateQuery = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
$updateQuery->bind_param("ii", $notif_id, $user_id);

if ($updateQuery->execute()) {
    // Get unread count
    $countQuery = $conn->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $countQuery->bind_param("i", $user_id);
    $countQuery->execute();
    $unread = $countQuery->get_result()->fetch_assoc()['unread'];
    
    echo json_encode([
        'status' => 'success',
        'unread_count' => $unread
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
