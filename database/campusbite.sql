-- ============================================================
-- CampusBite Database Schema
-- Campus Food Ordering & Delivery Platform for Ashesi University
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `order_tracking`;
DROP TABLE IF EXISTS `payment`;
DROP TABLE IF EXISTS `orderdetails`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `cart`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `brands`; -- Acts as Kitchens/Vendors (Hallmark, Akonnor, Munchees, Peckish, Eat Healthy, Hi5)
DROP TABLE IF EXISTS `categories`; -- Food Categories (Rice & Bowls, Local Dishes, Grills & Fast Food, Drinks, Snacks, Healthy)
DROP TABLE IF EXISTS `customer`;

-- ── 1. customer (Students, Faculty, Couriers, Admins) ───────────────
CREATE TABLE `customer` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_pass` varchar(255) NOT NULL,
  `customer_country` varchar(50) NOT NULL DEFAULT 'Ghana',
  `customer_city` varchar(50) NOT NULL DEFAULT 'Berekuso',
  `customer_contact` varchar(20) NOT NULL,
  `campus_hostel` varchar(100) DEFAULT NULL, -- e.g. 'Walter Sisulu Hall', 'Wangari Maathai', 'Charlotte Court', etc.
  `customer_image` varchar(150) DEFAULT NULL,
  `user_role` int(11) NOT NULL DEFAULT 2, -- 1: Admin, 2: Customer (Student/Faculty), 3: Student Courier, 4: Kitchen Merchant
  `has_pass` tinyint(1) NOT NULL DEFAULT 0, -- 1 if customer subscribed to GH₵ 35 CampusBite Pass
  `pass_expiry` date DEFAULT NULL,
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `customer_email` (`customer_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 2. brands / vendors (Kitchens & Food Spots) ─────────────────────
CREATE TABLE `brands` (
  `brand_id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_name` varchar(100) NOT NULL,
  `zone` enum('Zone A', 'Zone B') NOT NULL DEFAULT 'Zone A', -- Zone A: On-campus, Zone B: Berekuso corridor
  `location_desc` varchar(255) DEFAULT NULL,
  `commission_rate` decimal(4,2) NOT NULL DEFAULT 0.10,
  `brand_image` varchar(150) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 3. categories (Meal types) ──────────────────────────────────────
CREATE TABLE `categories` (
  `cat_id` int(11) NOT NULL AUTO_INCREMENT,
  `cat_name` varchar(100) NOT NULL,
  `cat_icon` varchar(50) DEFAULT 'bi-egg-fried',
  PRIMARY KEY (`cat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 4. products (Food Menu Items) ───────────────────────────────────
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_cat` int(11) NOT NULL,
  `product_brand` int(11) NOT NULL, -- references kitchen/vendor
  `product_title` varchar(200) NOT NULL,
  `product_price` double NOT NULL,
  `product_desc` text DEFAULT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `product_keywords` varchar(200) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `prep_time_minutes` int(11) NOT NULL DEFAULT 15,
  PRIMARY KEY (`product_id`),
  KEY `product_cat` (`product_cat`),
  KEY `product_brand` (`product_brand`),
  CONSTRAINT `products_cat_fk` FOREIGN KEY (`product_cat`) REFERENCES `categories` (`cat_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `products_brand_fk` FOREIGN KEY (`product_brand`) REFERENCES `brands` (`brand_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 5. cart ────────────────────────────────────────────────────────
CREATE TABLE `cart` (
  `p_id` int(11) NOT NULL,
  `ip_add` varchar(50) NOT NULL,
  `c_id` int(11) DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `notes` varchar(255) DEFAULT NULL,
  KEY `p_id` (`p_id`),
  KEY `c_id` (`c_id`),
  CONSTRAINT `cart_p_fk` FOREIGN KEY (`p_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cart_c_fk` FOREIGN KEY (`c_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 6. orders ──────────────────────────────────────────────────────
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `invoice_no` int(11) NOT NULL,
  `order_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `order_status` varchar(50) NOT NULL DEFAULT 'Placed', -- Placed, Preparing, Ready, In Transit, Delivered, Cancelled
  `delivery_zone` varchar(20) NOT NULL DEFAULT 'Zone A', -- Zone A or Zone B
  `delivery_fee` double NOT NULL DEFAULT 4.00,
  `drop_location` varchar(150) NOT NULL, -- e.g. 'Walter Sisulu Hall - Room 204', 'FabLab Courtyard'
  `special_instructions` varchar(255) DEFAULT NULL,
  `courier_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `orders_c_fk` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 7. orderdetails ────────────────────────────────────────────────
CREATE TABLE `orderdetails` (
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `orderdetails_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `orderdetails_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 8. payment ─────────────────────────────────────────────────────
CREATE TABLE `payment` (
  `pay_id` int(11) NOT NULL AUTO_INCREMENT,
  `amt` double NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'GHS',
  `payment_mode` varchar(50) NOT NULL DEFAULT 'Paystack - MoMo', -- MTN MoMo, Telecel Cash, Paystack, Flutterwave, Cash/On-Delivery
  `transaction_reference` varchar(100) DEFAULT NULL,
  `payment_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pay_id`),
  KEY `customer_id` (`customer_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `payment_c_fk` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`),
  CONSTRAINT `payment_o_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 9. order_tracking (Live timeline tracking) ─────────────────────
CREATE TABLE `order_tracking` (
  `tracking_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `status_message` varchar(255) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tracking_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_tracking_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA (Campus Vendors, Categories & Representative Meals)
-- ============================================================

INSERT INTO `categories` (`cat_id`, `cat_name`, `cat_icon`) VALUES
(1, 'Rice & Jollof Bowls', 'bi-bowl'),
(2, 'Local Staples (Fufu & Banku)', 'bi-fire'),
(3, 'Grills & Shawarma', 'bi-segmented-nav'),
(4, 'Healthy Bowls & Salads', 'bi-heart-pulse'),
(5, 'Pastries & Quick Bites', 'bi-cup-hot'),
(6, 'Drinks & Fresh Smoothies', 'bi-cup-straw');

INSERT INTO `brands` (`brand_id`, `brand_name`, `zone`, `location_desc`, `commission_rate`, `brand_image`) VALUES
(1, 'Hallmark Cafeteria', 'Zone A', 'Ashesi Campus Ground Floor Court', 0.08, 'hallmark.jpg'),
(2, 'Akonnor Cafeteria', 'Zone A', 'Akonnor Canteen Hub (Upper Floor)', 0.08, 'akonnor.jpg'),
(3, 'Munchees Grills', 'Zone A', 'Student Center Pavilion', 0.10, 'munchees.jpg'),
(4, 'Peckish Berekuso', 'Zone B', 'Berekuso Town Junction', 0.10, 'peckish.jpg'),
(5, 'Eat Healthy Spot', 'Zone B', 'Off-Campus Valley View Road', 0.09, 'eathealthy.jpg'),
(6, 'Hi5 Lounge & Kitchen', 'Zone B', 'Berekuso Main Street', 0.10, 'hi5.jpg');

INSERT INTO `products` (`product_id`, `product_cat`, `product_brand`, `product_title`, `product_price`, `product_desc`, `product_image`, `product_keywords`, `is_available`, `prep_time_minutes`) VALUES
(1, 1, 1, 'Hallmark Special Jollof + Grilled Chicken', 35.00, 'Smoky Ghana jollof rice served with seasoned crispy grilled chicken quarter, shito and fresh coleslaw.', 'jollof_chicken.jpg', 'jollof, chicken, hallmark, lunch, rice', 1, 15),
(2, 1, 2, 'Akonnor Fried Rice + Spicy Gizzard', 32.00, 'Savory wok-tossed fried rice with diced garden veggies, pepper gizzard and chili sauce.', 'fried_rice.jpg', 'fried rice, gizzard, akonnor, lunch', 1, 15),
(3, 3, 3, 'Munchees Beef Shawarma Wrap (Extra Cheese)', 30.00, 'Loaded double beef wrap with tahini-garlic cream sauce, melted cheddar, lettuce and french fries inside.', 'shawarma.jpg', 'shawarma, beef, munchees, snack, dinner', 1, 10),
(4, 2, 2, 'Akonnor Banku with Grilled Tilapia & Hot Pepper', 45.00, 'Two balls of soft fresh banku served with whole spiced grilled tilapia fish, green pepper and red chili sauce.', 'banku_tilapia.jpg', 'banku, tilapia, local, akonnor, fish', 1, 20),
(5, 4, 5, 'Eat Healthy Grilled Chicken & Avocado Salad', 38.00, 'Romaine lettuce, diced ripe avocado, cherry tomatoes, cucumbers and tender grilled chicken breast with honey mustard dressing.', 'salad_bowl.jpg', 'healthy, salad, avocado, diet, eat healthy', 1, 12),
(6, 1, 4, 'Peckish Loaded Fried Plantain & Beans (Red Red)', 25.00, 'Sweet fried ripe plantain with slow-cooked black-eyed beans stew, boiled egg and fried gari topping.', 'red_red.jpg', 'peckish, beans, plantain, red red, local', 1, 15),
(7, 3, 3, 'Munchees Late-Night Suya Platter', 35.00, 'Sliced tender beef suya dusted in spicy yaji pepper blend, served with sliced onions and fresh tomatoes.', 'suya.jpg', 'suya, beef, munchees, late night', 1, 10),
(8, 6, 5, 'Fresh Pineapple Ginger Juice (500ml)', 15.00, 'Cold-pressed natural sweet pineapple juice infused with fresh spicy ginger root. No added sugar.', 'juice.jpg', 'juice, drink, ginger, healthy', 1, 5);

COMMIT;
