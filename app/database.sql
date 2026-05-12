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
    `location` VARCHAR(50) DEFAULT 'Main Hall',
    `table_type` ENUM('standard','booth','counter','vip') DEFAULT 'standard',
    `is_smoking` TINYINT(1) DEFAULT 0,
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
    `stock` INT(11) DEFAULT 100,
    `available` TINYINT(1) DEFAULT 1,
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
INSERT INTO `tables` (`table_number`, `capacity`, `price`, `status`, `position_x`, `position_y`, `features`, `location`, `table_type`, `is_smoking`) VALUES
('T01', 2, 150.00, 'available', 0, 0, 'Window view, Natural lighting', 'Window Side', 'standard', 0),
('T02', 2, 150.00, 'available', 1, 0, 'Window view, Natural lighting', 'Window Side', 'standard', 0),
('T03', 4, 250.00, 'available', 2, 0, 'Private booth, Quiet corner', 'Private Area', 'booth', 0),
('T04', 4, 250.00, 'available', 0, 1, 'Center stage, Sushi bar view', 'Main Hall', 'standard', 0),
('T05', 6, 350.00, 'available', 1, 1, 'Sushi bar view', 'Main Hall', 'counter', 0),
('T06', 4, 250.00, 'available', 2, 1, 'Family size, Spacious', 'Main Hall', 'standard', 0),
('T07', 8, 500.00, 'available', 0, 2, 'Corner private, Quiet', 'Private Area', 'booth', 0),
('T08', 6, 350.00, 'available', 1, 2, 'Sushi bar view', 'Main Hall', 'counter', 0),
('T09', 2, 150.00, 'available', 2, 2, 'VIP section, Premium service', 'VIP Section', 'vip', 0);

-- Seed data for menu items
INSERT INTO `menu_items` (`name`, `description`, `price`, `category`, `image`, `stock`, `available`) VALUES
('Salmon Nigiri', 'Fresh Norwegian salmon over seasoned sushi rice', 220.00, 'sushi', 'salmon-nigiri.jpg', 50, 1),
('Tuna Nigiri', 'Premium bluefin tuna over seasoned sushi rice', 280.00, 'sushi', 'tuna-nigiri.jpg', 30, 1),
('Ebi Nigiri', 'Sweet shrimp over seasoned sushi rice', 250.00, 'sushi', 'ebi-nigiri.jpg', 40, 1),
('Tamago Nigiri', 'Japanese sweet egg omelette over rice', 180.00, 'sushi', 'tamago-nigiri.jpg', 60, 1),
('Tuna Sashimi', 'Thinly sliced premium bluefin tuna, 6 pieces', 450.00, 'sashimi', 'tuna-sashimi.jpg', 25, 1),
('Salmon Sashimi', 'Thinly sliced fresh Norwegian salmon, 6 pieces', 380.00, 'sashimi', 'salmon-sashimi.jpg', 35, 1),
('Yellowtail Sashimi', 'Butter-soft yellowtail slices, 6 pieces', 480.00, 'sashimi', 'yellowtail-sashimi.jpg', 20, 1),
('Mixed Sashimi', 'Assorted chef selection of fresh fish, 12 pieces', 650.00, 'sashimi', 'mixed-sashimi.jpg', 15, 1),
('Dragon Roll', 'Shrimp tempura inside, eel and avocado on top', 520.00, 'rolls', 'dragon-roll.jpg', 30, 1),
('California Roll', 'Crab, avocado, and cucumber roll', 320.00, 'rolls', 'california-roll.jpg', 50, 1),
('Spicy Tuna Roll', 'Spicy tuna with cucumber and sesame seeds', 350.00, 'rolls', 'spicy-tuna-roll.jpg', 40, 1),
('Rainbow Roll', 'California roll topped with assorted fresh fish', 480.00, 'rolls', 'rainbow-roll.jpg', 25, 1),
('Edamame', 'Steamed soybeans with sea salt', 150.00, 'appetizers', 'edamame.jpg', 100, 1),
('Miso Soup', 'Traditional soybean paste soup with tofu and seaweed', 120.00, 'appetizers', 'miso-soup.jpg', 80, 1),
('Gyoza', 'Pan-fried pork and vegetable dumplings, 6 pieces', 220.00, 'appetizers', 'gyoza.jpg', 60, 1),
('Tempura', 'Assorted shrimp and vegetable tempura', 280.00, 'appetizers', 'tempura.jpg', 45, 1),
('Japanese Sake', 'Warm premium junmai sake, 180ml', 250.00, 'drinks', 'sake.jpg', 40, 1),
('Green Tea', 'Traditional Japanese green tea', 80.00, 'drinks', 'green-tea.jpg', 100, 1),
('Ramune', 'Japanese marble soda, original flavor', 120.00, 'drinks', 'ramune.jpg', 80, 1),
('Matcha Latte', 'Creamy matcha green tea latte', 180.00, 'drinks', 'matcha-latte.jpg', 50, 1);

SET FOREIGN_KEY_CHECKS = 1;
