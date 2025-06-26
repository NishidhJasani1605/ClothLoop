-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 19, 2025 at 08:37 AM
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
-- Database: `clothloop`
--

-- --------------------------------------------------------

--
-- Table structure for table `buyers`
--

CREATE TABLE `buyers` (
  `id` int(11) NOT NULL,
  `address` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buyers`
--

INSERT INTO `buyers` (`id`, `address`, `latitude`, `longitude`) VALUES
(2, NULL, 21.18935400, 72.86125200),
(3, NULL, 23.05228800, 72.58112000),
(6, NULL, 23.05228800, 72.58112000),
(7, NULL, 23.05228800, 72.58112000),
(8, NULL, 23.05228800, 72.58112000),
(9, NULL, 23.05228800, 72.58112000);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(17, 'Men', 'Men\'s clothing', '2025-04-18 13:47:37'),
(18, 'Women', 'Women\'s clothing', '2025-04-18 13:47:37'),
(19, 'Kids', 'Kids\' clothing', '2025-04-18 13:47:37');

-- --------------------------------------------------------

--
-- Table structure for table `customer_interests`
--

CREATE TABLE `customer_interests` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_interests`
--

INSERT INTO `customer_interests` (`id`, `buyer_id`, `product_id`, `created_at`) VALUES
(4, 2, 4, '2025-04-18 23:23:46'),
(7, 6, 5, '2025-04-19 04:54:05'),
(8, 6, 15, '2025-04-19 04:54:15'),
(9, 6, 10, '2025-04-19 04:54:36'),
(10, 7, 13, '2025-04-19 04:59:23'),
(11, 7, 9, '2025-04-19 04:59:34'),
(12, 7, 11, '2025-04-19 04:59:46'),
(13, 8, 12, '2025-04-19 05:01:51'),
(14, 8, 14, '2025-04-19 05:01:56'),
(15, 8, 10, '2025-04-19 05:02:06'),
(16, 9, 16, '2025-04-19 05:05:09'),
(17, 9, 10, '2025-04-19 05:05:19'),
(18, 9, 11, '2025-04-19 05:05:25');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `size` varchar(20) DEFAULT NULL,
  `occasion` varchar(50) DEFAULT NULL,
  `rental_price` decimal(10,2) NOT NULL,
  `status` enum('available','rented','unavailable') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `terms` text DEFAULT NULL,
  `views` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `seller_id`, `title`, `description`, `category_id`, `size`, `occasion`, `rental_price`, `status`, `created_at`, `updated_at`, `terms`, `views`) VALUES
