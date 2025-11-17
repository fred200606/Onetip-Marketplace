-- Chat Rooms Table
CREATE TABLE IF NOT EXISTS marketplace_chat_rooms (
    chat_id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    seller_id INT NOT NULL,
    item_id INT NOT NULL,
    item_type ENUM('marketplace', 'service') DEFAULT 'marketplace',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_message_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_archived TINYINT(1) DEFAULT 0,
    FOREIGN KEY (buyer_id) REFERENCES userdata(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES userdata(id) ON DELETE CASCADE,
    UNIQUE KEY unique_chat (buyer_id, seller_id, item_id, item_type),
    INDEX idx_buyer (buyer_id),
    INDEX idx_seller (seller_id),
    INDEX idx_item (item_id, item_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Messages Table
CREATE TABLE IF NOT EXISTS marketplace_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    chat_id INT NOT NULL,
    sender_id INT NOT NULL,
    message_text TEXT,
    image_path VARCHAR(255),
    status ENUM('sent', 'delivered', 'read') DEFAULT 'sent',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chat_id) REFERENCES marketplace_chat_rooms(chat_id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES userdata(id) ON DELETE CASCADE,
    INDEX idx_chat (chat_id),
    INDEX idx_sender (sender_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chat Status Table (tracks unread counts)
CREATE TABLE IF NOT EXISTS marketplace_chat_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chat_id INT NOT NULL,
    user_id INT NOT NULL,
    unread_count INT DEFAULT 0,
    last_read_at DATETIME,
    FOREIGN KEY (chat_id) REFERENCES marketplace_chat_rooms(chat_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES userdata(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_chat (chat_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Typing Indicator Table (optional, for real-time typing status)
CREATE TABLE IF NOT EXISTS marketplace_typing_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chat_id INT NOT NULL,
    user_id INT NOT NULL,
    is_typing TINYINT(1) DEFAULT 0,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (chat_id) REFERENCES marketplace_chat_rooms(chat_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES userdata(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_typing (chat_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
