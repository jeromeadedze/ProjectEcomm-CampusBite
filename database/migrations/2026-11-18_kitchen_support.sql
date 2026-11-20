-- ============================================================
-- Direct support messages to kitchens, and give kitchens phone/WhatsApp contacts.
-- Run once on an existing campusbite database (phpMyAdmin > SQL tab), after 2026-11-17_kitchen_delivery.sql.
-- ============================================================

-- Add a direct phone contact to each kitchen
ALTER TABLE `brands` ADD COLUMN `brand_phone` varchar(20) DEFAULT NULL AFTER `location_desc`;

UPDATE `brands` SET `brand_phone` = '0241002001' WHERE `brand_id` = 1; -- Hallmark Cafeteria
UPDATE `brands` SET `brand_phone` = '0241002002' WHERE `brand_id` = 2; -- Akonnor Cafeteria
UPDATE `brands` SET `brand_phone` = '0241002003' WHERE `brand_id` = 3; -- Munchees Grills
UPDATE `brands` SET `brand_phone` = '0241002004' WHERE `brand_id` = 4; -- Peckish Berekuso
UPDATE `brands` SET `brand_phone` = '0241002005' WHERE `brand_id` = 5; -- Eat Healthy Spot
UPDATE `brands` SET `brand_phone` = '0241002006' WHERE `brand_id` = 6; -- Hi5 Lounge & Kitchen

-- Allow support messages to be routed directly to a kitchen
ALTER TABLE `support_messages` ADD COLUMN `brand_id` int(11) DEFAULT NULL AFTER `order_id`;
ALTER TABLE `support_messages` ADD KEY `brand_id` (`brand_id`);
ALTER TABLE `support_messages` ADD CONSTRAINT `support_b_fk` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`brand_id`) ON DELETE SET NULL;

-- Link existing order support messages to their kitchen
UPDATE `support_messages` s
JOIN `orders` o ON s.`order_id` = o.`order_id`
SET s.`brand_id` = o.`brand_id`
WHERE s.`brand_id` IS NULL AND s.`order_id` IS NOT NULL;
