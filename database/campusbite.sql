-- ============================================================
-- CampusBite Database Schema
-- Campus Food Ordering & Delivery Platform for Ashesi University
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- Customer and brands point at each other, so the drops and creates run with foreign key checks off
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `support_replies`;
DROP TABLE IF EXISTS `support_messages`;
DROP TABLE IF EXISTS `favorites`;
DROP TABLE IF EXISTS `order_tracking`;
DROP TABLE IF EXISTS `refunds`;
DROP TABLE IF EXISTS `payouts`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `payment`;
DROP TABLE IF EXISTS `orderdetails`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `cart`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `brands`; -- Acts as Kitchens/Vendors (Hallmark, Akonnor, Munchees, Peckish, Eat Healthy, Hi5)
DROP TABLE IF EXISTS `categories`; -- Food Categories (Rice & Bowls, Local Dishes, Grills & Fast Food, Drinks, Snacks, Healthy)
DROP TABLE IF EXISTS `customer`;

-- ── 1. customer (Students, Faculty, Kitchen staff, Admins) ──────────
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
  `user_role` int(11) NOT NULL DEFAULT 2, -- 1: Admin, 2: Customer (Student/Faculty), 4: Kitchen (role 3, courier, was retired: kitchens deliver their own orders)
  `brand_id` int(11) DEFAULT NULL, -- for kitchen accounts (role 4): the kitchen they run
  `has_pass` tinyint(1) NOT NULL DEFAULT 0, -- 1 if customer subscribed to GH₵ 35 CampusBite Pass
  `pass_expiry` date DEFAULT NULL,
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `customer_email` (`customer_email`),
  KEY `brand_id` (`brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 2. brands / vendors (Kitchens & Food Spots) ─────────────────────
CREATE TABLE `brands` (
  `brand_id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_name` varchar(100) NOT NULL,
  `zone` enum('Zone A', 'Zone B') NOT NULL DEFAULT 'Zone A', -- Zone A: On-campus, Zone B: Berekuso corridor
  `location_desc` varchar(255) DEFAULT NULL,
  `brand_phone` varchar(20) DEFAULT NULL,
  `commission_rate` decimal(4,2) NOT NULL DEFAULT 0.10,
  `brand_image` varchar(150) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `admin_paused` tinyint(1) NOT NULL DEFAULT 0, -- 1: paused by the admin; the kitchen can't reopen itself
  -- Where Paystack pays the kitchen its share of each order (split payments). Set by the kitchen in Kitchen info.
  `payout_type` varchar(20) DEFAULT NULL, -- 'mobile_money' or 'bank'
  `payout_bank_code` varchar(20) DEFAULT NULL, -- Paystack's code for the network or bank, e.g. MTN, 040100
  `payout_bank_name` varchar(100) DEFAULT NULL,
  `payout_account_number` varchar(30) DEFAULT NULL,
  `payout_account_name` varchar(100) DEFAULT NULL,
  `paystack_subaccount` varchar(40) DEFAULT NULL, -- Paystack subaccount code (ACCT_...); no code = can't take orders yet
  PRIMARY KEY (`brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kitchen accounts point at their kitchen (added here because brands is created after customer)
ALTER TABLE `customer` ADD CONSTRAINT `customer_brand_fk` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`brand_id`) ON DELETE SET NULL;

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
  `brand_id` int(11) DEFAULT NULL, -- the one kitchen this order is from
  `invoice_no` int(11) NOT NULL,
  `order_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `order_status` varchar(50) NOT NULL DEFAULT 'Placed', -- Placed, Preparing, Ready, In Transit, Delivered, Cancelled
  `delivery_zone` varchar(20) NOT NULL DEFAULT 'Zone A', -- Zone A or Zone B
  `delivery_fee` double NOT NULL DEFAULT 5.00,
  `drop_location` varchar(150) NOT NULL, -- e.g. 'Walter Sisulu Hall - Room 204', 'FabLab Courtyard'
  `special_instructions` varchar(255) DEFAULT NULL,
  `commission_rate` decimal(4,2) DEFAULT NULL, -- the kitchen's commission when the order was placed, so later rate changes don't alter past payouts
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` varchar(20) DEFAULT NULL, -- customer, kitchen or admin
  `cancel_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `customer_id` (`customer_id`),
  KEY `brand_id` (`brand_id`),
  CONSTRAINT `orders_brand_fk` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`brand_id`),
  CONSTRAINT `orders_c_fk` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 7. orderdetails ────────────────────────────────────────────────
CREATE TABLE `orderdetails` (
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `price` double DEFAULT NULL, -- price per meal at the time of the order
  `notes` varchar(255) DEFAULT NULL, -- customer's special request, copied from the cart
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
  `verified_at` datetime DEFAULT NULL, -- when Paystack confirmed this payment; NULL for simulated payments
  `subaccount_code` varchar(40) DEFAULT NULL, -- the kitchen's Paystack subaccount the payment was split with; NULL = all went to CampusBite
  `kitchen_share` decimal(10,2) DEFAULT NULL, -- what Paystack sends the kitchen
  `campusbite_share` decimal(10,2) DEFAULT NULL, -- CampusBite's commission; Paystack's fee comes out of this
  `refund_recovery` decimal(10,2) NOT NULL DEFAULT 0.00, -- taken from the kitchen's share to repay earlier refunds (negative = handed back)
  PRIMARY KEY (`pay_id`),
  UNIQUE KEY `transaction_reference` (`transaction_reference`),
  KEY `customer_id` (`customer_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `payment_c_fk` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`),
  CONSTRAINT `payment_o_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Refunds for cancelled orders (one per order, the full amount paid, through Paystack)
