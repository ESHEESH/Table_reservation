-- Add VIP Customer System
-- Migration for Priority Queue Implementation

-- Create VIP customers table
CREATE TABLE IF NOT EXISTS `vip_customers` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `phone` VARCHAR(20) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `vip_level` ENUM('bronze','silver','gold','platinum') DEFAULT 'bronze',
    `total_bookings` INT(11) DEFAULT 0,
    `total_spent` DECIMAL(10,2) DEFAULT 0.00,
    `joined_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_booking_date` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `phone` (`phone`),
    KEY `vip_level` (`vip_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add VIP flag to reservations
ALTER TABLE `reservations` 
ADD COLUMN `is_vip` TINYINT(1) DEFAULT 0 AFTER `status`,
ADD COLUMN `priority_score` INT(11) DEFAULT 0 AFTER `is_vip`,
ADD COLUMN `booking_timestamp` BIGINT DEFAULT 0 AFTER `priority_score`;

-- Add index for priority sorting
ALTER TABLE `reservations` 
ADD KEY `priority_idx` (`priority_score`, `booking_timestamp`);

-- Sample VIP customers (for testing)
INSERT INTO `vip_customers` (`phone`, `name`, `vip_level`, `total_bookings`, `total_spent`) VALUES
('+639123456789', 'John Doe', 'platinum', 25, 15000.00),
('+639987654321', 'Jane Smith', 'gold', 15, 8500.00),
('+639111222333', 'Bob Johnson', 'silver', 8, 4200.00);
