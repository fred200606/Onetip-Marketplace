<?php
session_name('USER_SESSION');
session_start();
require '../config/db.php';

header('Content-Type: application/json');

// Authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Sanitize message text
function sanitizeMessage($text) {
    return htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');
}

switch ($action) {
    case 'create_chat':
        $seller_id = intval($_POST['seller_id'] ?? 0);
        $item_id = intval($_POST['item_id'] ?? 0);
        $item_type = $_POST['item_type'] ?? 'marketplace';
        
        if ($seller_id <= 0 || $item_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
            exit();
        }
        
        if ($seller_id == $user_id) {
            echo json_encode(['status' => 'error', 'message' => 'Cannot message yourself']);
            exit();
        }
        
        // Check if item exists
        if ($item_type === 'marketplace') {
            $checkItem = $conn->prepare("SELECT item_id, user_id FROM marketplace_items WHERE item_id = ?");
        } else {
            $checkItem = $conn->prepare("SELECT id as item_id, user_id FROM service_offers WHERE id = ?");
        }
        $checkItem->bind_param("i", $item_id);
        $checkItem->execute();
        $itemResult = $checkItem->get_result();
        
        if ($itemResult->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Item not found']);
            exit();
        }
        
        $itemData = $itemResult->fetch_assoc();
        if ($itemData['user_id'] != $seller_id) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid seller for this item']);
            exit();
        }
        
        // Check if chat room already exists
        $query = "SELECT chat_id FROM marketplace_chat_rooms 
                  WHERE buyer_id = ? AND seller_id = ? AND item_id = ? AND item_type = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiis", $user_id, $seller_id, $item_id, $item_type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $chat = $result->fetch_assoc();
            echo json_encode(['status' => 'success', 'chat_id' => $chat['chat_id'], 'existing' => true]);
        } else {
            // Create new chat room
            $insert = "INSERT INTO marketplace_chat_rooms (buyer_id, seller_id, item_id, item_type) 
                       VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert);
            $stmt->bind_param("iiis", $user_id, $seller_id, $item_id, $item_type);
            
            if ($stmt->execute()) {
                $chat_id = $stmt->insert_id;
                
                // Initialize chat status for both users
                $initStatus = "INSERT INTO marketplace_chat_status (chat_id, user_id, unread_count) VALUES (?, ?, 0), (?, ?, 0)";
                $stmtStatus = $conn->prepare($initStatus);
                $stmtStatus->bind_param("iiii", $chat_id, $user_id, $chat_id, $seller_id);
                $stmtStatus->execute();
                
                echo json_encode(['status' => 'success', 'chat_id' => $chat_id, 'existing' => false]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create chat room']);
            }
        }
        break;
        
    case 'send_message':
        $chat_id = intval($_POST['chat_id'] ?? 0);
        $message_text = $_POST['message_text'] ?? '';
        $image_path = null;
        
        if ($chat_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid chat ID']);
            exit();
        }
        
        // Verify user is part of this chat
        $verify = "SELECT buyer_id, seller_id FROM marketplace_chat_rooms WHERE chat_id = ?";
        $stmt = $conn->prepare($verify);
        $stmt->bind_param("i", $chat_id);
        $stmt->execute();
        $chatData = $stmt->get_result()->fetch_assoc();
        
        if (!$chatData || ($chatData['buyer_id'] != $user_id && $chatData['seller_id'] != $user_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit();
        }
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
            $fileType = $_FILES['image']['type'];
            $fileSize = $_FILES['image']['size'];
            
            if (!in_array($fileType, $allowed)) {
                echo json_encode(['status' => 'error', 'message' => 'Only JPG and PNG images allowed']);
                exit();
            }
            
            if ($fileSize > 2 * 1024 * 1024) {
                echo json_encode(['status' => 'error', 'message' => 'Image size must be less than 2MB']);
                exit();
            }
            
            $uploadDir = '../uploads/chat_images/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'chat_' . $chat_id . '_' . time() . '_' . uniqid() . '.' . $extension;
            $uploadPath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                $image_path = 'uploads/chat_images/' . $filename;
            }
        }
        
        $message_text = sanitizeMessage($message_text);
        
        if (empty($message_text) && empty($image_path)) {
            echo json_encode(['status' => 'error', 'message' => 'Message cannot be empty']);
            exit();
        }
        
        // Insert message
        $insert = "INSERT INTO marketplace_messages (chat_id, sender_id, message_text, image_path, status) 
                   VALUES (?, ?, ?, ?, 'sent')";
        $stmt = $conn->prepare($insert);
        $stmt->bind_param("iiss", $chat_id, $user_id, $message_text, $image_path);
        
        if ($stmt->execute()) {
            $message_id = $stmt->insert_id;
            
            // Update last_message_at
            $updateRoom = "UPDATE marketplace_chat_rooms SET last_message_at = NOW() WHERE chat_id = ?";
            $stmtRoom = $conn->prepare($updateRoom);
            $stmtRoom->bind_param("i", $chat_id);
            $stmtRoom->execute();
            
            // Increment unread count for other user
            $other_user_id = ($chatData['buyer_id'] == $user_id) ? $chatData['seller_id'] : $chatData['buyer_id'];
            $updateUnread = "UPDATE marketplace_chat_status SET unread_count = unread_count + 1 
                            WHERE chat_id = ? AND user_id = ?";
            $stmtUnread = $conn->prepare($updateUnread);
            $stmtUnread->bind_param("ii", $chat_id, $other_user_id);
            $stmtUnread->execute();
            
            echo json_encode([
                'status' => 'success',
                'message_id' => $message_id,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to send message']);
        }
        break;
        
    case 'fetch_messages':
        $chat_id = intval($_GET['chat_id'] ?? 0);
        $last_message_id = intval($_GET['last_message_id'] ?? 0);
        
        if ($chat_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid chat ID']);
            exit();
        }
        
        // Verify access
        $verify = "SELECT buyer_id, seller_id FROM marketplace_chat_rooms WHERE chat_id = ?";
        $stmt = $conn->prepare($verify);
        $stmt->bind_param("i", $chat_id);
        $stmt->execute();
        $chatData = $stmt->get_result()->fetch_assoc();
        
        if (!$chatData || ($chatData['buyer_id'] != $user_id && $chatData['seller_id'] != $user_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit();
        }
        
        // Fetch messages
        $query = "SELECT m.*, u.first_name, u.last_name 
                  FROM marketplace_messages m
                  JOIN userdata u ON m.sender_id = u.id
                  WHERE m.chat_id = ? AND m.message_id > ?
                  ORDER BY m.created_at ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $chat_id, $last_message_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = [
                'message_id' => $row['message_id'],
                'sender_id' => $row['sender_id'],
                'sender_name' => $row['first_name'] . ' ' . $row['last_name'],
                'message_text' => $row['message_text'],
                'image_path' => $row['image_path'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'is_mine' => ($row['sender_id'] == $user_id)
            ];
        }
        
        // Mark messages as read
        if (!empty($messages)) {
            $updateStatus = "UPDATE marketplace_messages SET status = 'read' 
                            WHERE chat_id = ? AND sender_id != ? AND status != 'read'";
            $stmtUpdate = $conn->prepare($updateStatus);
            $stmtUpdate->bind_param("ii", $chat_id, $user_id);
            $stmtUpdate->execute();
            
            // Reset unread count
            $resetUnread = "UPDATE marketplace_chat_status SET unread_count = 0, last_read_at = NOW() 
                           WHERE chat_id = ? AND user_id = ?";
            $stmtReset = $conn->prepare($resetUnread);
            $stmtReset->bind_param("ii", $chat_id, $user_id);
            $stmtReset->execute();
        }
        
        echo json_encode(['status' => 'success', 'messages' => $messages]);
        break;
        
    case 'get_conversations':
        $query = "SELECT 
                    c.chat_id,
                    c.buyer_id,
                    c.seller_id,
                    c.item_id,
                    c.item_type,
                    c.last_message_at,
                    cs.unread_count,
                    CASE 
                        WHEN c.buyer_id = ? THEN u_seller.first_name
                        ELSE u_buyer.first_name
                    END as other_first_name,
                    CASE 
                        WHEN c.buyer_id = ? THEN u_seller.last_name
                        ELSE u_buyer.last_name
                    END as other_last_name,
                    CASE 
                        WHEN c.buyer_id = ? THEN u_seller.profile_photo
                        ELSE u_buyer.profile_photo
                    END as other_profile_photo,
                    CASE 
                        WHEN c.buyer_id = ? THEN u_seller.department
                        ELSE u_buyer.department
                    END as other_department,
                    CASE
                        WHEN c.item_type = 'marketplace' THEN mi.productName
                        ELSE so.serviceTitle
                    END as item_name,
                    (SELECT message_text FROM marketplace_messages 
                     WHERE chat_id = c.chat_id 
                     ORDER BY created_at DESC LIMIT 1) as last_message
                  FROM marketplace_chat_rooms c
                  JOIN userdata u_buyer ON c.buyer_id = u_buyer.id
                  JOIN userdata u_seller ON c.seller_id = u_seller.id
                  LEFT JOIN marketplace_items mi ON c.item_id = mi.item_id AND c.item_type = 'marketplace'
                  LEFT JOIN service_offers so ON c.item_id = so.id AND c.item_type = 'service'
                  LEFT JOIN marketplace_chat_status cs ON c.chat_id = cs.chat_id AND cs.user_id = ?
                  WHERE (c.buyer_id = ? OR c.seller_id = ?)
                  ORDER BY c.last_message_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $conversations = [];
        while ($row = $result->fetch_assoc()) {
            $conversations[] = $row;
        }
        
        echo json_encode(['status' => 'success', 'conversations' => $conversations]);
        break;
        
    case 'update_typing':
        $chat_id = intval($_POST['chat_id'] ?? 0);
        $is_typing = intval($_POST['is_typing'] ?? 0);
        
        if ($chat_id <= 0) {
            echo json_encode(['status' => 'error']);
            exit();
        }
        
        $query = "INSERT INTO marketplace_typing_status (chat_id, user_id, is_typing) 
                  VALUES (?, ?, ?)
                  ON DUPLICATE KEY UPDATE is_typing = ?, updated_at = NOW()";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiii", $chat_id, $user_id, $is_typing, $is_typing);
        $stmt->execute();
        
        echo json_encode(['status' => 'success']);
        break;
        
    case 'get_typing_status':
        $chat_id = intval($_GET['chat_id'] ?? 0);
        
        if ($chat_id <= 0) {
            echo json_encode(['status' => 'error']);
            exit();
        }
        
        $query = "SELECT u.first_name, t.is_typing 
                  FROM marketplace_typing_status t
                  JOIN userdata u ON t.user_id = u.id
                  WHERE t.chat_id = ? AND t.user_id != ? 
                  AND t.is_typing = 1 
                  AND TIMESTAMPDIFF(SECOND, t.updated_at, NOW()) < 5";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $chat_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            echo json_encode(['status' => 'success', 'is_typing' => true, 'name' => $row['first_name']]);
        } else {
            echo json_encode(['status' => 'success', 'is_typing' => false]);
        }
        break;
        
    case 'delete_message':
        $message_id = intval($_POST['message_id'] ?? 0);
        
        if ($message_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid message ID']);
            exit();
        }
        
        // Verify user owns this message
        $verify = "SELECT m.message_id, m.sender_id, m.chat_id 
                   FROM marketplace_messages m
                   JOIN marketplace_chat_rooms c ON m.chat_id = c.chat_id
                   WHERE m.message_id = ? 
                   AND (c.buyer_id = ? OR c.seller_id = ?)";
        $stmt = $conn->prepare($verify);
        $stmt->bind_param("iii", $message_id, $user_id, $user_id);
        $stmt->execute();
        $messageData = $stmt->get_result()->fetch_assoc();
        
        if (!$messageData) {
            echo json_encode(['status' => 'error', 'message' => 'Message not found']);
            exit();
        }
        
        // Only allow sender to delete their own message
        if ($messageData['sender_id'] != $user_id) {
            echo json_encode(['status' => 'error', 'message' => 'You can only delete your own messages']);
            exit();
        }
        
        // Soft delete: update message text and mark as deleted
        $delete = "UPDATE marketplace_messages 
                   SET message_text = '[Message deleted]', 
                       image_path = NULL,
                       status = 'deleted' 
                   WHERE message_id = ?";
        $stmt = $conn->prepare($delete);
        $stmt->bind_param("i", $message_id);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Message deleted']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete message']);
        }
        break;
        
    case 'delete_conversation':
        $chat_id = intval($_POST['chat_id'] ?? 0);
        
        if ($chat_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid chat ID']);
            exit();
        }
        
        // Verify user is part of this chat
        $verify = "SELECT buyer_id, seller_id FROM marketplace_chat_rooms WHERE chat_id = ?";
        $stmt = $conn->prepare($verify);
        $stmt->bind_param("i", $chat_id);
        $stmt->execute();
        $chatData = $stmt->get_result()->fetch_assoc();
        
        if (!$chatData || ($chatData['buyer_id'] != $user_id && $chatData['seller_id'] != $user_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit();
        }
        
        // Start transaction for permanent deletion
        $conn->begin_transaction();
        
        try {
            // Delete all messages in the conversation
            $deleteMessages = "DELETE FROM marketplace_messages WHERE chat_id = ?";
            $stmt = $conn->prepare($deleteMessages);
            $stmt->bind_param("i", $chat_id);
            $stmt->execute();
            
            // Delete chat status entries
            $deleteStatus = "DELETE FROM marketplace_chat_status WHERE chat_id = ?";
            $stmt = $conn->prepare($deleteStatus);
            $stmt->bind_param("i", $chat_id);
            $stmt->execute();
            
            // Delete typing status entries (if table exists)
            $deleteTyping = "DELETE FROM marketplace_typing_status WHERE chat_id = ?";
            $stmt = $conn->prepare($deleteTyping);
            $stmt->bind_param("i", $chat_id);
            $stmt->execute();
            
            // Delete the chat room itself
            $deleteRoom = "DELETE FROM marketplace_chat_rooms WHERE chat_id = ?";
            $stmt = $conn->prepare($deleteRoom);
            $stmt->bind_param("i", $chat_id);
            $stmt->execute();
            
            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Conversation permanently deleted']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete conversation: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>
