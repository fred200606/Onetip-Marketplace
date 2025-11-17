<?php
session_name('USER_SESSION');
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../loginreg/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$chat_id = intval($_GET['chat_id'] ?? 0);

// Fetch user data
$queryUser = "SELECT * FROM userdata WHERE id = ?";
$stmt = $conn->prepare($queryUser);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$username = htmlspecialchars($user['username']);
$tip_email = htmlspecialchars($user['tip_email']);
$profile_photo = !empty($user['profile_photo']) && file_exists($user['profile_photo']) 
    ? htmlspecialchars($user['profile_photo']) 
    : '../assets/Images/profile-icon.png';

// Fetch chat details if chat_id is provided
$chatDetails = null;
if ($chat_id > 0) {
    $query = "SELECT 
                c.*,
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
                CASE
                    WHEN c.item_type = 'marketplace' THEN mi.productImg
                    ELSE so.serviceImages
                END as item_image
              FROM marketplace_chat_rooms c
              JOIN userdata u_buyer ON c.buyer_id = u_buyer.id
              JOIN userdata u_seller ON c.seller_id = u_seller.id
              LEFT JOIN marketplace_items mi ON c.item_id = mi.item_id AND c.item_type = 'marketplace'
              LEFT JOIN service_offers so ON c.item_id = so.id AND c.item_type = 'service'
              WHERE c.chat_id = ? AND (c.buyer_id = ? OR c.seller_id = ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiiiiii", $user_id, $user_id, $user_id, $user_id, $chat_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $chatDetails = $result->fetch_assoc();
    } else {
        // Invalid chat or no access
        header("Location: dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ONE-TiP - Messages</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/dashboard.css">
    <style>
        .chat-container {
            display: grid;
            grid-template-columns: 300px 1fr 280px;
            height: calc(100vh - 140px);
            background: #f8f9fa;
            gap: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* Left Sidebar - Conversations */
        .conversations-sidebar {
            background: white;
            border-right: 1px solid #e0e0e0;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            max-height: 100%;
        }
        
        .conversations-header {
            padding: 16px;
            border-bottom: 1px solid #e0e0e0;
            background: #fff;
            position: sticky;
            top: 0;
            z-index: 10;
            flex-shrink: 0;
        }
        
        .conversations-header h3 {
            margin: 0 0 10px 0;
            font-size: 1.2rem;
        }
        
        #conversationsList {
            flex: 1;
            overflow-y: auto;
        }
        
        .conversation-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            display: flex;
            gap: 12px;
            align-items: center;
            transition: background 0.2s;
            position: relative;
        }
        
        .conversation-item:hover {
            background: #f5f5f5;
        }
        
        .conversation-item:hover .conversation-delete-btn {
            display: block;
        }
        
        .conversation-delete-btn {
            display: none;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 0.75rem;
            cursor: pointer;
            z-index: 10;
        }
        
        .conversation-delete-btn:hover {
            background: #c82333;
        }
        
        .conversations-header {
            padding: 16px;
            border-bottom: 1px solid #e0e0e0;
            background: #fff;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .conversations-header h3 {
            margin: 0 0 10px 0;
            font-size: 1.2rem;
        }
        
        .conversation-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            display: flex;
            gap: 12px;
            align-items: center;
            transition: background 0.2s;
        }
        
        .conversation-item:hover,
        .conversation-item.active {
            background: #f5f5f5;
        }
        
        .conversation-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }
        
        .conversation-info {
            flex: 1;
            min-width: 0;
        }
        
        .conversation-name {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .conversation-preview {
            font-size: 0.85rem;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-time {
            font-size: 0.75rem;
            color: #999;
        }
        
        .unread-badge {
            background: #007bff;
            color: white;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        /* Middle Panel - Chat Window */
        .chat-window {
            display: flex;
            flex-direction: column;
            background: #fff;
            height: 100%;
            overflow: hidden;
        }
        
        .chat-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e0e0e0;
            background: white;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }
        
        .chat-header-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .chat-header-info h4 {
            margin: 0;
            font-size: 1rem;
        }
        
        .chat-header-info p {
            margin: 2px 0 0 0;
            font-size: 0.85rem;
            color: #666;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
            min-height: 0;
        }
        
        .message-bubble {
            display: flex;
            margin-bottom: 16px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message-bubble.mine {
            justify-content: flex-end;
        }
        
        .message-content {
            max-width: 60%;
            padding: 10px 14px;
            border-radius: 16px;
            word-wrap: break-word;
        }
        
        .message-bubble.mine .message-content {
            background: #007bff;
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .message-bubble.other .message-content {
            background: white;
            color: #333;
            border-bottom-left-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .message-image {
            max-width: 100%;
            border-radius: 8px;
            margin-top: 8px;
            cursor: pointer;
        }
        
        .message-time {
            font-size: 0.7rem;
            margin-top: 4px;
            opacity: 0.7;
        }
        
        .message-status {
            font-size: 0.7rem;
            margin-left: 4px;
        }
        
        .typing-indicator {
            padding: 10px 20px;
            font-size: 0.85rem;
            color: #666;
            font-style: italic;
            flex-shrink: 0;
        }
        
        /* Message Input - Fixed at bottom */
        .chat-input-area {
            padding: 16px 20px;
            background: white;
            border-top: 1px solid #e0e0e0;
            flex-shrink: 0;
        }
        
        .chat-input-wrapper {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .chat-input {
            flex: 1;
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-size: 0.95rem;
            outline: none;
        }
        
        .chat-input:focus {
            border-color: #007bff;
        }
        
        .chat-btn {
            padding: 10px 16px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .chat-btn-send {
            background: #007bff;
            color: white;
        }
        
        .chat-btn-send:hover {
            background: #0056b3;
        }
        
        .chat-btn-attach {
            background: #f0f0f0;
            color: #333;
        }
        
        .chat-btn-attach:hover {
            background: #e0e0e0;
        }
        
        /* Right Panel - Fixed height with scroll */
        .chat-info-panel {
            background: white;
            border-left: 1px solid #e0e0e0;
            padding: 20px;
            overflow-y: auto;
            max-height: 100%;
        }
        
        .item-preview {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .item-preview img {
            width: 100%;
            max-height: 160px;
            object-fit: contain;
            border-radius: 8px;
            background: #f5f5f5;
            padding: 10px;
        }
        
        .item-preview h4 {
            margin: 10px 0 0 0;
            font-size: 0.95rem;
        }
        
        .user-info-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .user-info-section h5 {
            margin: 0 0 12px 0;
            font-size: 0.9rem;
            color: #666;
        }
        
        .user-info-item {
            margin-bottom: 10px;
            font-size: 0.85rem;
        }
        
        .empty-chat {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            color: #999;
        }
        
        /* Item Details Card - Fixed at top */
        .chat-item-card {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
            margin: 10px 20px;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-shrink: 0;
        }
        
        .chat-item-image {
            width: 60px;
            height: 60px;
            border-radius: 6px;
            object-fit: cover;
            flex-shrink: 0;
        }
        
        .chat-item-details {
            flex: 1;
            min-width: 0;
        }
        
        .chat-item-title {
            font-weight: 600;
            font-size: 0.9rem;
            margin: 0 0 4px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .chat-item-type {
            font-size: 0.75rem;
            color: #666;
            text-transform: capitalize;
        }
        
        /* Chat header actions */
        .chat-header-actions {
            margin-left: auto;
        }
        
        .chat-header-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: background 0.2s;
        }
        
        .chat-header-btn:hover {
            background: #c82333;
        }
        
        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .chat-container {
                grid-template-columns: 260px 1fr;
            }
            .chat-info-panel {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .chat-container {
                grid-template-columns: 1fr;
                height: calc(100vh - 120px);
            }
            .conversations-sidebar {
                display: none;
            }
            
            .chat-header {
                padding: 12px 16px;
            }
            
            .chat-messages {
                padding: 16px;
            }
            
            .chat-input-area {
                padding: 12px 16px;
            }
        }
        
        /* Scrollbar styling */
        .conversations-sidebar::-webkit-scrollbar,
        .chat-messages::-webkit-scrollbar,
        .chat-info-panel::-webkit-scrollbar {
            width: 6px;
        }
        
        .conversations-sidebar::-webkit-scrollbar-track,
        .chat-messages::-webkit-scrollbar-track,
        .chat-info-panel::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        .conversations-sidebar::-webkit-scrollbar-thumb,
        .chat-messages::-webkit-scrollbar-thumb,
        .chat-info-panel::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }
        
        .conversations-sidebar::-webkit-scrollbar-thumb:hover,
        .chat-messages::-webkit-scrollbar-thumb:hover,
        .chat-info-panel::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body>
    <!-- Navigation Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="logo-section">
                <img src="../assets/Images/LOGO-LONG.png" alt="ONE-TiP" class="header-logo">
            </div>
            <div class="search-section">
                <input type="text" id="globalSearch" placeholder="Search conversations..." class="search-input">
                <button type="button" class="search-btn" id="searchBtn">
                    <img src="../assets/Images/grey-search-bar.svg" alt="Search" class="search-icon">
                </button>
            </div>
            <div class="user-section">
                <div class="user-profile" id="userProfile">
                    <img src="<?= $profile_photo ?>" alt="User" class="profile-img">
                    <span class="username">@<?= $username ?></span>
                </div>
            </div>
        </div>
        <nav class="nav-tabs">
            <a href="dashboard.php" class="nav-tab">Dashboard</a>
            <a href="marketplace.php" class="nav-tab">Marketplace</a>
            <a href="services.php" class="nav-tab">Services</a>
            <a href="chat.php" class="nav-tab active">Messages</a>
        </nav>
    </header>

    <div style="max-width: 1400px; margin: 20px auto; padding: 0 20px;">
        <div class="chat-container">
            <!-- Left Sidebar: Conversations -->
            <div class="conversations-sidebar">
                <div class="conversations-header">
                    <h3>Messages</h3>
                </div>
                <div id="conversationsList">
                    <div style="padding: 40px 20px; text-align: center; color: #999;">
                        Loading conversations...
                    </div>
                </div>
            </div>

            <!-- Middle: Chat Window -->
            <div class="chat-window">
                <?php if ($chatDetails): ?>
                    <!-- Item Details Card -->
                    <div class="chat-item-card">
                        <?php 
                        $itemImg = $chatDetails['item_image'] ?? '';
                        if ($chatDetails['item_type'] === 'service' && !empty($itemImg)) {
                            $itemImg = explode(',', $itemImg)[0];
                        }
                        // Fix: Remove ../ prefix if already present
                        $itemImgSrc = !empty($itemImg) ? $itemImg : '../assets/Images/placeholder.png';
                        if (!str_starts_with($itemImgSrc, '../') && !str_starts_with($itemImgSrc, 'http')) {
                            $itemImgSrc = '../' . trim($itemImgSrc);
                        }
                        ?>
                        <img src="<?= $itemImgSrc ?>" alt="Item" class="chat-item-image">
                        <div class="chat-item-details">
                            <div class="chat-item-title"><?= htmlspecialchars($chatDetails['item_name'] ?? 'Item') ?></div>
                            <div class="chat-item-type"><?= ucfirst($chatDetails['item_type'] ?? '') ?> Listing</div>
                        </div>
                    </div>
                    
                    <div class="chat-header">
                        <?php
                        $otherProfilePhoto = $chatDetails['other_profile_photo'] ?? '';
                        // Fix: Handle profile photo path correctly
                        if (!empty($otherProfilePhoto)) {
                            if (!str_starts_with($otherProfilePhoto, '../') && !str_starts_with($otherProfilePhoto, 'http')) {
                                $otherPhotoSrc = '../' . trim($otherProfilePhoto);
                            } else {
                                $otherPhotoSrc = $otherProfilePhoto;
                            }
                        } else {
                            $otherPhotoSrc = '../assets/Images/profile-icon.png';
                        }
                        ?>
                        <img src="<?= $otherPhotoSrc ?>" alt="User" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        <div>
                            <h4 style="margin: 0;"><?= htmlspecialchars($chatDetails['other_first_name'] . ' ' . $chatDetails['other_last_name']) ?></h4>
                            <p style="margin: 2px 0 0 0; font-size: 0.85rem; color: #666;"><?= htmlspecialchars($chatDetails['other_department'] ?? 'Student') ?></p>
                        </div>
                        <div class="chat-header-actions">
                            <button class="chat-header-btn delete" id="deleteConversationBtn" title="Delete Conversation">
                                🗑️ Delete Chat
                            </button>
                        </div>
                    </div>
                    
                    <div class="chat-messages" id="chatMessages">
                        <!-- Messages will be loaded here -->
                    </div>
                    
                    <div class="typing-indicator" id="typingIndicator" style="display: none;">
                        <span id="typingText"></span>
                    </div>
                    
                    <div class="chat-input-area">
                        <div class="chat-input-wrapper">
                            <input type="file" id="imageUpload" accept="image/jpeg,image/png,image/jpg" style="display: none;">
                            <button style="padding: 10px 16px; border: none; border-radius: 20px; cursor: pointer; background: #f0f0f0;" id="attachBtn">📎</button>
                            <input type="text" id="messageInput" class="chat-input" placeholder="Type a message..." maxlength="1000">
                            <button style="padding: 10px 16px; border: none; border-radius: 20px; cursor: pointer; background: #007bff; color: white;" id="sendBtn">Send</button>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; text-align: center; color: #999;">
                        <div>
                            <h3>💬 No conversation selected</h3>
                            <p>Select a conversation from the sidebar</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Panel -->
            <?php if ($chatDetails): ?>
            <div class="chat-info-panel">
                <div style="text-align: center; margin-bottom: 20px;">
                    <img src="<?= $itemImgSrc ?>" alt="Item" style="width: 100%; max-height: 160px; object-fit: contain; border-radius: 8px;">
                    <h4 style="margin: 10px 0 0 0;"><?= htmlspecialchars($chatDetails['item_name'] ?? 'Item') ?></h4>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const currentUserId = <?= $user_id ?>;
        const currentChatId = <?= $chat_id ?>;
        let lastMessageId = 0;
        let pollInterval = null;

        <?php if ($chat_id > 0): ?>
        // Delete conversation
        document.getElementById('deleteConversationBtn')?.addEventListener('click', async () => {
            if (!confirm('Are you sure you want to delete this conversation? This action cannot be undone.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_conversation');
            formData.append('chat_id', currentChatId);
            
            try {
                const response = await fetch('chat_api.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const result = await response.json();
                
                if (result.status === 'success') {
                    alert('Conversation deleted');
                    window.location.href = 'chat.php';
                } else {
                    alert(result.message || 'Failed to delete conversation');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        });

        // Load messages
        async function loadMessages() {
            try {
                const response = await fetch(`chat_api.php?action=fetch_messages&chat_id=${currentChatId}&last_message_id=${lastMessageId}`);
                const result = await response.json();
                
                if (result.status === 'success' && result.messages.length > 0) {
                    result.messages.forEach(msg => {
                        appendMessage(msg);
                        lastMessageId = Math.max(lastMessageId, msg.message_id);
                    });
                    scrollToBottom();
                }
            } catch (error) {
                console.error('Error loading messages:', error);
            }
        }

        function appendMessage(msg) {
            const container = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message-bubble ${msg.is_mine ? 'mine' : 'other'}`;
            messageDiv.dataset.messageId = msg.message_id;
            
            let imageHtml = '';
            if (msg.image_path) {
                imageHtml = `<img src="../${msg.image_path}" style="max-width: 100%; border-radius: 8px; margin-top: 8px;">`;
            }
            
            const isDeleted = msg.status === 'deleted';
            const messageContent = isDeleted ? '<em style="opacity: 0.6;">[Message deleted]</em>' : (msg.message_text || '');
            
            const deleteBtn = msg.is_mine && !isDeleted ? `
                <div class="message-actions">
                    <button class="message-action-btn" onclick="deleteMessage(${msg.message_id})" title="Delete">🗑️</button>
                </div>
            ` : '';
            
            messageDiv.innerHTML = `
                ${deleteBtn}
                <div class="message-content">
                    <div>${messageContent}</div>
                    ${imageHtml}
                    <div style="font-size: 0.7rem; margin-top: 4px; opacity: 0.7;">
                        ${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                    </div>
                </div>
            `;
            
            container.appendChild(messageDiv);
        }

        // Delete message function
        async function deleteMessage(messageId) {
            if (!confirm('Delete this message?')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_message');
            formData.append('message_id', messageId);
            
            try {
                const response = await fetch('chat_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.status === 'success') {
                    const messageEl = document.querySelector(`[data-message-id="${messageId}"]`);
                    if (messageEl) {
                        const content = messageEl.querySelector('.message-content');
                        content.innerHTML = '<div><em style="opacity: 0.6;">[Message deleted]</em></div>';
                        const actions = messageEl.querySelector('.message-actions');
                        if (actions) actions.remove();
                    }
                } else {
                    alert(result.message || 'Failed to delete message');
                }
            } catch (error) {
                alert('Error deleting message');
            }
        }

        function scrollToBottom() {
            const container = document.getElementById('chatMessages');
            container.scrollTop = container.scrollHeight;
        }

        // Send message
        async function sendMessage() {
            const input = document.getElementById('messageInput');
            const messageText = input.value.trim();
            
            if (!messageText) return;
            
            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('chat_id', currentChatId);
            formData.append('message_text', messageText);
            
            try {
                const response = await fetch('chat_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.status === 'success') {
                    input.value = '';
                } else {
                    alert(result.message || 'Failed to send message');
                }
            } catch (error) {
                alert('Error sending message');
            }
        }

        document.getElementById('messageInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                sendMessage();
            }
        });

        document.getElementById('sendBtn').addEventListener('click', sendMessage);

        loadMessages();
        pollInterval = setInterval(loadMessages, 2000);
        <?php endif; ?>

        // Load conversations
        async function loadConversations() {
            try {
                const response = await fetch('chat_api.php?action=get_conversations');
                const result = await response.json();
                
                if (result.status === 'success') {
                    const container = document.getElementById('conversationsList');
                    
                    if (result.conversations.length === 0) {
                        container.innerHTML = '<div style="padding: 40px 20px; text-align: center; color: #999;">No conversations yet</div>';
                        return;
                    }
                    
                    container.innerHTML = result.conversations.map(conv => {
                        // Fix: Handle profile photo path correctly
                        let photoSrc = '../assets/Images/profile-icon.png';
                        if (conv.other_profile_photo) {
                            if (!conv.other_profile_photo.startsWith('../') && !conv.other_profile_photo.startsWith('http')) {
                                photoSrc = '../' + conv.other_profile_photo;
                            } else {
                                photoSrc = conv.other_profile_photo;
                            }
                        }
                        
                        return `
                            <div class="conversation-item" data-chat-id="${conv.chat_id}">
                                <img src="${photoSrc}" alt="User" class="conversation-avatar" onclick="window.location.href='chat.php?chat_id=${conv.chat_id}'">
                                <div style="flex: 1; min-width: 0;" onclick="window.location.href='chat.php?chat_id=${conv.chat_id}'">
                                    <div style="font-weight: 600; margin-bottom: 4px;">${conv.other_first_name} ${conv.other_last_name}</div>
                                    <div style="font-size: 0.85rem; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${conv.last_message || 'No messages yet'}</div>
                                </div>
                                <button class="conversation-delete-btn" onclick="deleteConversationFromList(event, ${conv.chat_id})">Delete</button>
                            </div>
                        `;
                    }).join('');
                }
            } catch (error) {
                console.error('Error loading conversations:', error);
            }
        }

        // Delete conversation from list
        async function deleteConversationFromList(event, chatId) {
            event.stopPropagation();
            
            if (!confirm('Delete this conversation?')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_conversation');
            formData.append('chat_id', chatId);
            
            try {
                const response = await fetch('chat_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.status === 'success') {
                    // Remove from UI
                    const convItem = document.querySelector(`[data-chat-id="${chatId}"]`);
                    if (convItem) {
                        convItem.remove();
                    }
                    
                    // If we're viewing this conversation, redirect
                    if (chatId === currentChatId) {
                        window.location.href = 'chat.php';
                    }
                } else {
                    alert(result.message || 'Failed to delete conversation');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        loadConversations();
        setInterval(loadConversations, 10000);
    </script>
</body>
</html>
