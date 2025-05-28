-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 14, 2025 at 08:26 AM
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
-- Database: `realestate`
--

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `user_type` varchar(20) NOT NULL,
  `status` varchar(20) DEFAULT 'unread',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`id`, `buyer_id`, `property_id`, `created_at`) VALUES
(165, 6, 1, '2025-05-07 18:02:37'),
(166, 6, 2, '2025-05-07 18:02:41'),
(176, 6, 3, '2025-05-07 18:50:08'),
(177, 6, 28, '2025-05-07 18:56:36');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','approved','rejected','responded','scheduled','completed') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `property_type_id` int(11) NOT NULL,
  `bedrooms` int(11) NOT NULL,
  `bathrooms` int(11) NOT NULL,
  `area` decimal(10,2) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `zip_code` varchar(20) NOT NULL,
  `year_built` int(11) DEFAULT NULL,
  `garage` tinyint(1) DEFAULT 0,
  `air_conditioning` tinyint(1) DEFAULT 0,
  `swimming_pool` tinyint(1) DEFAULT 0,
  `backyard` tinyint(1) DEFAULT 0,
  `gym` tinyint(1) DEFAULT 0,
  `fireplace` tinyint(1) DEFAULT 0,
  `security_system` tinyint(1) DEFAULT 0,
  `washer_dryer` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive','pending','sold') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `seller_id`, `title`, `description`, `price`, `property_type_id`, `bedrooms`, `bathrooms`, `area`, `address`, `city`, `state`, `zip_code`, `year_built`, `garage`, `air_conditioning`, `swimming_pool`, `backyard`, `gym`, `fireplace`, `security_system`, `washer_dryer`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'Modern Apartment with City View', 'Stunning modern apartment with panoramic city views. Features include hardwood floors, stainless steel appliances, and a spacious balcony.', 450000.00, 2, 2, 2, 1200.00, '164-14 90th Street, Howard Beach', 'USA', 'Queens, NY', '11414', 2015, 1, 1, 0, 0, 1, 0, 1, 1, 'active', '2025-04-29 12:41:58', '2025-05-03 23:57:41'),
(2, 2, 'Spacious Family Home with Garden', 'Beautiful family home with a large garden, perfect for entertaining. Features 4 bedrooms, renovated kitchen, and a two-car garage.', 750000.00, 1, 4, 3, 2500.00, '456 Oak Avenue', 'Los Angeles', 'CA', '90001', 2005, 2, 1, 1, 1, 0, 1, 1, 1, 'active', '2025-04-29 12:41:58', '2025-04-29 12:41:58'),
(3, 3, 'Luxury Condo in Downtown', 'Luxury condo in the heart of downtown. Walking distance to restaurants, shops, and entertainment. Features high-end finishes and amenities.', 550000.00, 3, 2, 2, 1500.00, '789 Market Street, Unit 12D', 'San Francisco', 'CA', '94103', 2018, 1, 1, 1, 0, 1, 0, 1, 1, 'active', '2025-04-29 12:41:58', '2025-04-29 12:41:58'),
(4, 3, 'Charming Townhouse Near Park', 'Charming townhouse located near a beautiful park. Features include an updated kitchen, hardwood floors, and a private patio.', 410000.00, 4, 3, 2, 1800.00, '321 Park Lane', 'Chicago', 'IL', '60601', 2009, 1, 1, 0, 1, 0, 1, 1, 1, 'active', '2025-04-29 12:41:58', '2025-04-29 12:41:58'),
(5, 2, 'Waterfront Home with Private Dock', 'Stunning waterfront home with a private dock. Enjoy breathtaking views and direct water access for boating and fishing enthusiasts.', 1200000.00, 1, 5, 4, 3500.00, '555 Ocean Drive', 'Miami', 'FL', '33101', 2012, 2, 1, 1, 1, 1, 1, 1, 1, 'active', '2025-04-29 12:41:58', '2025-04-29 12:41:58'),
(6, 3, 'Modern Loft in Art District', 'Stylish modern loft in the vibrant Art District. Features high ceilings, exposed brick walls, and large windows that flood the space with natural light.', 385000.00, 2, 1, 1, 1100.00, '888 Gallery Way, Loft 3C', 'Los Angeles', 'CA', '90013', 2010, 1, 1, 0, 0, 0, 0, 1, 1, 'active', '2025-04-29 12:41:58', '2025-04-29 12:41:58'),
(28, 7, 'Amber Abode', 'Amber Abode evokes warmth, comfort, and timeless elegance. The name suggests a home bathed in golden hues, embodying coziness and sophistication. It brings to mind a space where natural light enhances rich, earthy tones—perhaps with warm wooden accents, soft amber lighting, and a welcoming atmosphere.  Whether nestled in nature or designed with a modern touch, Amber Abode feels like a sanctuary where life glows with serenity and charm.', 6500000.00, 1, 10, 4, 50000.00, 'Crescini, San Antonio', 'Cavite City', 'Philippines', '4100', 2023, 1, 1, 1, 1, 0, 1, 1, 1, 'active', '2025-05-04 20:03:01', '2025-05-08 22:51:47'),
(31, 7, 'The Montierra', 'The Montierra is where sophistication meets serenity. Nestled in an elevated enclave, this premier condominium offers breathtaking views, contemporary architecture, and a lifestyle defined by comfort and prestige. Designed for discerning residents, The Montierra blends urban convenience with natural beauty—featuring resort-inspired amenities, smart living spaces, and seamless access to key destinations. Whether you&#039;re coming home to unwind or entertain, The Montierra is your sanctuary in the sky.', 14000000.00, 3, 8, 3, 3500.00, 'Capitol Commons, Meralco Ave', 'Pasig City', 'Philippines', '1600', 2025, 1, 1, 1, 0, 1, 0, 1, 1, 'active', '2025-05-10 00:42:47', '2025-05-10 00:57:16');