(4, 1, 'Saaree', 'Blue and Red', 18, 'M', 'Ethnic', 2299.00, 'unavailable', '2025-04-18 20:18:15', '2025-04-19 04:23:30', 'Do not Wash', 0),
(5, 1, 'Kurta', 'blue color ', 17, 'XL', 'Casual', 459.00, 'available', '2025-04-19 00:18:45', '2025-04-19 05:56:32', 'Do not Wash', 0),
(7, 4, 'Shervani', 'Red and White color', 17, 'L', 'Wedding', 2000.00, 'available', '2025-04-19 01:11:02', '2025-04-19 01:11:02', 'Do not Wash', 0),
(9, 1, 'Chaniya Choli', 'Red color patola pettern', 18, 'M', 'Wedding', 2500.00, 'available', '2025-04-19 04:21:25', '2025-04-19 04:21:25', 'Do not wash', 0),
(10, 1, 'Sari', 'White Color', 18, 'M', 'Wedding', 1500.00, 'available', '2025-04-19 04:23:20', '2025-04-19 04:23:20', 'Do not Wash', 0),
(11, 1, 'Gown', 'Pink color', 18, 'M', 'Wedding', 5000.00, 'available', '2025-04-19 04:26:30', '2025-04-19 05:55:16', 'Do not wash', 0),
(12, 4, 'Suit', 'Blue color', 17, 'M', 'Formal', 1000.00, 'available', '2025-04-19 04:29:19', '2025-04-19 06:01:58', 'Do not Wash', 0),
(13, 4, 'Wedding Shervani', 'Traditional', 17, 'M', 'Wedding', 5500.00, 'available', '2025-04-19 04:30:57', '2025-04-19 04:30:57', 'Do not wash', 0),
(14, 4, 'Suit', 'Marun color', 17, 'M', 'Formal', 1200.00, 'available', '2025-04-19 04:31:52', '2025-04-19 06:01:24', 'Do not wash', 0),
(15, 4, 'Kurti', 'Yellow and pink color', 18, 'M', 'Traditional', 1300.00, 'available', '2025-04-19 04:34:54', '2025-04-19 05:59:08', 'Do not wash', 0),
(16, 4, 'Kurto', 'Blue color', 19, 'M', 'Wedding', 500.00, 'available', '2025-04-19 04:36:37', '2025-04-19 05:57:55', 'Do not wash', 0),
(17, 4, 'Koti and Kurto', 'Jodhpuri', 19, 'L', 'Traditional', 1400.00, 'available', '2025-04-19 04:37:42', '2025-04-19 05:57:25', 'DO not Wash', 0),
(18, 10, 'Chaniya choli', 'Pink color', 18, 'M', 'Wedding', 3000.00, 'available', '2025-04-19 05:51:58', '2025-04-19 05:51:58', 'Do not wash', 0),
(19, 10, 'Kurto', '# pice jodhpuri', 17, 'M', 'Traditional', 3500.00, 'available', '2025-04-19 05:53:09', '2025-04-19 05:53:09', 'Do not wash', 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `created_at`) VALUES
(6, 4, 'uploads/products/4/1745007495_p5.jpg', 1, '2025-04-18 20:18:15'),
(9, 7, 'uploads/products/7/1745025062_p4.jpg', 1, '2025-04-19 01:11:02'),
(14, 9, 'uploads/products/9/1745036485_1745036485.6245_taa777.webp', 1, '2025-04-19 04:21:25'),
(15, 10, 'uploads/products/10/1745036600_1745036600.4935_1592216718444562-2.jpg', 1, '2025-04-19 04:23:20'),
(18, 13, 'uploads/products/13/1745037057_1745037057.0016_AC-08th-nov-23_6261-copy.jpg', 1, '2025-04-19 04:30:57'),
(23, 18, 'uploads/products/18/1745041918_1745041918.5235_9b4144384393894e85e61ee112a3d3c3.jpg', 1, '2025-04-19 05:51:58'),
(24, 19, 'uploads/products/19/1745041989_1745041989.8333_3449d323a31bbe1033919c50fe248e0f.jpg', 1, '2025-04-19 05:53:09'),
(25, 11, 'uploads/products/11/1745042116_1745042116.0418_a075e15249b8123e3d5179c2ba286d9f.jpg', 1, '2025-04-19 05:55:16'),
(26, 5, 'uploads/products/5/1745042192_1745042192.4932_cbd657dd14cb50984cab3f43af64a4b8.jpg', 1, '2025-04-19 05:56:32'),
(27, 17, 'uploads/products/17/1745042245_1745042245.1741_7b548c91bb11694e0ffcee59252e1106 (1).jpg', 1, '2025-04-19 05:57:25'),
(28, 16, 'uploads/products/16/1745042275_1745042275.3956_de863cda17d2d2453fb4e5dfa685de64.jpg', 1, '2025-04-19 05:57:55'),
(29, 15, 'uploads/products/15/1745042348_1745042348.3371_1387102ff2744fc097ffb44dee8aa22a.jpg', 1, '2025-04-19 05:59:08'),
(30, 14, 'uploads/products/14/1745042484_1745042484.6965_00c90ba57ea0481ab08fa3b6c2fbe1f1.jpg', 1, '2025-04-19 06:01:24'),
(31, 12, 'uploads/products/12/1745042518_1745042518.7891_3449d1c1888868b3629e0431077d1202.jpg', 1, '2025-04-19 06:01:58');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `buyer_id` int(11) DEFAULT NULL,
  `rating` decimal(3,1) NOT NULL,
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`id`, `product_id`, `buyer_id`, `rating`, `review`, `created_at`) VALUES
(5, 4, 2, 3.0, 'Colors are not Punchy', '2025-04-18 17:48:46'),
(7, 5, 2, 5.0, 'Excellent', '2025-04-18 21:11:34'),
(10, 17, 2, 4.0, 'Good', '2025-04-19 01:15:21'),
(11, 16, 2, 3.0, 'very good', '2025-04-19 01:16:11'),
(12, 15, 2, 4.0, 'Nice', '2025-04-19 01:16:38'),
(13, 14, 2, 5.0, 'Nice color', '2025-04-19 01:16:59'),
(14, 13, 2, 5.0, 'Best Treditional cloths', '2025-04-19 01:17:25'),
(15, 12, 2, 4.0, 'Good color', '2025-04-19 01:17:44'),
(16, 11, 2, 5.0, 'Nice color', '2025-04-19 01:18:10');

-- --------------------------------------------------------

--
-- Table structure for table `sellers`
--

