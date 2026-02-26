-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 26, 2026 at 01:13 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fashion_bloom`
--

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('read','unread') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `status`, `created_at`) VALUES
(1, 'Sample User', 'user@example.com', 'Test', 'This is a test message', 'unread', '2025-12-20 11:21:45'),
(2, 'Sample Contact', 'contact@example.com', 'general', 'Sample message content', 'unread', '2025-12-20 11:23:03');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(50) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'cod',
  `payment_status` varchar(50) DEFAULT 'pending',
  `subtotal` decimal(10,2) DEFAULT NULL,
  `tax` decimal(10,2) DEFAULT NULL,
  `shipping` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `stripe_payment_id` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `total_amount`, `status`, `created_at`, `first_name`, `last_name`, `email`, `phone`, `address`, `city`, `postal_code`, `notes`, `payment_method`, `payment_status`, `subtotal`, `tax`, `shipping`, `total`, `total_price`, `stripe_payment_id`, `updated_at`) VALUES
(4, 1, 'ORD-20251219-44601', 0.00, 'processing', '2025-12-19 16:07:21', 'Sample', 'User', 'user@example.com', '+1234567890', 'Sample Address', 'Sample City', '12345', '', 'card', 'completed', 12500.00, 2125.00, 0.00, 14625.00, 14625.00, NULL, '2025-12-20 08:52:40'),
(5, 15, 'ORD-20251220-34596', 0.00, 'cancelled', '2025-12-20 03:59:24', 'Sample', 'Customer', 'customer@example.com', '+1234567891', 'Sample Address 2', 'Sample City', '12345', '', 'card', 'completed', 4500.00, 765.00, 0.00, NULL, 5265.00, NULL, '2025-12-20 08:54:37'),
(6, 17, 'ORD-20251223-72172', 0.00, 'delivered', '2025-12-23 13:57:16', 'Sample', 'Buyer', 'buyer@example.com', '+1234567892', 'Sample Address 3', 'Sample City', '12345', '', 'card', 'pending', 12500.00, 2125.00, 0.00, NULL, 14625.00, NULL, '2025-12-23 18:02:12');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `product_price`, `quantity`, `category`, `created_at`) VALUES
(4, 4, 1, 'Premium Gold Bracelet', 12500.00, 1, 'bracelets', '2025-12-19 20:07:21'),
(5, 5, 2, 'Silver Chain Bracelet', 4500.00, 1, 'bracelets', '2025-12-20 07:59:24'),
(6, 6, 1, 'Premium Gold Bracelet', 12500.00, 1, 'bracelets', '2025-12-23 17:57:16');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `stock_quantity` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `price`, `image_url`, `description`, `created_at`, `stock_quantity`) VALUES
(1, 'Premium Gold Bracelet', 'bracelets', 12500.00, 'assets/images/products/bracelets/bracelet_1.jpg', NULL, '2025-12-19 08:04:26', 0),
(2, 'Silver Chain Bracelet', 'bracelets', 4500.00, 'assets/images/products/bracelets/bracelet_2.jpg', NULL, '2025-12-19 08:04:26', 0),
(3, 'Diamond Tennis Bracelet', 'bracelets', 28000.00, 'assets/images/products/bracelets/bracelet_3.jpg', NULL, '2025-12-19 08:04:26', 0),
(4, 'Platinum Bracelet', 'bracelets', 35000.00, 'assets/images/products/bracelets/bracelet_4.jpg', NULL, '2025-12-19 08:04:26', 0),
(5, 'Gold Bracelet', 'bracelets', 15000.00, 'assets/images/products/bracelets/bracelet_5.jpg', NULL, '2025-12-19 08:04:26', 0),
(6, 'Diamond Bracelet', 'bracelets', 45000.00, 'assets/images/products/bracelets/bracelet_6.jpg', NULL, '2025-12-19 08:04:26', 0),
(7, 'Diamond Bracelet', 'bracelets', 55000.00, 'assets/images/products/bracelets/bracelet_7.jpg', NULL, '2025-12-19 08:04:26', 0),
(8, 'Diamond Bracelet', 'bracelets', 75000.00, 'assets/images/products/bracelets/bracelet_8.jpg', NULL, '2025-12-19 08:04:26', 0),
(9, 'Smart Digital Watch', 'digital_watches', 8500.00, 'assets/images/products/digital_watches/digital_watch_1.jpg', NULL, '2025-12-19 08:04:26', 0),
(10, 'Sports Digital Watch', 'digital_watches', 4200.00, 'assets/images/products/digital_watches/digital_watch_2.jpg', NULL, '2025-12-19 08:04:26', 0),
(11, 'LED Digital Watch', 'digital_watches', 2500.00, 'assets/images/products/digital_watches/digital_watch_3.jpg', NULL, '2025-12-19 08:04:26', 0),
(12, 'Retro Digital Watch', 'digital_watches', 5500.00, 'assets/images/products/digital_watches/digital_watch_4.jpg', NULL, '2025-12-19 08:04:26', 0),
(13, 'Retro Digital Watch', 'digital_watches', 6500.00, 'assets/images/products/digital_watches/digital_watch_5.png', NULL, '2025-12-19 08:04:26', 0),
(14, 'Retro Digital Watch', 'digital_watches', 8000.00, 'assets/images/products/digital_watches/digital_watch_6.jpg', NULL, '2025-12-19 08:04:26', 0),
(15, 'Retro Digital Watch', 'digital_watches', 9500.00, 'assets/images/products/digital_watches/digital_watch_7.jpg', NULL, '2025-12-19 08:04:26', 0),
(16, 'Retro Digital Watch', 'digital_watches', 11500.00, 'assets/images/products/digital_watches/digital_watch_8.png', NULL, '2025-12-19 08:04:26', 0),
(17, 'Classic Analog Watch', 'normal_watches', 7500.00, 'assets/images/products/normal_watches/normal_watch_1.jpg', NULL, '2025-12-19 08:04:26', 0),
(18, 'Luxury Business Watch', 'normal_watches', 18000.00, 'assets/images/products/normal_watches/normal_watch_2.jpg', NULL, '2025-12-19 08:04:26', 0),
(19, 'Casual Day Watch', 'normal_watches', 5500.00, 'assets/images/products/normal_watches/normal_watch_3.png', NULL, '2025-12-19 08:04:26', 0),
(20, 'Luxury Day Watch', 'normal_watches', 9500.00, 'assets/images/products/normal_watches/normal_watch_4.jpg', NULL, '2025-12-19 08:04:26', 0),
(21, 'Luxury Day Watch', 'normal_watches', 12000.00, 'assets/images/products/normal_watches/normal_watch_5.png', NULL, '2025-12-19 08:04:26', 0),
(22, 'Luxury Day Watch', 'normal_watches', 15000.00, 'assets/images/products/normal_watches/normal_watch_6.png', NULL, '2025-12-19 08:04:26', 0),
(23, 'Luxury Day Watch', 'normal_watches', 18500.00, 'assets/images/products/normal_watches/normal_watch_7.png', NULL, '2025-12-19 08:04:26', 0),
(24, 'Luxury Day Watch', 'normal_watches', 22000.00, 'assets/images/products/normal_watches/normal_watch_8.jpg', NULL, '2025-12-19 08:04:26', 0),
(25, '18K Gold Chain', 'gold_chains', 32000.00, 'assets/images/products/gold_chains/gold_chain_1.jpg', NULL, '2025-12-19 08:04:26', 0),
(26, 'Gold Rope Chain', 'gold_chains', 26500.00, 'assets/images/products/gold_chains/gold_chain_2.jpg', NULL, '2025-12-19 08:04:26', 0),
(27, 'Cuban Link Gold Chain', 'gold_chains', 42000.00, 'assets/images/products/gold_chains/gold_chain_3.jpg', NULL, '2025-12-19 08:04:26', 0),
(28, 'Gold Chain', 'gold_chains', 18500.00, 'assets/images/products/gold_chains/gold_chain_4.jpg', NULL, '2025-12-19 08:04:26', 0),
(29, 'Gold Chain', 'gold_chains', 22000.00, 'assets/images/products/gold_chains/gold_chain_5.jpg', NULL, '2025-12-19 08:04:26', 0),
(30, 'Gold Chain', 'gold_chains', 28000.00, 'assets/images/products/gold_chains/gold_chain_6.jpg', NULL, '2025-12-19 08:04:26', 0),
(31, 'Gold Chain', 'gold_chains', 35000.00, 'assets/images/products/gold_chains/gold_chain_7.jpg', NULL, '2025-12-19 08:04:26', 0),
(32, 'Gold Chain', 'gold_chains', 45000.00, 'assets/images/products/gold_chains/gold_chain_8.jpg', NULL, '2025-12-19 08:04:26', 0),
(33, 'Sterling Silver Chain', 'silver_chains', 6500.00, 'assets/images/products/silver_chains/silver_chain_1.jpg', NULL, '2025-12-19 08:04:26', 0),
(34, 'Silver Box Chain', 'silver_chains', 4500.00, 'assets/images/products/silver_chains/silver_chain_2.jpg', NULL, '2025-12-19 08:04:26', 0),
(35, 'Silver Snake Chain', 'silver_chains', 5500.00, 'assets/images/products/silver_chains/silver_chain_3.jpg', NULL, '2025-12-19 08:04:26', 0),
(36, 'Silver Chain', 'silver_chains', 8000.00, 'assets/images/products/silver_chains/silver_chain_4.jpg', NULL, '2025-12-19 08:04:26', 0),
(37, 'Silver Chain', 'silver_chains', 10000.00, 'assets/images/products/silver_chains/silver_chain_5.jpg', NULL, '2025-12-19 08:04:26', 0),
(38, 'Silver Chain', 'silver_chains', 12500.00, 'assets/images/products/silver_chains/silver_chain_6.jpg', NULL, '2025-12-19 08:04:26', 0),
(39, 'Silver Chain', 'silver_chains', 15000.00, 'assets/images/products/silver_chains/silver_chain_7.jpg', NULL, '2025-12-19 08:04:26', 0),
(40, 'Silver Chain', 'silver_chains', 18500.00, 'assets/images/products/silver_chains/silver_chain_8.jpg', NULL, '2025-12-19 08:04:26', 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_rating_summary`
--

