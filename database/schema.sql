-- 建表結構
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `mydb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mydb`;

DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `user_addresses`;
DROP TABLE IF EXISTS `user_favorites`;
DROP TABLE IF EXISTS `user_item`;
DROP TABLE IF EXISTS `points_log`;
DROP TABLE IF EXISTS `membership_rules`;
DROP TABLE IF EXISTS `vip_levels`;
DROP TABLE IF EXISTS `user_wallet_transactions`;
DROP TABLE IF EXISTS `user_wallets`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `items`;

CREATE TABLE `items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` int(11) NOT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `image_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `password_reset_hash` varchar(255) DEFAULT NULL,
  `password_reset_expires_at` datetime DEFAULT NULL,
  `status` enum('active','disabled') NOT NULL DEFAULT 'active',
  `total_spent` decimal(10,2) NOT NULL DEFAULT 0.00,
  `last_level_up_time` datetime DEFAULT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `total_points_earned` int(11) NOT NULL DEFAULT 0,
  `total_points_spent` int(11) NOT NULL DEFAULT 0,
  `membership_level` varchar(20) NOT NULL DEFAULT 'bronze',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unq_users_email` (`email`),
  KEY `idx_users_membership_level` (`membership_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `status` enum('Added to cart','Confirmed','Completed','Cancelled') NOT NULL,
  `review_title` varchar(255) DEFAULT NULL,
  `review_content` text DEFAULT NULL,
  `review_rating` tinyint(4) DEFAULT NULL,
  `review_date` datetime DEFAULT NULL,
  `is_reviewed` tinyint(4) DEFAULT 0,
  `date_time` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_item_user` (`user_id`),
  KEY `idx_user_item_item` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_favorites` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`, `item_id`),
  KEY `idx_user_favorites_user` (`user_id`),
  KEY `idx_user_favorites_item` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `address_label` varchar(100) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `recipient_name` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `address_type` varchar(50) NOT NULL DEFAULT '住宅',
  `region` varchar(50) NOT NULL,
  `district` varchar(100) NOT NULL,
  `village_estate` varchar(255) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `building` varchar(255) NOT NULL,
  `floor` varchar(50) DEFAULT NULL,
  `unit` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_addresses_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_provider` varchar(50) DEFAULT NULL,
  `payment_reference` varchar(255) DEFAULT NULL COMMENT 'Stripe PI / PayPal id / wallet:訂單編號',
  `shipping_address` text NOT NULL,
  `status` enum('pending','paid','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unq_orders_order_number` (`order_number`),
  UNIQUE KEY `uq_orders_user_provider_payment_ref` (`user_id`,`payment_provider`,`payment_reference`),
  KEY `idx_orders_user` (`user_id`),
  KEY `idx_orders_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_item` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_wallets` (
  `user_id` int(11) NOT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_wallet_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('refund','payment','adjustment') NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_wallet_tx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `membership_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level_key` varchar(20) NOT NULL,
  `level_name` varchar(50) NOT NULL,
  `min_spent` decimal(10,2) NOT NULL DEFAULT 0.00,
  `points_multiplier` decimal(4,2) NOT NULL DEFAULT 1.00,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unq_membership_rules_level_key` (`level_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `points_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `change_type` enum('earn','spend','adjust') NOT NULL,
  `points_change` int(11) NOT NULL,
  `amount_hkd` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_points_log_user` (`user_id`),
  KEY `idx_points_log_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `admins` (`username`, `password`) VALUES
('admin', '$2y$10$YourHashedPasswordHere');

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;
