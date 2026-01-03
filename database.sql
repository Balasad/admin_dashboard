-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 03, 2026 at 07:38 AM
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
-- Database: `classified_ads`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `email`, `created_at`) VALUES
(1, 'admin', '$2y$10$S1TAM/jGlN3C0KZr5OAXaeJRey131s83bTaUfgdsbZTBG/.ZxDdt2', 'admin@example.com', '2025-12-31 04:36:56');

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `category_id`, `name`, `is_active`, `created_at`) VALUES
(1, 2, 'Samsung', 1, '2025-12-30 11:30:18'),
(2, 2, 'Apple', 1, '2025-12-30 11:30:18'),
(3, 2, 'OnePlus', 1, '2025-12-30 11:30:18'),
(4, 2, 'Xiaomi', 1, '2025-12-30 11:30:18'),
(5, 2, 'Realme', 1, '2025-12-30 11:30:18'),
(6, 2, 'Vivo', 1, '2025-12-30 11:30:18'),
(7, 2, 'Oppo', 1, '2025-12-30 11:30:18'),
(8, 2, 'Google', 1, '2025-12-30 11:30:18'),
(9, 5, 'Dell', 1, '2025-12-30 11:30:18'),
(10, 5, 'HP', 1, '2025-12-30 11:30:18'),
(11, 5, 'Lenovo', 1, '2025-12-30 11:30:18'),
(12, 5, 'Apple', 1, '2025-12-30 11:30:18'),
(13, 5, 'Asus', 1, '2025-12-30 11:30:18'),
(14, 5, 'Acer', 1, '2025-12-30 11:30:18'),
(15, 6, 'Maruti Suzuki', 1, '2025-12-30 11:30:18'),
(16, 6, 'Hyundai', 1, '2025-12-30 11:30:18'),
(17, 6, 'Tata', 1, '2025-12-30 11:30:18'),
(18, 6, 'Honda', 1, '2025-12-30 11:30:18'),
(19, 6, 'Mahindra', 1, '2025-12-30 11:30:18'),
(20, 6, 'Toyota', 1, '2025-12-30 11:30:18'),
(21, 6, 'mahindra', 1, '2025-12-30 11:36:36'),
(22, 7, 'nissan', 1, '2025-12-30 12:59:23'),
(23, 12, 'Yamaha Motors', 1, '2025-12-31 05:35:33'),
(24, 6, 'nissan', 1, '2026-01-02 08:52:27');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `slug` varchar(100) NOT NULL,
  `icon` varchar(10) DEFAULT '?',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `parent_id`, `slug`, `icon`, `created_at`) VALUES
(1, 'Electronics', NULL, 'electronics', '', '2025-12-30 11:30:18'),
(2, 'Mobiles', 1, 'mobiles', '', '2025-12-30 11:30:18'),
(3, 'Smartphones', 2, 'smartphones', '', '2025-12-30 11:30:18'),
(4, 'Feature Phones', 2, 'feature-phones', '', '2025-12-30 11:30:18'),
(5, 'Laptops', 1, 'laptops', '', '2025-12-30 11:30:18'),
(6, 'Cars', NULL, 'cars', '', '2025-12-30 11:30:18'),
(7, 'Sedan', 6, 'sedan', '', '2025-12-30 11:30:18'),
(8, 'SUV', 6, 'suv', '', '2025-12-30 11:30:18'),
(9, 'Furniture', NULL, 'furniture', '', '2025-12-30 11:30:18'),
(10, 'Tables', 9, '-ables', '', '2025-12-31 04:52:38'),
(11, 'Motors', 9, 'Bikes', '', '2025-12-31 04:54:23'),
(12, 'Motors', 11, '-otors', '', '2025-12-31 05:31:45'),
(13, 'Cars', 3, '-ars', '', '2026-01-03 04:05:50');

-- --------------------------------------------------------

--
-- Table structure for table `category_fields`
--

