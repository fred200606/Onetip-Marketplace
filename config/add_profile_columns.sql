-- Add missing columns to userdata table
ALTER TABLE `userdata` 
ADD COLUMN `bio` TEXT NULL AFTER `department`,
ADD COLUMN `campus` VARCHAR(50) DEFAULT 'Arlegui' AFTER `bio`,
ADD COLUMN `profile_photo` VARCHAR(255) NULL AFTER `campus`;
