-- ============================================================
-- Run this once on an existing campusbite database (phpMyAdmin > SQL tab).
-- Fresh installs don't need it: database/campusbite.sql already includes these changes.
-- ============================================================

-- Phase 1: customer's special request on each ordered meal
ALTER TABLE `orderdetails` ADD COLUMN `notes` varchar(255) DEFAULT NULL AFTER `qty`;

-- Phase 2: courier assigned to an order (a customer row with user_role = 3).
-- The courier_id column was already in the schema; this links it to the customer table.
ALTER TABLE `orders`
  ADD KEY `courier_id` (`courier_id`),
  ADD CONSTRAINT `orders_courier_fk` FOREIGN KEY (`courier_id`) REFERENCES `customer` (`customer_id`) ON DELETE SET NULL;

-- Phase 2: saved favourite meals
CREATE TABLE IF NOT EXISTS `favorites` (
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`customer_id`, `product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `favorites_c_fk` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `favorites_p_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Phase 2: support messages from the contact page
CREATE TABLE IF NOT EXISTS `support_messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `topic` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Open', -- Open, Resolved
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`),
  KEY `customer_id` (`customer_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `support_c_fk` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `support_o_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
