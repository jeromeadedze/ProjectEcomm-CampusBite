-- ============================================================
-- Kitchen portal. Run once on an existing campusbite database (phpMyAdmin > SQL tab),
-- after 2026-11-08_phase1_phase2.sql. Fresh installs don't need it.
-- ============================================================

-- Kitchen accounts (user_role = 4) are linked to the kitchen they run
ALTER TABLE `customer`
  ADD COLUMN `brand_id` int(11) DEFAULT NULL AFTER `user_role`,
  ADD KEY `brand_id` (`brand_id`),
  ADD CONSTRAINT `customer_brand_fk` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`brand_id`) ON DELETE SET NULL;

-- Every order now comes from exactly one kitchen
ALTER TABLE `orders`
  ADD COLUMN `brand_id` int(11) DEFAULT NULL AFTER `customer_id`,
  ADD KEY `brand_id` (`brand_id`),
  ADD CONSTRAINT `orders_brand_fk` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`brand_id`);

-- Older orders: use the kitchen of their first meal
UPDATE `orders` o
SET o.`brand_id` = (SELECT p.`product_brand` FROM `orderdetails` od JOIN `products` p ON od.`product_id` = p.`product_id`
                    WHERE od.`order_id` = o.`order_id` ORDER BY p.`product_id` LIMIT 1)
WHERE o.`brand_id` IS NULL;

-- The price each meal was sold at, so totals and kitchen revenue don't change when a menu price changes
ALTER TABLE `orderdetails` ADD COLUMN `price` double DEFAULT NULL AFTER `qty`;

-- Older order lines: best available guess is today's menu price
UPDATE `orderdetails` od JOIN `products` p ON od.`product_id` = p.`product_id`
SET od.`price` = p.`product_price`
WHERE od.`price` IS NULL;