CREATE TABLE `sellers` (
  `id` int(11) NOT NULL,
  `shop_name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `avg_rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sellers`
--

INSERT INTO `sellers` (`id`, `shop_name`, `description`, `address`, `latitude`, `longitude`, `avg_rating`, `total_reviews`) VALUES
(1, 'Seller Hub', 'hi', 'Vesu', 40.71280000, -74.00600000, 4.50, 5),
(4, 'Lorem Ipsum', 'Rental shop', 'Katargam', 21.14472170, 72.77177350, 3.20, 5),
(10, 'Ali shop', NULL, 'Udhna', NULL, NULL, 4.80, 3);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_no` varchar(20) DEFAULT NULL,
  `role` enum('admin','seller','buyer') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_photo` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone_no`, `role`, `created_at`, `updated_at`, `profile_photo`, `status`) VALUES
(1, 'Nishidh', 'seller@gmail.com', '$2y$10$V1hasP2.9d6O9DsyPpzI0ehiwc5N/7EfBfKboVsTfWWl066umz49y', '9825561050', 'seller', '2025-04-18 11:52:54', '2025-04-19 05:09:19', 'profile_68032fff84f07.png', 'active'),
(2, 'Aaryan Joshi', 'buyer@gmail.com', '$2y$10$RMHJ7KmR06n.TaYDIaZ7aeu.O/Zs6d/AMSDG1Kf1OwszgVVZY3L7e', '1234567890', 'buyer', '2025-04-18 14:41:57', '2025-04-19 04:48:49', 'profile_68032b315e5fc.jpg', 'active'),
(3, 'Buyer 2', 'buyer2@gmail.com', '$2y$10$WKkg0WWEHzmZwdrVgB2mo.xd5z/gQ0uX5G6719tDJuwVubSXSuF9e', '7418529630', 'buyer', '2025-04-19 01:06:37', '2025-04-19 06:13:50', 'profile_68033f1e89279.jpg', 'active'),
(4, 'Lorem Ipsum', 'seller2@gmail.com', '$2y$10$Xor2KnAD34j/6LUhFKvewOQXLoZdMyswUfkt76fHiYsyKfX/6GkZe', '7896541230', 'seller', '2025-04-19 01:09:00', '2025-04-19 05:40:04', 'profile_68033734d339b.png', 'active'),
(6, 'Yash Jariwala', 'yash@gmail.com', '$2y$10$3BOca96xxgCMnIJzadgVmOoAffXj7BopISTAQFCRtpqMlLRx4BdV.', '9313906844', 'buyer', '2025-04-19 04:53:08', '2025-04-19 04:53:50', 'profile_68032c5e1271a.jpg', 'active'),
(7, 'Nishidh Jasani', 'nishidh@gmail.com', '$2y$10$siapeFGQB/WrfRBgAAQmBObMLVLjNlAgavFDg5P0xE6KWceVmmGGe', '9825561050', 'buyer', '2025-04-19 04:55:16', '2025-04-19 04:55:51', 'profile_68032cd7cd673.jpg', 'active'),
(8, 'Aaditya Desai', 'aadi@gmail.com', '$2y$10$WNXT4ThtrduQqNfoBrYfTuiI0a40PLHy2m4RzmGIEHPAXqgkgqb5u', '8160224860', 'admin', '2025-04-19 05:01:15', '2025-04-19 05:01:38', 'profile_68032e32c7a2e.jpg', 'active'),
(9, 'Aaryan Joshi', 'aaryan@gmail.com', '$2y$10$7X5U86oY5UrHdqVS9ldShOcbFbRWz5Hg6sH7gUJS59zA8IvhV1j/K', '9512874414', 'buyer', '2025-04-19 05:03:13', '2025-04-19 05:04:56', 'profile_68032ec407fc6.jpg', 'active'),
(10, 'Ali', 'Ali@gmail.com', '$2y$10$8OiaTMKUqA78OwKtNpj79.pgSlkIQi1lmlnCPNuephDwfvx9lYG8y', '8521479630', 'seller', '2025-04-19 05:45:59', '2025-04-19 05:45:59', NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `buyer_id`, `product_id`, `created_at`) VALUES
(2, 2, 4, '2025-04-19 01:59:44'),
(3, 3, 7, '2025-04-19 02:00:57'),
(4, 2, 5, '2025-04-19 02:01:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buyers`
--
ALTER TABLE `buyers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `customer_interests`
--
ALTER TABLE `customer_interests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `status` (`status`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `buyer_id` (`buyer_id`);

--
-- Indexes for table `sellers`
--
ALTER TABLE `sellers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `email_2` (`email`),
  ADD KEY `role` (`role`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `buyer_id` (`buyer_id`,`product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `buyer_id_2` (`buyer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `customer_interests`
--
ALTER TABLE `customer_interests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `buyers`
--
ALTER TABLE `buyers`
  ADD CONSTRAINT `buyers_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_interests`
--
ALTER TABLE `customer_interests`
  ADD CONSTRAINT `customer_interests_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_interests_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sellers`
--
ALTER TABLE `sellers`
  ADD CONSTRAINT `sellers_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
