-- Create notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `related_id` INT DEFAULT NULL,
    `related_type` VARCHAR(50) DEFAULT NULL,
    `is_read` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `userdata`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_read` (`user_id`, `is_read`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create vouches table (if not exists)
CREATE TABLE IF NOT EXISTS `vouches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `buyer_id` INT NOT NULL,
    `seller_id` INT NOT NULL,
    `item_type` VARCHAR(50) NOT NULL,
    `item_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`buyer_id`) REFERENCES `userdata`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`seller_id`) REFERENCES `userdata`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_vouch` (`buyer_id`, `seller_id`, `item_type`, `item_id`),
    INDEX `idx_seller` (`seller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create ratings table (if not exists)
CREATE TABLE IF NOT EXISTS `ratings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `item_type` VARCHAR(50) NOT NULL,
    `item_id` INT NOT NULL,
    `rating` TINYINT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `review` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `userdata`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_rating` (`user_id`, `item_type`, `item_id`),
    INDEX `idx_item` (`item_type`, `item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
