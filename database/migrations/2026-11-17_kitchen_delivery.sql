-- ============================================================
-- Kitchens deliver their own orders, and payouts use the commission rate at the time of each order.
-- Run once on an existing campusbite database (phpMyAdmin > SQL tab), after 2026-11-09_kitchen_portal.sql.
-- Fresh installs from campusbite.sql don't need it.
-- ============================================================

-- The courier role (3) is retired: former couriers become regular customers
UPDATE `customer` SET `user_role` = 2 WHERE `user_role` = 3;

-- Orders no longer have a courier
ALTER TABLE `orders` DROP FOREIGN KEY `orders_courier_fk`;
ALTER TABLE `orders` DROP KEY `courier_id`, DROP COLUMN `courier_id`;

-- The kitchen's commission rate when the order was placed
ALTER TABLE `orders` ADD COLUMN `commission_rate` decimal(4,2) DEFAULT NULL AFTER `special_instructions`;

-- Older orders: best available value is the kitchen's current rate
UPDATE `orders` o JOIN `brands` b ON o.`brand_id` = b.`brand_id`
SET o.`commission_rate` = b.`commission_rate`
WHERE o.`commission_rate` IS NULL;
