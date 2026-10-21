-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 20, 2026 at 03:06 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `racik_pos`
--

-- --------------------------------------------------------

--
-- Table structure for table `app_settings`
--

CREATE TABLE `app_settings` (
  `setting_key` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `app_settings`
--

INSERT INTO `app_settings` (`setting_key`, `setting_value`) VALUES
('business_name', 'Racik Coffee & Mart'),
('margin_alert', '35'),
('notifications', '1'),
('price_rounding', '500');

-- --------------------------------------------------------

--
-- Table structure for table `cost_history`
--

CREATE TABLE `cost_history` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` int UNSIGNED DEFAULT NULL,
  `previous_hpp` decimal(14,4) NOT NULL,
  `new_hpp` decimal(14,4) NOT NULL,
  `cost_change` decimal(14,4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stock` decimal(14,3) NOT NULL DEFAULT '0.000',
  `average_cost` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `min_stock` decimal(14,3) NOT NULL DEFAULT '0.000',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `name`, `sku`, `category`, `unit`, `stock`, `average_cost`, `min_stock`, `created_at`) VALUES
(1, 'Biji kopi arabika', 'BHN-001', 'Bahan baku', 'gram', 1782.000, 120.0000, 500.000, '2026-10-20 12:20:19'),
(2, 'Susu full cream', 'BHN-002', 'Bahan baku', 'ml', 2400.000, 25.0000, 1000.000, '2026-10-20 12:20:19'),
(3, 'Gula aren', 'BHN-003', 'Bahan baku', 'gram', 900.000, 45.0000, 300.000, '2026-10-20 12:20:19'),
(4, 'Gelas 16 oz', 'BHN-004', 'Kemasan', 'pcs', 47.000, 650.0000, 50.000, '2026-10-20 12:20:19'),
(5, 'Beras premium', 'RTL-001', 'Barang ritel', 'gram', 12000.000, 18.0000, 3000.000, '2026-10-20 12:20:19'),
(6, 'Air mineral botol', 'RTL-002', 'Barang ritel', 'pcs', 36.000, 2500.0000, 12.000, '2026-10-20 12:20:19'),
(7, 'nabati', 'm-400', 'makanan ringan', 'pcs', 200.000, 2000.0000, 0.000, '2026-10-20 12:31:36'),
(8, 'sosis', 'm-305', 'Bahan baku', 'pcs', 50.000, 8000.0000, 0.000, '2026-10-20 12:51:51'),
(9, 'saus', 'm-306', 'Bahan baku', 'ml', 8000.000, 54.5500, 3000.000, '2026-10-20 12:58:48'),
(10, 'kecap asin', 'M-307', 'Bahan baku', 'ml', 8000.000, 50.0000, 4000.000, '2026-10-20 13:17:21'),
(11, 'Garam halus', 'BHN-007', 'Bahan baku', 'gram', 500.000, 10.0000, 100.000, '2026-10-20 13:26:49'),
(12, 'Lada bubuk', 'BHN-008', 'Bahan baku', 'gram', 800.000, 200.0000, 800.000, '2026-10-20 13:26:49'),
(13, 'Kaldu bubuk', 'BHN-009', 'Bahan baku', 'gram', 100.000, 100.0000, 20.000, '2026-10-20 13:26:49'),
(26, 'Bawang bombay', 'BHN-010', 'Bahan baku', 'pcs', 1000.000, 1000.0000, 300.000, '2026-10-20 13:37:35'),
(84, 'telur', 'M-310', 'Bahan baku', 'pcs', 420.000, 2000.0000, 200.000, '2026-10-20 13:43:38');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` bigint UNSIGNED NOT NULL,
  `inventory_id` int UNSIGNED DEFAULT NULL,
  `movement_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(14,3) NOT NULL,
  `reference_note` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_movements`
--

INSERT INTO `inventory_movements` (`id`, `inventory_id`, `movement_type`, `quantity`, `reference_note`, `created_at`) VALUES
(1, 7, 'Penyesuaian', 200.000, 'Stok awal', '2026-10-20 12:31:36'),
(2, 8, 'Penyesuaian', 50.000, 'Stok awal', '2026-10-20 12:51:51'),
(3, 9, 'Penyesuaian', 8000.000, 'Stok awal', '2026-10-20 12:58:48'),
(4, 10, 'Penyesuaian', 8000.000, 'Stok awal', '2026-10-20 13:17:21'),
(5, 26, 'Penyesuaian', 990.000, 'Penyesuaian stok manual', '2026-10-20 13:40:26'),
(6, 12, 'Penyesuaian', 750.000, 'Penyesuaian stok manual', '2026-10-20 13:41:25'),
(7, 84, 'Penyesuaian', 400.000, 'Stok awal', '2026-10-20 13:43:38'),
(8, 84, 'Pembelian', 20.000, 'INV-20261020-142155', '2026-10-20 14:24:49'),
(9, 1, 'Penjualan', -18.000, 'TRX-261020-145419-341', '2026-10-20 14:54:19'),
(10, 4, 'Penjualan', -1.000, 'TRX-261020-145419-341', '2026-10-20 14:54:19');

-- --------------------------------------------------------

--
-- Table structure for table `price_history`
--

CREATE TABLE `price_history` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` int UNSIGNED DEFAULT NULL,
  `product_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_price` decimal(14,2) NOT NULL,
  `new_price` decimal(14,2) NOT NULL,
  `action_by` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Admin Demo',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `price_history`