CREATE TABLE `refunds` (
  `refund_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `pay_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending', -- pending, processing, processed, failed, not_needed (the payment was simulated)
  `paystack_refund_id` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`refund_id`),
  UNIQUE KEY `refund_order` (`order_id`),
  KEY `pay_id` (`pay_id`),
  CONSTRAINT `refunds_o_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `refunds_p_fk` FOREIGN KEY (`pay_id`) REFERENCES `payment` (`pay_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Forgot-password links. Only a SHA-256 hash of each link's token is stored
CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `request_ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reset_id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `customer_id` (`customer_id`),
  KEY `request_ip` (`request_ip`, `created_at`),
  CONSTRAINT `password_resets_c_fk` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE
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

-- ── 10. favorites ───────────────────────────────────────────────────
CREATE TABLE `favorites` (
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`customer_id`, `product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `favorites_c_fk` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `favorites_p_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 11. support_messages ────────────────────────────────────────────
CREATE TABLE `support_messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `topic` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Open', -- Open, Resolved
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`),
  KEY `customer_id` (`customer_id`),
  KEY `order_id` (`order_id`),
  KEY `brand_id` (`brand_id`),
  CONSTRAINT `support_c_fk` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `support_o_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL,
  CONSTRAINT `support_b_fk` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`brand_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Replies in a support conversation, from the customer, the kitchen or a CampusBite admin
CREATE TABLE `support_replies` (
  `reply_id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `author_id` int(11) DEFAULT NULL,
  `author_role` varchar(10) NOT NULL,       -- customer, kitchen, admin
  `body` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reply_id`),
  KEY `message_id` (`message_id`),
  KEY `author_id` (`author_id`),
  CONSTRAINT `support_replies_m_fk` FOREIGN KEY (`message_id`) REFERENCES `support_messages` (`message_id`) ON DELETE CASCADE,
  CONSTRAINT `support_replies_a_fk` FOREIGN KEY (`author_id`) REFERENCES `customer` (`customer_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA (Campus Vendors, Categories & Representative Meals)
-- ============================================================

INSERT INTO `categories` (`cat_id`, `cat_name`, `cat_icon`) VALUES
(1, 'Rice & Jollof Bowls', 'bi-egg-fried'),
(2, 'Local Staples (Fufu & Banku)', 'bi-fire'),
(3, 'Grills & Suya', 'bi-segmented-nav'),
(4, 'Healthy Bowls & Salads', 'bi-heart-pulse'),
(5, 'Pastries & Quick Bites', 'bi-cup-hot'),
(6, 'Drinks & Fresh Smoothies', 'bi-cup-straw'),
(7, 'Shawarma & Wraps', 'bi-basket'),
(8, 'Desserts & Cakes', 'bi-cake2'),
(9, 'Soft Drinks', 'bi-cup');