-- --------------------------------------------------------

--
-- Table structure for table `property_images`
--

CREATE TABLE `property_images` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `property_images`
--

INSERT INTO `property_images` (`id`, `property_id`, `image_path`, `is_primary`, `created_at`) VALUES
(1, 1, 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267', 1, '2025-04-29 12:41:58'),
(2, 1, 'https://images.unsplash.com/photo-1574362848149-11496d93a7c7', 0, '2025-04-29 12:41:58'),
(3, 2, 'https://images.unsplash.com/photo-1583608205776-bfd35f0d9f83', 1, '2025-04-29 12:41:58'),
(4, 2, 'https://images.unsplash.com/photo-1560184897-ae75f418493e', 0, '2025-04-29 12:41:58'),
(5, 3, 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750', 1, '2025-04-29 12:41:58'),
(6, 3, 'https://images.unsplash.com/photo-1493809842364-78817add7ffb', 0, '2025-04-29 12:41:58'),
(7, 4, 'https://images.unsplash.com/photo-1576941089067-2de3c901e126', 1, '2025-04-29 12:41:58'),
(8, 4, 'https://images.unsplash.com/photo-1568605114967-8130f3a36994', 0, '2025-04-29 12:41:58'),
(9, 5, 'https://images.unsplash.com/photo-1523217582562-09d0def993a6', 1, '2025-04-29 12:41:58'),
(10, 5, 'https://images.unsplash.com/photo-1600607688969-a5bfcd646154', 0, '2025-04-29 12:41:58'),
(11, 6, 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688', 1, '2025-04-29 12:41:58'),
(12, 6, 'https://images.unsplash.com/photo-1486304873000-235643847519', 0, '2025-04-29 12:41:58'),
(60, 28, '/uploads/properties/28_68175775a6834.jpg', 0, '2025-05-04 20:03:01'),
(61, 28, '/uploads/properties/28_68175775a6d0e.jpg', 0, '2025-05-04 20:03:01'),
(63, 28, '/uploads/properties/28_68175775a8523.jpg', 1, '2025-05-04 20:03:01'),
(66, 28, '/uploads/properties/28_681cc4b27e1c9.jpg', 0, '2025-05-08 22:50:26'),
(67, 28, '/uploads/properties/28_681cc4b27e71e.jpg', 0, '2025-05-08 22:50:26'),
(68, 31, '/uploads/properties/31_681e308768854.jpg', 0, '2025-05-10 00:42:47'),
(69, 31, '/uploads/properties/31_681e308768bf6.jpg', 0, '2025-05-10 00:42:47'),
(70, 31, '/uploads/properties/31_681e308769075.jpg', 0, '2025-05-10 00:42:47'),
(71, 31, '/uploads/properties/31_681e308769302.jpg', 0, '2025-05-10 00:42:47'),
(72, 31, '/uploads/properties/31_681e308769553.jpg', 1, '2025-05-10 00:42:47');

-- --------------------------------------------------------

--
-- Table structure for table `property_types`
--

CREATE TABLE `property_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `property_types`
--

INSERT INTO `property_types` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Single Family Home', 'Traditional house for a single family', '2025-04-29 12:41:58', '2025-04-29 12:41:58'),
(2, 'Apartment', 'A residential unit in a larger building complex', '2025-04-29 12:41:58', '2025-04-29 12:41:58'),
(3, 'Condo', 'An individually owned unit in a community of other units', '2025-04-29 12:41:58', '2025-04-29 12:41:58'),
(4, 'Townhouse', 'Attached houses with shared walls and multiple floors', '2025-04-29 12:41:58', '2025-04-29 12:41:58'),
(5, 'Land', 'Undeveloped real estate without buildings', '2025-04-29 12:41:58', '2025-04-29 12:41:58'),
(6, 'Commercial', 'Properties used for business purposes', '2025-04-29 12:41:58', '2025-04-29 12:41:58');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','reviewed','resolved') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','seller','buyer') NOT NULL,
  `status` enum('active','inactive','pending') DEFAULT 'active',
  `company_name` varchar(100) NOT NULL,
  `bio` text NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `zip_code` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password`, `role`, `status`, `company_name`, `bio`, `address`, `city`, `state`, `zip_code`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'XTate Prosystem', 'admin@example.com', '968-854-7501', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', '', '', '', '', '', '', '2025-04-29 12:41:58', '2025-05-10 00:56:24', '2025-05-10 00:56:24'),
(2, 'John Seller', 'seller@example.com', '555-123-4567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', 'active', '', '', '', '', '', '', '2025-04-29 12:41:58', '2025-05-08 21:49:25', '2025-05-08 21:49:25'),
(3, 'Mike Johnson', 'mike@example.com', '555-222-3333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', 'active', '', '', '', '', '', '', '2025-04-29 12:41:58', '2025-05-08 22:55:18', '2025-05-08 22:55:18'),
(4, 'Jane Buyer', 'buyer@example.com', '555-987-6543', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer', 'active', '', '', '', '', '', '', '2025-04-29 12:41:58', '2025-05-04 19:12:47', '2025-05-04 19:12:47'),
(5, 'Sara Williams', 'sara@example.com', '555-444-5555', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer', 'active', '', '', '', '', '', '', '2025-04-29 12:41:58', '2025-05-07 21:17:09', '2025-05-07 20:16:40'),
(6, 'Kei Hoho', 'micseihoho@gmail.com', '123-456-7890', '$2y$10$S7Mdnq0SXrqeMnopqlpPM.1g.hriGKcAwXUxOUWc2oZDJU/1lt9yu', 'buyer', 'active', '', 'Hi, I’m a serious buyer looking for the right property that fits my needs and budget. I value clear information, honest communication, and smooth transactions. Ready to explore and make a smart move!', 'Barangay 48-M, Bagong Pook, San Antonio', 'Cavite City', 'Philippines', '4100', '2025-04-29 13:10:35', '2025-05-11 14:36:00', '2025-05-11 14:36:00'),
(7, 'Mico Angelo Del Rosario', 'micoangelo024655@gmail.com', '123-456-7890', '$2y$10$U93gSjqcQa/CBofq3wGvFeR/e5scApk1sfZXVD11fJBSsZB3aUQzG', 'seller', 'active', 'Kei State', 'Ready to sell my property and connect with motivated buyers. I value clear communication and a quick, fair process.', 'Barangay 10-B Kingfisher Ariston Sison Rd., Seabreeze Subd.', 'Cavite City', 'Philippines', '4100', '2025-04-29 13:18:50', '2025-05-10 00:13:10', '2025-05-10 00:13:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`buyer_id`,`property_id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `property_type_id` (`property_type_id`);

--
-- Indexes for table `property_images`
--
ALTER TABLE `property_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `property_types`
--
ALTER TABLE `property_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reporter_id` (`reporter_id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `property_images`
--
ALTER TABLE `property_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `property_types`
--
ALTER TABLE `property_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD CONSTRAINT `inquiries_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inquiries_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `properties`
--
ALTER TABLE `properties`
  ADD CONSTRAINT `properties_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `properties_ibfk_2` FOREIGN KEY (`property_type_id`) REFERENCES `property_types` (`id`);

--
-- Constraints for table `property_images`
--
ALTER TABLE `property_images`
  ADD CONSTRAINT `property_images_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
