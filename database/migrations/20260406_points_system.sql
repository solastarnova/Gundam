START TRANSACTION;

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `points` int(11) NOT NULL DEFAULT 0 AFTER `status`,
  ADD COLUMN IF NOT EXISTS `total_points_earned` int(11) NOT NULL DEFAULT 0 AFTER `points`,
  ADD COLUMN IF NOT EXISTS `total_points_spent` int(11) NOT NULL DEFAULT 0 AFTER `total_points_earned`,
  ADD COLUMN IF NOT EXISTS `membership_level` varchar(20) NOT NULL DEFAULT 'bronze' AFTER `total_points_spent`;

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

INSERT INTO `membership_rules` (`level_key`, `level_name`, `min_spent`, `points_multiplier`, `discount_percent`, `sort_order`) VALUES
('bronze', '青铜', 0, 1.00, 0.00, 1),
('silver', '白银', 2000, 1.20, 3.00, 2),
('gold', '黄金', 5000, 1.50, 5.00, 3),
('platinum', '铂金', 10000, 2.00, 8.00, 4)
ON DUPLICATE KEY UPDATE
`level_name` = VALUES(`level_name`),
`min_spent` = VALUES(`min_spent`),
`points_multiplier` = VALUES(`points_multiplier`),
`discount_percent` = VALUES(`discount_percent`),
`sort_order` = VALUES(`sort_order`);

COMMIT;