--

INSERT INTO `price_history` (`id`, `product_id`, `product_name`, `old_price`, `new_price`, `action_by`, `created_at`) VALUES
(1, NULL, 'Beras premium 1 kg', 26000.00, 30000.00, 'Admin Demo', '2026-10-20 12:28:49');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_type` enum('menu','retail') COLLATE utf8mb4_unicode_ci NOT NULL,
  `selling_price` decimal(14,2) NOT NULL,
  `target_margin` decimal(5,2) NOT NULL DEFAULT '40.00',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `inventory_id` int UNSIGNED DEFAULT NULL,
  `stock_quantity` decimal(14,3) NOT NULL DEFAULT '1.000',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `sku`, `category`, `product_type`, `selling_price`, `target_margin`, `active`, `inventory_id`, `stock_quantity`, `created_at`) VALUES
(1, 'Es kopi susu', 'MENU-001', 'Minuman', 'menu', 22000.00, 55.00, 1, NULL, 1.000, '2026-10-20 12:20:19'),
(2, 'Americano', 'MENU-002', 'Minuman', 'menu', 18000.00, 60.00, 1, NULL, 1.000, '2026-10-20 12:20:19'),
(4, 'Air mineral 600 ml', 'RTL-102', 'Minuman', 'retail', 5000.00, 30.00, 1, 6, 1.000, '2026-10-20 12:20:19');

-- --------------------------------------------------------

--
-- Table structure for table `product_recipes`
--

CREATE TABLE `product_recipes` (
  `id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `inventory_id` int UNSIGNED NOT NULL,
  `quantity` decimal(14,3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_recipes`
--

INSERT INTO `product_recipes` (`id`, `product_id`, `inventory_id`, `quantity`) VALUES
(1, 1, 1, 18.000),
(2, 1, 2, 150.000),
(3, 1, 3, 20.000),
(4, 1, 4, 1.000),
(5, 2, 1, 18.000),
(6, 2, 4, 1.000);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_invoices`
--

CREATE TABLE `purchase_invoices` (
  `id` int UNSIGNED NOT NULL,
  `supplier_id` int UNSIGNED NOT NULL,
  `invoice_number` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purchase_date` date NOT NULL,
  `total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_invoices`
--

INSERT INTO `purchase_invoices` (`id`, `supplier_id`, `invoice_number`, `purchase_date`, `total`, `notes`, `created_at`) VALUES
(1, 1, 'INV-20261020-142155', '2026-10-20', 40000.00, '', '2026-10-20 14:24:49');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` int UNSIGNED NOT NULL,
  `invoice_id` int UNSIGNED NOT NULL,
  `inventory_id` int UNSIGNED NOT NULL,
  `quantity` decimal(14,3) NOT NULL,
  `unit_cost` decimal(14,4) NOT NULL,
  `line_total` decimal(14,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_items`
--

INSERT INTO `purchase_items` (`id`, `invoice_id`, `inventory_id`, `quantity`, `unit_cost`, `line_total`) VALUES
(1, 1, 84, 20.000, 2000.0000, 40000.00);

-- --------------------------------------------------------

--
-- Table structure for table `recommendations`
--

CREATE TABLE `recommendations` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` int UNSIGNED DEFAULT NULL,
  `status` enum('pending','accepted','rejected','applied') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `suggested_price` decimal(14,2) NOT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recommendations`
--

INSERT INTO `recommendations` (`id`, `product_id`, `status`, `suggested_price`, `reviewed_at`) VALUES
(1, NULL, 'rejected', 30000.00, '2026-10-20 12:22:09'),
(2, NULL, 'rejected', 30000.00, '2026-10-20 12:22:13'),
(3, NULL, 'rejected', 30000.00, '2026-10-20 12:22:19'),
(4, NULL, 'applied', 30000.00, '2026-10-20 12:28:49');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` bigint UNSIGNED NOT NULL,
  `sale_number` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` enum('Tunai','QRIS','Debit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cash_received` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total` decimal(14,2) NOT NULL,
  `gross_profit` decimal(14,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `sale_number`, `payment_method`, `cash_received`, `total`, `gross_profit`, `created_at`) VALUES
(1, 'TRX-261020-145419-341', 'Tunai', 18000.00, 18000.00, 15190.00, '2026-10-20 14:54:19');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` bigint UNSIGNED NOT NULL,
  `sale_id` bigint UNSIGNED NOT NULL,
  `product_id` int UNSIGNED DEFAULT NULL,
  `product_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku_snapshot` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(14,3) NOT NULL,
  `unit_price` decimal(14,2) NOT NULL,
  `hpp_snapshot` decimal(14,4) NOT NULL,
  `line_total` decimal(14,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `product_name`, `sku_snapshot`, `quantity`, `unit_price`, `hpp_snapshot`, `line_total`) VALUES
(1, 1, 2, 'Americano', 'MENU-002', 1.000, 18000.00, 2810.0000, 18000.00);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `created_at`) VALUES
(1, 'PT Sumber Pangan', '2026-10-20 12:20:19'),
(2, 'Koperasi Kopi Nusantara', '2026-10-20 12:20:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `cost_history`
--
ALTER TABLE `cost_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cost_product` (`product_id`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`);

--
-- Indexes for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_movement_inventory` (`inventory_id`);

--
-- Indexes for table `price_history`
--
ALTER TABLE `price_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_price_product` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `fk_products_inventory` (`inventory_id`);

--
-- Indexes for table `product_recipes`
--
ALTER TABLE `product_recipes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_product_ingredient` (`product_id`,`inventory_id`),
  ADD KEY `fk_recipe_inventory` (`inventory_id`);

--
-- Indexes for table `purchase_invoices`
--
ALTER TABLE `purchase_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `fk_purchase_supplier` (`supplier_id`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_purchase_item_invoice` (`invoice_id`),
  ADD KEY `fk_purchase_item_inventory` (`inventory_id`);

--
-- Indexes for table `recommendations`
--
ALTER TABLE `recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recommendation_product` (`product_id`,`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sale_number` (`sale_number`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sale_item_sale` (`sale_id`),
  ADD KEY `fk_sale_item_product` (`product_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cost_history`
--
ALTER TABLE `cost_history`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=565;

--
-- AUTO_INCREMENT for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `price_history`
--
ALTER TABLE `price_history`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `product_recipes`
--
ALTER TABLE `product_recipes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `purchase_invoices`
--
ALTER TABLE `purchase_invoices`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `recommendations`
--
ALTER TABLE `recommendations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cost_history`
--
ALTER TABLE `cost_history`
  ADD CONSTRAINT `fk_cost_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD CONSTRAINT `fk_movement_inventory` FOREIGN KEY (`inventory_id`) REFERENCES `inventory_items` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `price_history`
--
ALTER TABLE `price_history`
  ADD CONSTRAINT `fk_price_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_inventory` FOREIGN KEY (`inventory_id`) REFERENCES `inventory_items` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_recipes`
--
ALTER TABLE `product_recipes`
  ADD CONSTRAINT `fk_recipe_inventory` FOREIGN KEY (`inventory_id`) REFERENCES `inventory_items` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_recipe_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_invoices`
--
ALTER TABLE `purchase_invoices`
  ADD CONSTRAINT `fk_purchase_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD CONSTRAINT `fk_purchase_item_inventory` FOREIGN KEY (`inventory_id`) REFERENCES `inventory_items` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_purchase_item_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recommendations`
--
ALTER TABLE `recommendations`
  ADD CONSTRAINT `fk_recommendation_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `fk_sale_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sale_item_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
