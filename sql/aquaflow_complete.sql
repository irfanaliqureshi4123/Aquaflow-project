-- ================================================================
-- AQUAFLOW COMPLETE DATABASE SCHEMA
-- Consolidated single file for complete database setup
-- Version: 1.1 (January 2026) - Updated with all missing tables, columns, views and procedures
-- Database: aquaflow_db
-- ================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ================================================================
-- SECTION 0: PROCEDURES
-- ================================================================

DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS `cleanup_old_payment_logs` (IN `days_to_keep` INT)
BEGIN
  DELETE FROM `payment_logs` 
  WHERE `created_at` < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
END$$

DELIMITER ;

-- ================================================================
-- SECTION 1: CORE TABLES
-- ================================================================

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `action` text DEFAULT NULL,
  `ip_address` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('admin','staff','customer') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sales` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','delivered','cancelled') DEFAULT 'pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `customer_email` varchar(255) DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT 'Cash on Delivery',
  `invoice_no` varchar(255) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `payment_status` enum('pending','processing','paid','failed','cancelled','refunded') DEFAULT 'pending',
  `payment_method_used` varchar(50) NULL,
  `paid_at` datetime NULL,
  `notes` text NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `method_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `requires_payment_method_id` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` varchar(50) NOT NULL,
  `invoice_no` varchar(255) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `status` enum('Paid','Pending','Failed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) NULL,
  `stripe_payment_id` varchar(255) NULL,
  `stripe_intent_id` varchar(255) NULL,
  `amount_pkr` decimal(10,2) NULL,
  `amount_usd` decimal(10,2) NULL,
  `exchange_rate` decimal(10,4) NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `gateway` enum('stripe', 'paypal', 'cod') DEFAULT 'stripe',
  `last_4_digits` varchar(4) NULL,
  `error_message` text NULL,
  `attempted_at` datetime NULL,
  `completed_at` datetime NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `payment_logs`
--

CREATE TABLE `payment_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NULL,
  `order_id` int(11) NULL,
  `transaction_id` varchar(255) NULL,
  `amount` decimal(10,2) NULL,
  `method` varchar(50) NULL,
  `status` varchar(50) NULL,
  `event_type` varchar(100) NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `ip_address` varchar(45) NULL,
  `user_agent` varchar(255) NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL,
  INDEX `idx_payment_logs_user_id` (`user_id`),
  INDEX `idx_payment_logs_order_id` (`order_id`),
  INDEX `idx_payment_logs_transaction_id` (`transaction_id`),
  INDEX `idx_payment_logs_created_at` (`created_at`),
  INDEX `idx_payment_logs_status` (`status`),
  INDEX `idx_payment_logs_event_type` (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `webhook_events`
--

CREATE TABLE `webhook_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` varchar(255) UNIQUE NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `processed` tinyint(1) DEFAULT 0,
  `processed_at` datetime NULL,
  `error_message` text NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_webhook_events_event_id` (`event_id`),
  INDEX `idx_webhook_events_event_type` (`event_type`),
  INDEX `idx_webhook_events_processed` (`processed`),
  INDEX `idx_webhook_events_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `id` int(11) NOT NULL,
  `order_id` varchar(50) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `delivery_date` date NOT NULL,
  `status` enum('Pending','On the Way','Delivered','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `recipient_email` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `type` enum('order','support','newsletter','system') DEFAULT 'system',
  `status` enum('sent','failed','pending') DEFAULT 'pending',
  `sent_at` datetime DEFAULT current_timestamp(),
  `cc` varchar(255) DEFAULT NULL,
  `bcc` varchar(255) DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `partnerships`
--

CREATE TABLE `partnerships` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `assigned_area` varchar(150) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `support_messages`
--

CREATE TABLE `support_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `reply` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `replied_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `wholesale_orders`
--

CREATE TABLE `wholesale_orders` (
  `id` int(11) NOT NULL,
  `business_name` varchar(150) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `status` enum('pending','approved','delivered') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `delivery_rate` decimal(10,2) DEFAULT NULL,
  `about` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ================================================================
-- SECTION 2: MEMBERSHIP SYSTEM
-- ================================================================

--
-- Table structure for table `memberships`
--

CREATE TABLE IF NOT EXISTS `memberships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `bottles_per_week` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `user_membership`
--

CREATE TABLE `user_membership` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `membership_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `user_memberships`
--

CREATE TABLE `user_memberships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `membership_id` int(11) NOT NULL,
  `start_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  `end_date` datetime,
  `status` enum('active', 'expired', 'cancelled') DEFAULT 'active',
  `payment_method` varchar(50),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`membership_id`) REFERENCES `memberships`(`id`) ON DELETE CASCADE,
  INDEX `user_idx` (`user_id`),
  INDEX `membership_idx` (`membership_id`),
  INDEX `status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `membership_history`
--

CREATE TABLE IF NOT EXISTS `membership_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `membership_id` int(11) NOT NULL,
  `action` varchar(50),
  `start_date` datetime,
  `end_date` datetime,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`membership_id`) REFERENCES `memberships`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `contact_messages`
--

CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `phone` varchar(20),
    `subject` varchar(150) NOT NULL,
    `message_type` enum('general_query', 'bulk_order', 'complaint', 'partnership') DEFAULT 'general_query',
    `message` longtext NOT NULL,
    `status` enum('new', 'read', 'replied', 'closed') DEFAULT 'new',
    `admin_notes` longtext,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `status_idx` (`status`),
    INDEX `email_idx` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `otp` varchar(6) DEFAULT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `verified` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ================================================================
-- SECTION 3: VIEWS
-- ================================================================

--
-- View structure for `payment_analytics`
--

DROP TABLE IF EXISTS `payment_analytics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `payment_analytics`  AS 
SELECT 
  CAST(`o`.`order_date` AS DATE) AS `transaction_date`, 
  COUNT(DISTINCT `o`.`id`) AS `total_orders`, 
  COUNT(DISTINCT `o`.`user_id`) AS `unique_customers`, 
  `p`.`payment_method` AS `payment_method`, 
  SUM(CASE WHEN `o`.`status` = 'paid' OR `p`.`status` = 'Paid' THEN `o`.`total_amount` ELSE 0 END) AS `paid_amount`, 
  SUM(CASE WHEN `o`.`status` = 'pending' OR `p`.`status` = 'Pending' THEN `o`.`total_amount` ELSE 0 END) AS `pending_amount`, 
  SUM(CASE WHEN `o`.`status` = 'failed' OR `p`.`status` = 'Failed' THEN `o`.`total_amount` ELSE 0 END) AS `failed_amount`, 
  COUNT(CASE WHEN `p`.`status` = 'Paid' THEN 1 END) AS `successful_payments`, 
  COUNT(CASE WHEN `p`.`status` = 'Failed' THEN 1 END) AS `failed_payments` 
FROM (`orders` `o` LEFT JOIN `payments` `p` ON (`o`.`id` = `p`.`order_id`)) 
GROUP BY CAST(`o`.`order_date` AS DATE), `p`.`payment_method`;

-- ================================================================
-- SECTION 4: PRIMARY KEYS
-- ================================================================

ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status_idx` (`status`),
  ADD KEY `email_idx` (`email`);

ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `memberships`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `partnerships`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `idx_payments_user_status` (`payment_method`, `status`),
  ADD KEY `idx_payments_created_at` (`created_at`),
  ADD KEY `idx_payments_stripe_intent` (`stripe_intent_id`),
  ADD KEY `idx_payments_order_id` (`order_id`);

ALTER TABLE `payment_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_logs_user_id` (`user_id`),
  ADD KEY `idx_payment_logs_order_id` (`order_id`),
  ADD KEY `idx_payment_logs_transaction_id` (`transaction_id`),
  ADD KEY `idx_payment_logs_created_at` (`created_at`),
  ADD KEY `idx_payment_logs_status` (`status`),
  ADD KEY `idx_payment_logs_event_type` (`event_type`);

ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `support_messages`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `user_membership`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `membership_id` (`membership_id`);

ALTER TABLE `user_memberships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_idx` (`user_id`),
  ADD KEY `membership_idx` (`membership_id`),
  ADD KEY `status_idx` (`status`);

ALTER TABLE `webhook_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_id` (`event_id`),
  ADD KEY `idx_webhook_events_event_id` (`event_id`),
  ADD KEY `idx_webhook_events_event_type` (`event_type`),
  ADD KEY `idx_webhook_events_processed` (`processed`),
  ADD KEY `idx_webhook_events_created_at` (`created_at`);

ALTER TABLE `wholesale_orders`
  ADD PRIMARY KEY (`id`);

-- ================================================================
-- SECTION 6: AUTO_INCREMENT VALUES
-- ================================================================

ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `deliveries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

ALTER TABLE `memberships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

ALTER TABLE `partnerships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `payment_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `support_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

ALTER TABLE `user_membership`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `user_memberships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `webhook_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `wholesale_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- ================================================================
-- SECTION 7: FOREIGN KEY CONSTRAINTS
-- ================================================================

ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

ALTER TABLE `payment_logs`
  ADD CONSTRAINT `payment_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payment_logs_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `user_membership`
  ADD CONSTRAINT `user_membership_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_membership_ibfk_2` FOREIGN KEY (`membership_id`) REFERENCES `memberships` (`id`) ON DELETE CASCADE;

ALTER TABLE `user_memberships`
  ADD CONSTRAINT `user_memberships_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_memberships_ibfk_2` FOREIGN KEY (`membership_id`) REFERENCES `memberships` (`id`) ON DELETE CASCADE;

-- ================================================================
-- SECTION 8: SAMPLE DATA
-- ================================================================

-- Sample Payment Methods
INSERT INTO `payment_methods` (`id`, `method_name`, `is_active`, `created_at`) VALUES
(1, 'Credit Card', 1, '2025-11-14 10:39:02'),
(2, 'PayPal', 1, '2025-11-14 10:39:02'),
(3, 'Cash on Delivery', 1, '2025-11-14 10:39:02')
ON DUPLICATE KEY UPDATE `is_active`=VALUES(`is_active`);

-- Sample Products
INSERT INTO `products` (`id`, `name`, `description`, `size`, `price`, `stock`, `image`, `created_at`, `sales`) VALUES
(1, 'aquafina', 'for home use and wholesale deliveries', '1 Litre', 100.00, 1000, 'bottle1.jpeg', '2025-11-14 04:15:23', 0),
(2, 'Quafina', 'for home and whole and office deliveries', '500 ML', 50.00, 1000, 'bottle500.jpeg', '2025-11-14 04:17:27', 0)
ON DUPLICATE KEY UPDATE `stock`=VALUES(`stock`);

-- Sample User
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `role`, `created_at`) VALUES
(3, 'Irfan Ali', 'irfanaliqureshi0302@gmail.com', '$2y$10$lKr003RthQqtb2xkuefpNuUjEF1ehVLnTAZdJWUTkM/8UmU1Nc2YC', '03033979926', 'near ali place Qasimabad Hyderabad', 'customer', '2025-11-13 01:48:30')
ON DUPLICATE KEY UPDATE `address`=VALUES(`address`);

-- ================================================================
-- END OF COMPLETE DATABASE SCHEMA
-- ================================================================

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
