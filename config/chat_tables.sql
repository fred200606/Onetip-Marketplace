-- Chat Rooms Table
CREATE TABLE IF NOT EXISTS marketplace_chat_rooms (
    chat_id INT PRIMARY KEY AUTO_INCREMENT,
    buyer_id INT NOT NULL,
    seller_id INT NOT NULL,
    item_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_message_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    archived_by_buyer TINYINT(1) DEFAULT 0,
    archived_by_seller TINYINT(1) DEFAULT 0,
    FOREIGN KEY (buyer_id) REFERENCES userdata(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES userdata(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES marketplace_items(item_id) ON DELETE CASCADE,
    UNIQUE KEY unique_chat (buyer_id, seller_id, item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages Table
CREATE TABLE IF NOT EXISTS marketplace_messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    chat_id INT NOT NULL,
    sender_id INT NOT NULL,
    message_text TEXT NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    status ENUM('sent', 'delivered', 'read') DEFAULT 'sent',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chat_id) REFERENCES marketplace_chat_rooms(chat_id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES userdata(id) ON DELETE CASCADE,
    INDEX idx_chat_created (chat_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat Status Table (tracks unread messages)
CREATE TABLE IF NOT EXISTS marketplace_chat_status (
    status_id INT PRIMARY KEY AUTO_INCREMENT,
    chat_id INT NOT NULL,
    user_id INT NOT NULL,
    unread_count INT DEFAULT 0,
    last_read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chat_id) REFERENCES marketplace_chat_rooms(chat_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES userdata(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_chat (chat_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Online Status Table
CREATE TABLE IF NOT EXISTS user_online_status (
    user_id INT PRIMARY KEY,
    is_online TINYINT(1) DEFAULT 0,
    last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES userdata(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
