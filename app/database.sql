-- Sakura Sushi Reservation System - Database Schema
-- Data Structures & Algorithms Project
-- 
-- To import this file in XAMPP:
-- 1. Open phpMyAdmin (http://localhost/phpmyadmin)
-- 2. Create a new database called 'sakura_sushi'
-- 3. Go to the Import tab
-- 4. Select this file and click Go

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Create database
CREATE DATABASE IF NOT EXISTS `sakura_sushi` 
    DEFAULT CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE `sakura_sushi`;

-- Tables for restaurant seating
DROP TABLE IF EXISTS `pre_orders`;
DROP TABLE IF EXISTS `reservations`;
DROP TABLE IF EXISTS `menu_items`;
DROP TABLE IF EXISTS `tables`;

CREATE TABLE `tables` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `table_number` VARCHAR(10) NOT NULL,
    `capacity` INT(11) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `status` ENUM('available','occupied','reserved') DEFAULT 'available',
    `position_x` INT(11) DEFAULT 0,
    `position_y` INT(11) DEFAULT 0,
    `features` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `table_number` (`table_number`),
    KEY `status` (`status`),
    KEY `capacity` (`capacity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reservations table
CREATE TABLE `reservations` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `people_count` INT(11) NOT NULL,
    `table_id` INT(11) UNSIGNED DEFAULT NULL,
    `confirmation_code` VARCHAR(20) NOT NULL,
    `payment_receipt` VARCHAR(255) DEFAULT NULL,
    `reservation_date` DATE NOT NULL,
    `reservation_time` TIME NOT NULL,
    `special_requests` TEXT,
    `status` ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
    `has_pre_order` TINYINT(1) DEFAULT 0,
    `total_amount` DECIMAL(10,2) DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `confirmation_code` (`confirmation_code`),
    KEY `table_id` (`table_id`),
    KEY `reservation_date` (`reservation_date`),
    KEY `status` (`status`),
    CONSTRAINT `fk_reservation_table` 
        FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`) 
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Menu items table
CREATE TABLE `menu_items` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `price` DECIMAL(10,2) NOT NULL,
    `category` ENUM('sushi','sashimi','rolls','appetizers','drinks') NOT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pre-orders table
CREATE TABLE `pre_orders` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `reservation_id` INT(11) UNSIGNED NOT NULL,
    `menu_item_id` INT(11) UNSIGNED NOT NULL,
    `quantity` INT(11) NOT NULL DEFAULT 1,
    `subtotal` DECIMAL(10,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `reservation_id` (`reservation_id`),
    KEY `menu_item_id` (`menu_item_id`),
    CONSTRAINT `fk_preorder_reservation` 
        FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) 
        ON DELETE CASCADE,
    CONSTRAINT `fk_preorder_menu` 
        FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed data for tables
INSERT INTO `tables` (`table_number`, `capacity`, `price`, `status`, `position_x`, `position_y`, `features`) VALUES
('T01', 2, 15.00, 'available', 0, 0, 'Window view, Natural lighting'),
('T02', 2, 15.00, 'available', 1, 0, 'Window view, Natural lighting'),
('T03', 4, 25.00, 'available', 2, 0, 'Private booth, Quiet corner'),
('T04', 4, 25.00, 'available', 0, 1, 'Center stage, Sushi bar view'),
('T05', 4, 25.00, 'available', 1, 1, 'Standard seating'),
('T06', 6, 35.00, 'available', 2, 1, 'Family size, Spacious'),
('T07', 6, 35.00, 'available', 0, 2, 'Corner private, Quiet'),
('T08', 6, 35.00, 'available', 1, 2, 'Sushi bar view'),
('T09', 8, 50.00, 'available', 2, 2, 'VIP section, Premium service'),
('T10', 8, 50.00, 'available', 0, 3, 'VIP section, Premium service'),
('T11', 2, 12.00, 'available', 1, 3, 'Counter seating, Watch chef'),
('T12', 2, 12.00, 'available', 2, 3, 'Counter seating, Watch chef');

-- Seed data for menu items
INSERT INTO `menu_items` (`name`, `description`, `price`, `category`, `image`) VALUES
('Salmon Nigiri', 'Fresh Norwegian salmon over seasoned sushi rice', 8.50, 'sushi', 'salmon-nigiri.jpg'),
('Tuna Nigiri', 'Premium bluefin tuna over seasoned sushi rice', 10.00, 'sushi', 'tuna-nigiri.jpg'),
('Ebi Nigiri', 'Sweet shrimp over seasoned sushi rice', 9.00, 'sushi', 'ebi-nigiri.jpg'),
('Tamago Nigiri', 'Japanese sweet egg omelette over rice', 6.00, 'sushi', 'tamago-nigiri.jpg'),
('Tuna Sashimi', 'Thinly sliced premium bluefin tuna, 6 pieces', 14.00, 'sashimi', 'tuna-sashimi.jpg'),
('Salmon Sashimi', 'Thinly sliced fresh Norwegian salmon, 6 pieces', 12.00, 'sashimi', 'salmon-sashimi.jpg'),
('Yellowtail Sashimi', 'Butter-soft yellowtail slices, 6 pieces', 15.00, 'sashimi', 'yellowtail-sashimi.jpg'),
('Mixed Sashimi', 'Assorted chef selection of fresh fish, 12 pieces', 22.00, 'sashimi', 'mixed-sashimi.jpg'),
('Dragon Roll', 'Shrimp tempura inside, eel and avocado on top', 16.00, 'rolls', 'dragon-roll.jpg'),
('California Roll', 'Crab, avocado, and cucumber roll', 10.00, 'rolls', 'california-roll.jpg'),
('Spicy Tuna Roll', 'Spicy tuna with cucumber and sesame seeds', 11.00, 'rolls', 'spicy-tuna-roll.jpg'),
('Rainbow Roll', 'California roll topped with assorted fresh fish', 15.00, 'rolls', 'rainbow-roll.jpg'),
('Edamame', 'Steamed soybeans with sea salt', 5.00, 'appetizers', 'edamame.jpg'),
('Miso Soup', 'Traditional soybean paste soup with tofu and seaweed', 4.00, 'appetizers', 'miso-soup.jpg'),
('Gyoza', 'Pan-fried pork and vegetable dumplings, 6 pieces', 7.00, 'appetizers', 'gyoza.jpg'),
('Tempura', 'Assorted shrimp and vegetable tempura', 9.00, 'appetizers', 'tempura.jpg'),
('Japanese Sake', 'Warm premium junmai sake, 180ml', 8.00, 'drinks', 'sake.jpg'),
('Green Tea', 'Traditional Japanese green tea', 3.00, 'drinks', 'green-tea.jpg'),
('Ramune', 'Japanese marble soda, original flavor', 4.00, 'drinks', 'ramune.jpg'),
('Matcha Latte', 'Creamy matcha green tea latte', 5.50, 'drinks', 'matcha-latte.jpg');

SET FOREIGN_KEY_CHECKS = 1;