CREATE TABLE `category_fields` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `field_type` enum('text','number','dropdown','textarea','brand_dropdown','model_dropdown') DEFAULT 'text',
  `is_mandatory` tinyint(1) DEFAULT 0,
  `dropdown_options` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category_fields`
--

INSERT INTO `category_fields` (`id`, `category_id`, `field_name`, `field_type`, `is_mandatory`, `dropdown_options`, `display_order`, `created_at`) VALUES
(1, 1, 'Condition', 'dropdown', 1, '[\"Brand New\", \"Like New\", \"Good\", \"Fair\", \"Used\"]', 1, '2025-12-30 11:30:18'),
(2, 1, 'Warranty Available', 'dropdown', 0, '[\"Yes\", \"No\"]', 2, '2025-12-30 11:30:18'),
(3, 2, 'Brand', 'brand_dropdown', 1, NULL, 1, '2025-12-30 11:30:18'),
(4, 2, 'Model', 'model_dropdown', 1, NULL, 2, '2025-12-30 11:30:18'),
(5, 2, 'RAM', 'dropdown', 1, '[\"2GB\", \"3GB\", \"4GB\", \"6GB\", \"8GB\", \"12GB\", \"16GB\"]', 3, '2025-12-30 11:30:18'),
(6, 2, 'Storage', 'dropdown', 1, '[\"32GB\", \"64GB\", \"128GB\", \"256GB\", \"512GB\", \"1TB\"]', 4, '2025-12-30 11:30:18'),
(7, 2, 'Color', 'dropdown', 1, '[\"Black\", \"White\", \"Blue\", \"Green\", \"Red\", \"Gold\", \"Silver\", \"Purple\"]', 5, '2025-12-30 11:30:18'),
(8, 2, 'Battery Capacity', 'dropdown', 0, '[\"3000-4000mAh\", \"4000-5000mAh\", \"5000-6000mAh\", \"6000mAh+\"]', 6, '2025-12-30 11:30:18'),
(9, 3, 'Operating System', 'dropdown', 1, '[\"Android\", \"iOS\"]', 1, '2025-12-30 11:30:18'),
(10, 3, 'Screen Size', 'dropdown', 1, '[\"5.5-6.0\"\", \"6.0-6.5\"\", \"6.5-7.0\"\", \"7.0+\"]', 2, '2025-12-30 11:30:18'),
(11, 3, 'Camera', 'dropdown', 1, '[\"12MP\", \"48MP\", \"50MP\", \"64MP\", \"108MP\", \"200MP\"]', 3, '2025-12-30 11:30:18'),
(12, 3, '5G Support', 'dropdown', 1, '[\"Yes\", \"No\"]', 4, '2025-12-30 11:30:18'),
(13, 5, 'Brand', 'brand_dropdown', 1, NULL, 1, '2025-12-30 11:30:18'),
(14, 5, 'Model', 'model_dropdown', 1, NULL, 2, '2025-12-30 11:30:18'),
(15, 5, 'Processor', 'dropdown', 1, '[\"Intel Core i3\", \"Intel Core i5\", \"Intel Core i7\", \"Intel Core i9\", \"AMD Ryzen 5\", \"AMD Ryzen 7\", \"Apple M1\", \"Apple M2\"]', 3, '2025-12-30 11:30:18'),
(16, 5, 'RAM', 'dropdown', 1, '[\"4GB\", \"8GB\", \"16GB\", \"32GB\", \"64GB\"]', 4, '2025-12-30 11:30:18'),
(17, 5, 'Storage Type', 'dropdown', 1, '[\"HDD\", \"SSD\", \"SSD + HDD\"]', 5, '2025-12-30 11:30:18'),
(18, 5, 'Storage Capacity', 'dropdown', 1, '[\"256GB\", \"512GB\", \"1TB\", \"2TB\"]', 6, '2025-12-30 11:30:18'),
(19, 5, 'Screen Size', 'dropdown', 1, '[\"13\"\", \"14\"\", \"15.6\"\", \"17\"\"]', 7, '2025-12-30 11:30:18'),
(20, 6, 'Brand', 'brand_dropdown', 1, NULL, 1, '2025-12-30 11:30:18'),
(21, 6, 'Year', 'dropdown', 1, '[\"2024\", \"2023\", \"2022\", \"2021\", \"2020\", \"2019\", \"2018\", \"2017\"]', 2, '2025-12-30 11:30:18'),
(22, 6, 'Fuel Type', 'dropdown', 1, '[\"Petrol\", \"Diesel\", \"CNG\", \"Electric\", \"Hybrid\"]', 3, '2025-12-30 11:30:18'),
(23, 6, 'Transmission', 'dropdown', 1, '[\"Manual\", \"Automatic\", \"AMT\", \"CVT\"]', 4, '2025-12-30 11:30:18'),
(24, 6, 'Kilometers Driven', 'dropdown', 1, '[\"0-10,000\", \"10,000-25,000\", \"25,000-50,000\", \"50,000-75,000\", \"75,000-1,00,000\", \"1,00,000+\"]', 5, '2025-12-30 11:30:18'),
(25, 6, 'Owners', 'dropdown', 1, '[\"1st Owner\", \"2nd Owner\", \"3rd Owner\", \"4th+ Owner\"]', 6, '2025-12-30 11:30:18'),
(26, 10, 'Brands', 'dropdown', 0, '[\"Top\"]', 0, '2025-12-31 04:52:38'),
(27, 11, 'New Ones', 'dropdown', 1, '[\"TEST 1\"]', 0, '2025-12-31 04:54:23'),
(28, 11, 'Color', 'dropdown', 0, '[\"ters_2\"]', 1, '2025-12-31 04:54:23'),
(29, 12, 'Bikes', 'brand_dropdown', 1, NULL, 0, '2025-12-31 05:31:45'),
(30, 12, 'Duke', 'dropdown', 1, '[\"Orange\",\"Black\",\"White\"]', 1, '2025-12-31 05:31:45'),
(31, 13, 'Bikes', 'text', 1, NULL, 0, '2026-01-03 04:05:50');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_by_role` enum('admin','user') DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`user_id`, `name`, `email`, `phone`, `address`, `role`, `created_by`, `created_at`, `updated_by`, `updated_by_role`, `updated_at`) VALUES
(4, 'Kalaiselvi', 'skalaiselviskalaiselvi1@gmail.com', NULL, '16/33 karpaga kanniyamman koil Street', 'user', NULL, '2026-01-01 16:30:51', NULL, NULL, NULL),
(6, 'siva', 'sivas@teramed.in', '4575875', '16/8 singaperumal street,triplicane chennai-5', 'user', NULL, '2026-01-01 16:46:29', NULL, '', '2026-01-02 09:44:50'),
(9, 'testing', 'bala@gmail.com', '6385925032', 'jkkokpef', 'user', NULL, '2026-01-02 09:05:16', NULL, '', '2026-01-02 09:37:19'),
(10, 'Admin User', 'admin@example.com', '1234567890', 'Admin Address', 'admin', NULL, '2026-01-02 09:34:41', NULL, NULL, NULL),
(12, 'tester', 'admin@midrate.com', '562389585564', 'gtrhtrh', 'admin', 1, '2026-01-02 09:39:18', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `models`
--

CREATE TABLE `models` (
  `id` int(11) NOT NULL,
  `brand_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `models`
--

INSERT INTO `models` (`id`, `brand_id`, `name`, `is_active`, `created_at`) VALUES
(1, 1, 'Galaxy S24 Ultra', 1, '2025-12-30 11:30:18'),
(2, 1, 'Galaxy S24', 1, '2025-12-30 11:30:18'),
(3, 1, 'Galaxy S23 FE', 1, '2025-12-30 11:30:18'),
(4, 1, 'Galaxy A54', 1, '2025-12-30 11:30:18'),
(5, 1, 'Galaxy M34', 1, '2025-12-30 11:30:18'),
(6, 2, 'iPhone 15 Pro Max', 1, '2025-12-30 11:30:18'),
(7, 2, 'iPhone 15 Pro', 1, '2025-12-30 11:30:18'),
(8, 2, 'iPhone 15', 1, '2025-12-30 11:30:18'),
(9, 2, 'iPhone 14', 1, '2025-12-30 11:30:18'),
(10, 2, 'iPhone 13', 1, '2025-12-30 11:30:18'),
(11, 2, 'iPhone SE', 1, '2025-12-30 11:30:18'),
(12, 3, 'OnePlus 12', 1, '2025-12-30 11:30:18'),
(13, 3, 'OnePlus 11', 1, '2025-12-30 11:30:18'),
(14, 3, 'OnePlus Nord 3', 1, '2025-12-30 11:30:18'),
(15, 9, 'XPS 13', 1, '2025-12-30 11:30:18'),
(16, 9, 'XPS 15', 1, '2025-12-30 11:30:18'),
(17, 9, 'Inspiron 15', 1, '2025-12-30 11:30:18'),
(18, 9, 'Alienware m15', 1, '2025-12-30 11:30:18'),
(19, 12, 'MacBook Pro 14\"', 1, '2025-12-30 11:30:18'),
(20, 12, 'MacBook Pro 16\"', 1, '2025-12-30 11:30:18'),
(21, 12, 'MacBook Air M2', 1, '2025-12-30 11:30:18'),
(22, 12, 'MacBook Air M1', 1, '2025-12-30 11:30:18'),
(23, 22, 'nissan', 1, '2025-12-30 12:59:32'),
(24, 23, 'RN-49', 1, '2025-12-31 05:36:28'),
(25, 18, 'bike', 1, '2026-01-02 08:52:59');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `model_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `user_phone` varchar(20) DEFAULT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `status` enum('active','sold','inactive') DEFAULT 'active',
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `seller_id`, `category_id`, `brand_id`, `model_id`, `title`, `description`, `price`, `location`, `user_name`, `user_phone`, `user_email`, `status`, `views`, `created_at`) VALUES
(1, 1, 11, NULL, NULL, 'test', 'testing', 4555.00, 'chennai', 'Tester', '1254162316', '', 'active', 0, '2025-12-31 04:55:06'),
(2, NULL, 6, 22, NULL, 'cars', 'sfegtr', 78000.00, 'chennai', 'tester', '55613786', '', 'sold', 0, '2026-01-02 08:54:35'),
(3, NULL, 7, 22, NULL, 'cars', 'desf', 56000.00, 'Kochi', 'testing', '123456', '', 'active', 0, '2026-01-02 10:31:54'),
(4, NULL, 7, 22, NULL, 'cars', 'frgfrgerg', 230000.00, 'Kochi', 'tester', '123456', '', 'active', 0, '2026-01-02 10:34:27'),
(5, NULL, 7, 22, NULL, 'klohkui', 'kylo', 56865.00, 'Kochi', 'tester_1', '45645456', '', 'active', 0, '2026-01-02 10:35:30'),
(6, 1, 7, 22, NULL, 'feradeqwr', 'c cccgrth', 4500058.00, 'Kochi', 'bala_12', '1234568', '', 'active', 0, '2026-01-02 10:48:32'),
(7, 1, 6, 11, NULL, 'phone', 'cefwef', 457899.00, 'Kochi', 'bala_23', '56892348', '', 'active', 0, '2026-01-02 10:49:47'),
(8, 1, 10, NULL, NULL, 'hkgky', 'mjukyik', 450058.00, 'Kochi', 'bala', '4589256756', '', 'sold', 0, '2026-01-03 04:08:58');

-- --------------------------------------------------------

--
-- Table structure for table `product_field_values`
--

CREATE TABLE `product_field_values` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `field_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_field_values`
--

INSERT INTO `product_field_values` (`id`, `product_id`, `field_id`, `field_value`) VALUES
(1, 1, 27, 'TEST 1'),
(2, 1, 28, 'ters_2'),
(3, 2, 20, '22'),
(4, 2, 21, '2024'),
(5, 2, 22, 'Petrol'),
(6, 2, 23, 'Manual'),
(7, 2, 24, '25,000-50,000'),
(8, 2, 25, '2nd Owner'),
(9, 3, 20, '22'),
(10, 3, 21, '2021'),
(11, 3, 22, 'CNG'),
(12, 3, 23, 'Automatic'),
(13, 3, 24, '10,000-25,000'),
(14, 3, 25, '1st Owner'),
(15, 4, 20, '22'),
(16, 4, 21, '2024'),
(17, 4, 22, 'Petrol'),
(18, 4, 23, 'Manual'),
(19, 4, 24, '10,000-25,000'),
(20, 4, 25, '2nd Owner'),
(21, 5, 20, '22'),
(22, 5, 21, '2022'),
(23, 5, 22, 'Petrol'),
(24, 5, 23, 'Manual'),
(25, 5, 24, '25,000-50,000'),
(26, 5, 25, '2nd Owner'),
(27, 6, 20, '22'),
(28, 6, 21, '2019'),
(29, 6, 22, 'CNG'),
(30, 6, 23, 'AMT'),
(31, 6, 24, '50,000-75,000'),
(32, 6, 25, '2nd Owner'),
(33, 7, 26, 'Top'),
(34, 8, 26, 'Top');

-- --------------------------------------------------------

--
-- Table structure for table `sellers`
--

CREATE TABLE `sellers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sellers`
--

INSERT INTO `sellers` (`id`, `name`, `email`, `phone`, `password`, `location`, `is_active`, `created_at`) VALUES
(1, 'bala', 'bala@gmail.com', '6385925032', '$2y$10$nVrS9YA5WLd3/Nm4EwHAz.rOstk1/y0IK0VGwA9YP.NE43iTAcX8a', 'chennai', 1, '2025-12-31 06:53:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parent` (`parent_id`);

--
-- Indexes for table `category_fields`
--
ALTER TABLE `category_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `fk_updated_by` (`updated_by`),
  ADD KEY `fk_created_by` (`created_by`);

--
-- Indexes for table `models`
--
ALTER TABLE `models`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_brand` (`brand_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `brand_id` (`brand_id`),
  ADD KEY `model_id` (`model_id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `product_field_values`
--
ALTER TABLE `product_field_values`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `field_id` (`field_id`);

--
-- Indexes for table `sellers`
--
ALTER TABLE `sellers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `category_fields`
--
ALTER TABLE `category_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `models`
--
ALTER TABLE `models`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `product_field_values`
--
ALTER TABLE `product_field_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `sellers`
--
ALTER TABLE `sellers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `brands`
--
ALTER TABLE `brands`
  ADD CONSTRAINT `brands_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `category_fields`
--
ALTER TABLE `category_fields`
  ADD CONSTRAINT `category_fields_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `models`
--
ALTER TABLE `models`
  ADD CONSTRAINT `models_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  ADD CONSTRAINT `products_ibfk_3` FOREIGN KEY (`model_id`) REFERENCES `models` (`id`),
  ADD CONSTRAINT `products_ibfk_4` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_field_values`
--
ALTER TABLE `product_field_values`
  ADD CONSTRAINT `product_field_values_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_field_values_ibfk_2` FOREIGN KEY (`field_id`) REFERENCES `category_fields` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