INSERT INTO `brands` (`brand_id`, `brand_name`, `zone`, `location_desc`, `brand_phone`, `commission_rate`, `brand_image`) VALUES
(1, 'Hallmark Cafeteria', 'Zone A', 'Ashesi Campus Ground Floor Court', '0241002001', 0.08, 'hallmark.jpg'),
(2, 'Akonnor Cafeteria', 'Zone A', 'Akonnor Canteen Hub (Upper Floor)', '0241002002', 0.08, 'akonnor.jpg'),
(3, 'Munchees Grills', 'Zone A', 'Student Center Pavilion', '0241002003', 0.10, 'munchees.jpg'),
(4, 'Peckish Berekuso', 'Zone B', 'Berekuso Town Junction', '0241002004', 0.10, 'peckish.jpg'),
(5, 'Eat Healthy Spot', 'Zone B', 'Off-Campus Valley View Road', '0241002005', 0.09, 'eathealthy.jpg'),
(6, 'Hi5 Lounge & Kitchen', 'Zone B', 'Berekuso Main Street', '0241002006', 0.10, 'hi5.jpg');

INSERT INTO `products` (`product_id`, `product_cat`, `product_brand`, `product_title`, `product_price`, `product_desc`, `product_image`, `product_keywords`, `is_available`, `prep_time_minutes`) VALUES
(1, 1, 1, 'Hallmark Special Jollof + Grilled Chicken', 35.00, 'Smoky Ghana jollof rice served with seasoned crispy grilled chicken quarter, shito and fresh coleslaw.', 'jollof_chicken.jpg', 'jollof, chicken, hallmark, lunch, rice', 1, 15),
(2, 1, 2, 'Akonnor Fried Rice + Spicy Gizzard', 32.00, 'Savory wok-tossed fried rice with diced garden veggies, pepper gizzard and chili sauce.', 'fried_rice.jpg', 'fried rice, gizzard, akonnor, lunch', 1, 15),
(3, 7, 3, 'Munchees Beef Shawarma Wrap (Extra Cheese)', 30.00, 'Loaded double beef wrap with tahini-garlic cream sauce, melted cheddar, lettuce and french fries inside.', 'shawarma.jpg', 'shawarma, beef, munchees, snack, dinner', 1, 10),
(4, 2, 2, 'Akonnor Banku with Grilled Tilapia & Hot Pepper', 45.00, 'Two balls of soft fresh banku served with whole spiced grilled tilapia fish, green pepper and red chili sauce.', 'banku_tilapia.jpg', 'banku, tilapia, local, akonnor, fish', 1, 20),
(5, 4, 5, 'Eat Healthy Grilled Chicken & Avocado Salad', 38.00, 'Romaine lettuce, diced ripe avocado, cherry tomatoes, cucumbers and tender grilled chicken breast with honey mustard dressing.', 'salad_bowl.jpg', 'healthy, salad, avocado, diet, eat healthy', 1, 12),
(6, 1, 4, 'Peckish Loaded Fried Plantain & Beans (Red Red)', 25.00, 'Sweet fried ripe plantain with slow-cooked black-eyed beans stew, boiled egg and fried gari topping.', 'red_red.jpg', 'peckish, beans, plantain, red red, local', 1, 15),
(7, 3, 3, 'Munchees Late-Night Suya Platter', 35.00, 'Sliced tender beef suya dusted in spicy yaji pepper blend, served with sliced onions and fresh tomatoes.', 'suya.jpg', 'suya, beef, munchees, late night', 1, 10),
(8, 6, 5, 'Fresh Pineapple Ginger Juice (500ml)', 15.00, 'Cold-pressed natural sweet pineapple juice infused with fresh spicy ginger root. No added sugar.', 'pineapple_juice.jpg', 'juice, drink, ginger, healthy', 1, 5),
(9, 7, 1, 'Hallmark Classic Chicken Shawarma', 28.00, 'Grilled chicken strips, garlic sauce, pickles, fries and lettuce rolled in toasted pita bread.', 'chicken_shawarma.jpg', 'shawarma, chicken, hallmark, wrap', 1, 10),
(10, 5, 1, 'Hallmark Butter Croissant', 12.00, 'Flaky all-butter croissant baked fresh every morning and served warm.', 'butter_croissant.jpg', 'croissant, pastry, breakfast, hallmark', 1, 5),
(11, 8, 1, 'Hallmark Chocolate Fudge Cake Slice', 18.00, 'Layered chocolate sponge with rich fudge frosting.', 'choc_cake.jpg', 'cake, chocolate, dessert, hallmark', 1, 5),
(12, 9, 1, 'Coca-Cola Glass Bottle (300ml)', 8.00, 'Ice-cold Coke in the classic glass bottle, with a glass of ice on the side.', 'coke_bottle.jpg', 'coke, coca-cola, soda, drink', 1, 2),
(13, 6, 1, 'Hallmark Mango Smoothie', 18.00, 'Ripe mango blended with yoghurt and a touch of honey.', 'mango_smoothie.jpg', 'smoothie, mango, drink, hallmark', 1, 5),
(14, 2, 2, 'Akonnor Waakye Special', 30.00, 'Rice and beans cooked with sorghum leaves, with fried plantain, boiled egg, gari, shito and stew.', 'waakye.jpg', 'waakye, beans, local, akonnor', 1, 15),
(15, 7, 2, 'Akonnor Spicy Beef Shawarma with Shito', 30.00, 'Peppered beef, crunchy cabbage and shito mayo in a pita, toasted until crisp.', 'beef_shawarma_spicy.jpg', 'shawarma, beef, spicy, shito, akonnor', 1, 12),
(16, 6, 2, 'Akonnor Sobolo (Hibiscus Ginger)', 10.00, 'Chilled hibiscus drink brewed with ginger, cloves and pineapple peel.', 'sobolo.jpg', 'sobolo, hibiscus, drink, local', 1, 2),
(17, 8, 2, 'Akonnor Coconut Cake Slice', 15.00, 'Moist coconut sponge with a coconut cream topping.', 'coconut_cake.jpg', 'cake, coconut, dessert, akonnor', 1, 5),
(18, 9, 2, 'Fanta Orange Can (330ml)', 8.00, 'A chilled can of Fanta Orange.', 'fanta.jpg', 'fanta, soda, orange, drink', 1, 2),
(19, 7, 3, 'Munchees Mixed Shawarma Platter', 42.00, 'Chicken and beef shawarma meat over fries with garlic sauce, chilli and salad. No wrap.', 'shawarma_platter.jpg', 'shawarma, platter, fries, munchees', 1, 15),
(20, 3, 3, 'Munchees Peri-Peri Chicken Wings (8 pcs)', 35.00, 'Crispy wings tossed in hot peri-peri sauce, with a ranch dip.', 'chicken_wings.jpg', 'wings, chicken, grill, peri-peri, munchees', 1, 15),
(21, 9, 3, 'Coca-Cola Can (330ml)', 8.00, 'An ice-cold can of Coca-Cola.', 'coke_can.jpg', 'coke, coca-cola, soda, drink', 1, 2),
(22, 6, 3, 'Munchees Oreo Milkshake', 22.00, 'Vanilla ice cream blended with Oreo cookies and milk, topped with cream.', 'oreo_shake.jpg', 'milkshake, oreo, shake, munchees', 1, 5),
(23, 8, 3, 'Munchees Red Velvet Cupcake', 12.00, 'Red velvet cupcake with cream cheese frosting.', 'red_velvet.jpg', 'cupcake, red velvet, dessert, munchees', 1, 3),
(24, 2, 4, 'Peckish Spicy Kelewele', 18.00, 'Ripe plantain cubes fried with ginger, chilli and spices, served with roasted groundnuts.', 'kelewele.jpg', 'kelewele, plantain, local, peckish', 1, 10),
(25, 5, 4, 'Peckish Sausage & Cheese Croissant', 20.00, 'Warm croissant filled with grilled sausage, melted cheese, tomato and lettuce.', 'sausage_croissant.jpg', 'croissant, sausage, breakfast, peckish', 1, 8),
(26, 8, 4, 'Peckish Banana Bread Slice', 12.00, 'Homemade banana bread with walnuts, baked daily.', 'banana_bread.jpg', 'banana bread, cake, dessert, peckish', 1, 3),
(27, 9, 4, 'Sprite Can (330ml)', 8.00, 'A chilled can of Sprite.', 'sprite.jpg', 'sprite, soda, lemon, drink', 1, 2),
(28, 7, 4, 'Peckish Shawarma Fries', 28.00, 'Crispy fries loaded with chicken shawarma, cheese sauce and spring onion.', 'shawarma_fries.jpg', 'shawarma, fries, chicken, peckish', 1, 12),
(29, 7, 5, 'Eat Healthy Grilled Chicken Wholewheat Wrap', 30.00, 'Grilled chicken breast, hummus, spinach and cucumber in a wholewheat wrap.', 'chicken_wrap.jpg', 'wrap, chicken, healthy, eat healthy', 1, 10),
(30, 6, 5, 'Eat Healthy Berry Protein Smoothie', 25.00, 'Mixed berries, banana, oats, Greek yoghurt and a scoop of protein.', 'berry_smoothie.jpg', 'smoothie, berry, protein, healthy', 1, 5),
(31, 6, 5, 'Eat Healthy Green Detox Smoothie', 22.00, 'Spinach, cucumber, green apple, kiwi, ginger and lemon. No added sugar.', 'green_smoothie.jpg', 'smoothie, green, detox, healthy', 1, 5),
(32, 8, 5, 'Eat Healthy Greek Yoghurt Parfait', 20.00, 'Greek yoghurt layered with granola, strawberries and blueberries.', 'parfait.jpg', 'parfait, yoghurt, dessert, healthy', 1, 3),
(33, 4, 5, 'Eat Healthy Quinoa Power Bowl', 35.00, 'Quinoa with roasted vegetables, chickpeas, greens and a lemon tahini dressing.', 'quinoa_bowl.jpg', 'quinoa, bowl, vegan, healthy', 1, 12),
(34, 7, 6, 'Hi5 Lamb Shawarma Platter', 48.00, 'Spiced lamb shawarma with rice, salad, flatbread and garlic sauce.', 'lamb_platter.jpg', 'shawarma, lamb, platter, hi5', 1, 18),
(35, 5, 6, 'Hi5 Chocolate Croissant', 15.00, 'Buttery pain au chocolat with dark chocolate inside.', 'choc_croissant.jpg', 'croissant, chocolate, pastry, hi5', 1, 3),
(36, 8, 6, 'Hi5 Strawberry Cheesecake Slice', 25.00, 'Creamy baked cheesecake with strawberry glaze.', 'cheesecake.jpg', 'cheesecake, strawberry, dessert, hi5', 1, 3),
(37, 9, 6, 'Hi5 Coke Float', 18.00, 'Coca-Cola poured over two scoops of vanilla ice cream.', 'coke_float.jpg', 'coke float, ice cream, soda, hi5', 1, 5),
(38, 6, 6, 'Hi5 Tropical Passion Smoothie', 22.00, 'Passion fruit, mango and pineapple blended with ice.', 'tropical_smoothie.jpg', 'smoothie, passion fruit, tropical, hi5', 1, 5),
(39, 1, 6, 'Hi5 Jollof with Chicken Kebabs', 40.00, 'Party-style jollof rice topped with grilled chicken and pepper kebabs.', 'jollof_kebab.jpg', 'jollof, kebab, chicken, hi5', 1, 18);


-- Demo accounts, so a fresh import can be signed into straight away (passwords are in README.md: demo only, change
-- or delete these on any real server). The kitchen login runs Munchees Grills. New sign-ups are customers (role 2).
INSERT INTO `customer` (`customer_id`, `customer_name`, `customer_email`, `customer_pass`, `customer_contact`, `user_role`, `brand_id`) VALUES
(1, 'CampusBite Admin', 'admin@campusbite.com', '$2y$12$4n3JILZhgVDkN34KVB90huBwquGjeUQk5Z8e3mFYGJhtKVp.qoe6i', '0241000001', 1, NULL),
(2, 'Munchees Kitchen', 'kitchen@campusbite.com', '$2y$12$ukKcIGJf/trm.WO9LgkYKOY/8vWRmTJ0LWqwi4x4uzrL5fNI0kBra', '0241002003', 4, 3);
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
