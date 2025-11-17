<?php
require 'db.php';

// Check if reports table exists
$checkTable = "SHOW TABLES LIKE 'reports'";
$result = $conn->query($checkTable);

if ($result->num_rows === 0) {
    // Create reports table
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
        `status` enum('pending','notified','reviewed','resolved','dismissed') DEFAULT 'pending',
        `resolved_by` int(11) DEFAULT NULL,
        `resolved_at` timestamp NULL DEFAULT NULL,
        `action_taken` varchar(100) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `item_id` (`item_id`),
        KEY `seller_id` (`seller_id`),
        KEY `status` (`status`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($createTable)) {
        echo "✅ Reports table created successfully!\n";
    } else {
        echo "❌ Error creating reports table: " . $conn->error . "\n";
    }
} else {
    // Table exists, check if status column has correct ENUM values
    $checkColumn = "SHOW COLUMNS FROM reports LIKE 'status'";
    $colResult = $conn->query($checkColumn);
    
    if ($colResult->num_rows > 0) {
        $colData = $colResult->fetch_assoc();
        
        // Check if 'notified' is in the ENUM
        if (strpos($colData['Type'], 'notified') === false) {
            // Alter the status column to include 'notified'
            $alterTable = "ALTER TABLE reports MODIFY COLUMN status 
                          enum('pending','notified','reviewed','resolved','dismissed') DEFAULT 'pending'";
            
            if ($conn->query($alterTable)) {
                echo "✅ Reports table status column updated successfully!\n";
            } else {
                echo "❌ Error updating status column: " . $conn->error . "\n";
            }
        } else {
            echo "✅ Reports table already has correct structure!\n";
        }
    }
}

// Update any NULL status values to 'pending'
$updateNull = "UPDATE reports SET status = 'pending' WHERE status IS NULL";
$conn->query($updateNull);

echo "✅ Setup complete!\n";
?>