CREATE TABLE `product_rating_summary` (
  `product_id` int(11) NOT NULL,
  `total_reviews` int(11) DEFAULT 0,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `five_star` int(11) DEFAULT 0,
  `four_star` int(11) DEFAULT 0,
  `three_star` int(11) DEFAULT 0,
  `two_star` int(11) DEFAULT 0,
  `one_star` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_rating_summary`
--

INSERT INTO `product_rating_summary` (`product_id`, `total_reviews`, `average_rating`, `five_star`, `four_star`, `three_star`, `two_star`, `one_star`, `updated_at`) VALUES
(1, 5, 3.60, 2, 1, 1, 0, 1, '2025-12-12 05:49:39'),
(2, 2, 4.50, 1, 1, 0, 0, 0, '2025-12-12 05:43:02'),
(3, 2, 5.00, 2, 0, 0, 0, 0, '2025-12-12 05:43:02'),
(4, 1, 4.00, 0, 1, 0, 0, 0, '2025-12-12 05:43:02'),
(5, 0, 0.00, 0, 0, 0, 0, 0, '2025-12-23 04:35:55'),
(6, 0, 0.00, 0, 0, 0, 0, 0, '2025-12-22 17:17:56'),
(7, 0, 0.00, 0, 0, 0, 0, 0, '2025-12-23 10:04:47'),
(101, 3, 4.33, 1, 2, 0, 0, 0, '2025-12-12 05:38:58'),
(102, 2, 3.50, 0, 1, 1, 0, 0, '2025-12-12 05:43:02'),
(201, 2, 4.50, 1, 1, 0, 0, 0, '2025-12-12 05:38:58'),
(202, 1, 5.00, 1, 0, 0, 0, 0, '2025-12-12 05:43:02'),
(301, 1, 5.00, 1, 0, 0, 0, 0, '2025-12-12 05:38:58'),
(302, 1, 5.00, 1, 0, 0, 0, 0, '2025-12-12 05:43:02'),
(402, 1, 4.00, 0, 1, 0, 0, 0, '2025-12-12 05:43:02');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `title` varchar(255) NOT NULL,
  `review_text` text DEFAULT NULL,
  `verified_purchase` tinyint(1) DEFAULT 0,
  `helpful_count` int(11) DEFAULT 0,
  `unhelpful_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review_text`, `verified_purchase`, `helpful_count`, `unhelpful_count`, `created_at`, `updated_at`) VALUES
(4, 1, 2, 4, 'Great value for money', 'Very happy with this purchase. The product is well-made and looks beautiful. Would definitely buy again.', 1, 0, 0, '2025-12-12 05:38:58', '2025-12-12 05:38:58'),
(5, 1, 2, 5, 'Perfect gift', 'I bought this as a gift and the recipient loved it. The packaging was also very nice. 5 stars!', 1, 0, 0, '2025-12-12 05:38:58', '2025-12-12 05:38:58'),
(6, 1, 2, 3, 'Good but could be better', 'Nice bracelet but the clasp is a bit loose. Otherwise its a solid purchase.', 1, 0, 0, '2025-12-12 05:38:58', '2025-12-12 05:38:58'),
(7, 101, 2, 4, 'Good quality watch', 'Works perfectly. Battery lasts a long time. Only minor issue is the strap could be a bit more comfortable.', 1, 0, 0, '2025-12-12 05:38:58', '2025-12-12 05:38:58'),
(8, 101, 2, 5, 'Amazing design', 'Love the modern design of this watch. Very lightweight and comfortable to wear all day.', 0, 0, 0, '2025-12-12 05:38:58', '2025-12-12 05:38:58'),
(9, 101, 2, 4, 'Very reliable', 'Using this watch for 6 months now and its still working perfectly. Great investment!', 1, 0, 0, '2025-12-12 05:38:58', '2025-12-12 05:38:58'),
(10, 201, 2, 5, 'Premium quality watch', 'Absolutely stunning watch. The craftsmanship is incredible. Worth every penny!', 1, 0, 0, '2025-12-12 05:38:58', '2025-12-12 05:38:58'),
(11, 201, 2, 4, 'Elegant and classy', 'Beautiful classic design. Perfect for business meetings and casual outings.', 1, 0, 0, '2025-12-12 05:38:58', '2025-12-12 05:38:58'),
(12, 301, 2, 5, 'Authentic gold chain', 'Verified authentic 18K gold. Beautiful shine and perfect weight. Highly recommend!', 1, 0, 0, '2025-12-12 05:38:58', '2025-12-12 05:38:58'),
(13, 2, 2, 5, 'Beautiful Silver Chain Bracelet', 'Absolutely stunning! The quality is exceptional.', 1, 0, 0, '2025-12-12 05:43:02', '2025-12-12 05:43:02'),
(14, 2, 2, 4, 'Great quality', 'Very nice bracelet, arrived on time.', 1, 0, 0, '2025-12-12 05:43:02', '2025-12-19 07:04:38'),
(15, 3, 2, 5, 'Luxury at its finest', 'Diamond tennis bracelet is absolutely gorgeous!', 1, 0, 0, '2025-12-12 05:43:02', '2025-12-12 05:43:02'),
(16, 3, 2, 5, 'Perfect for special occasions', 'Looks amazing on the wrist. Highly recommend!', 1, 0, 0, '2025-12-12 05:43:02', '2025-12-12 05:43:02'),
(17, 4, 2, 4, 'Excellent platinum quality', 'Very durable and well-made. Worth the investment.', 1, 0, 0, '2025-12-12 05:43:02', '2025-12-12 05:43:02'),
(18, 102, 2, 4, 'Great sports watch', 'Perfect for tracking workouts. Battery lasts long.', 1, 0, 0, '2025-12-12 05:43:02', '2025-12-12 05:43:02'),
(19, 102, 2, 3, 'Good value', 'Nice watch for the price, though strap could be better.', 1, 0, 0, '2025-12-12 05:43:02', '2025-12-12 05:43:02'),
(20, 202, 2, 5, 'Business watch excellence', 'Perfect for professional settings. Looks premium!', 1, 0, 0, '2025-12-12 05:43:02', '2025-12-12 05:43:02'),
(21, 302, 2, 5, 'Premium gold rope chain', 'Beautiful rope texture, feels substantial and well-made.', 1, 0, 0, '2025-12-12 05:43:02', '2025-12-12 05:43:02'),
(22, 402, 2, 4, 'Nice silver chain', 'Good quality silver chain at a reasonable price.', 1, 0, 0, '2025-12-12 05:43:02', '2025-12-12 05:43:02'),
(24, 1, 1, 1, 'excellent', 'good product', 0, 0, 0, '2025-12-12 05:49:39', '2025-12-12 05:49:39');

-- --------------------------------------------------------

--
-- Table structure for table `resubmissions`
--

CREATE TABLE `resubmissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `details` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_helpful_votes`
--

CREATE TABLE `review_helpful_votes` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vote_type` enum('helpful','unhelpful') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_votes`
--

CREATE TABLE `review_votes` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vote_type` enum('helpful','unhelpful') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shopping_cart`
--

CREATE TABLE `shopping_cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shopping_cart`
--

INSERT INTO `shopping_cart` (`id`, `user_id`, `product_id`, `category`, `product_name`, `product_price`, `product_image`, `quantity`, `created_at`, `updated_at`) VALUES
(19, 1, 17, 'normal_watches', 'Classic Analog Watch', 7500.00, '/assets/images/products/normal_watches/normal_watch_1.jpg', 1, '2025-12-22 16:30:14', '2025-12-22 16:30:32'),
(20, 1, 1, 'bracelets', 'Premium Gold Bracelet', 12500.00, '/assets/images/products/bracelets/bracelet_1.jpg', 1, '2025-12-22 17:02:22', '2025-12-22 17:02:22'),
(24, 16, 2, 'bracelets', 'Silver Chain Bracelet', 4500.00, '/assets/images/products/bracelets/bracelet_2.jpg', 1, '2025-12-23 13:34:49', '2025-12-23 13:34:49'),
(28, 18, 7, 'bracelets', 'Diamond Bracelet', 55000.00, '/assets/images/products/bracelets/bracelet_7.jpg', 1, '2026-01-15 18:43:32', '2026-01-15 18:43:32'),
(29, 18, 2, 'bracelets', 'Silver Chain Bracelet', 4500.00, '/assets/images/products/bracelets/bracelet_2.jpg', 1, '2026-01-30 16:28:05', '2026-01-30 16:28:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(64) DEFAULT NULL,
  `token_expires` datetime DEFAULT NULL,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `email_verification_expires` timestamp NULL DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `email_verified`, `verification_token`, `token_expires`, `email_verification_token`, `email_verification_expires`, `phone`, `password`, `role`, `created_at`, `profile_photo`) VALUES
(1, 'Admin', 'User', 'admin@example.com', 1, NULL, NULL, '5c801bc5deb5c763e0685ba46cb9ab13fc0b45ca031be87d446d0d404a38adf2', '2025-12-14 03:46:04', '+1234567890', '$2y$10$xilaK8uVzuhTTCitrue29.TiT7f9NRv90BlMJonUBGc7TzpRSsLFq', 'admin', '2025-12-07 03:51:38', NULL),
(2, 'Sample', 'User1', 'user1@example.com', 1, NULL, NULL, NULL, NULL, '+1234567890', '$2y$10$gTOV8.9jdh/0X6Iaqb0IVuM3UXAU/R7pPkCi8IFE9l9/Bsa7KdGyy', 'user', '2025-12-07 13:18:05', NULL),
(15, 'Sample', 'Admin2', 'admin2@example.com', 1, NULL, NULL, NULL, NULL, '+1234567891', '$2y$10$UkcZetgyzuKZz.M9MgXBMOzZlDhr3vLOJCU9pWEKmqSBV96RzV4v2', 'admin', '2025-12-19 05:05:31', NULL),
(17, 'Sample', 'User2', 'user2@example.com', 1, NULL, NULL, NULL, NULL, '+1234567892', '$2y$10$EOJPnNE6rXr2jRlz4X5R/ua5WcOYSBdH0yy2NJ/etW9XwS5W/Ul5y', 'user', '2025-12-23 17:38:27', NULL),
(18, 'Sample', 'User3', 'user3@example.com', 1, NULL, NULL, NULL, NULL, '+1234567893', '$2y$10$oHjTTecCY8OTqdimVfi0KOSKEIookiEVI7zFr0cWkF/JABEIykhke', 'user', '2026-01-15 18:26:01', NULL),
(19, 'Sample', 'User4', 'user4@example.com', 0, '083861', '2026-01-31 10:26:57', NULL, NULL, '+1234567894', '$2y$10$m.LG2JGAgRY9MUp8y4ZmQeE1nFN2z1vc7gV3/ZhFwrHb3bt4GOf5G', 'user', '2026-01-31 09:11:57', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `session_id` varchar(255) DEFAULT NULL,
  `is_guest` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `category`, `product_name`, `product_price`, `product_image`, `created_at`, `session_id`, `is_guest`) VALUES
(10, NULL, 1, 'bracelets', 'Premium Gold Bracelet', 12500.00, '/assets/images/products/bracelets/bracelet_1.jpg', '2025-12-07 03:24:47', 'sample_session_1', 1),
(11, NULL, 2, 'bracelets', 'Silver Chain Bracelet', 4500.00, '/assets/images/products/bracelets/bracelet_2.jpg', '2025-12-07 03:24:50', 'sample_session_1', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `product_rating_summary`
--
ALTER TABLE `product_rating_summary`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `resubmissions`
--
ALTER TABLE `resubmissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `review_helpful_votes`
--
ALTER TABLE `review_helpful_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vote` (`review_id`,`user_id`),
  ADD KEY `idx_review_id` (`review_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `review_votes`
--
ALTER TABLE `review_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vote` (`review_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart_item` (`user_id`,`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `verification_token` (`verification_token`),
  ADD KEY `idx_email_verification_token` (`email_verification_token`),
  ADD KEY `idx_email_verified` (`email_verified`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_wishlist_item` (`user_id`,`product_id`),
  ADD UNIQUE KEY `unique_guest_wishlist_item` (`session_id`,`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `resubmissions`
--
ALTER TABLE `resubmissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review_helpful_votes`
--
ALTER TABLE `review_helpful_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `review_votes`
--
ALTER TABLE `review_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_rating_summary`
--
ALTER TABLE `product_rating_summary`
  ADD CONSTRAINT `product_rating_summary_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `resubmissions`
--
ALTER TABLE `resubmissions`
  ADD CONSTRAINT `resubmissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `review_helpful_votes`
--
ALTER TABLE `review_helpful_votes`
  ADD CONSTRAINT `review_helpful_votes_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `product_reviews` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_votes`
--
ALTER TABLE `review_votes`
  ADD CONSTRAINT `review_votes_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `product_reviews` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_votes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
