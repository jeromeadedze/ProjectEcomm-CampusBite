-- ============================================================
-- Migration: delivery fees by drop point
-- Orders now record the listed drop point the fee was worked out for (controllers/DeliveryController.php);
-- drop_location keeps the full text the kitchen sees, e.g. "Sisulu Hall · Room 208".
-- Saved drop points move to the new names. "Dufie Hostel" is now two hostels (Annex and Platinum),
-- so those customers are asked to pick again (set to NULL; checkout and Settings ask for it).
-- Run once, after 2026-11-29_unique_invoice.sql.
-- ============================================================

ALTER TABLE `cb_orders` ADD COLUMN `drop_point` varchar(60) DEFAULT NULL AFTER `delivery_fee`;

-- Drop points a kitchen delivers to for the near fee on top of its own area (comma-separated).
-- Queenstar is far for every kitchen except Yummu Yum.
ALTER TABLE `cb_brands` ADD COLUMN `near_drop_points` varchar(255) DEFAULT NULL AFTER `zone`;
UPDATE `cb_brands` SET `near_drop_points` = 'Queenstar' WHERE LOWER(REPLACE(`brand_name`, ' ', '')) IN ('yummuyum', 'yummyyum');

UPDATE `cb_customer` SET `campus_hostel` = 'Sisulu Hall' WHERE `campus_hostel` = 'Walter Sisulu Hall';
UPDATE `cb_customer` SET `campus_hostel` = 'Maathai Hall' WHERE `campus_hostel` = 'Wangari Maathai Hall';
UPDATE `cb_customer` SET `campus_hostel` = 'Masere' WHERE `campus_hostel` = 'Masere Hostel';
UPDATE `cb_customer` SET `campus_hostel` = NULL WHERE `campus_hostel` = 'Dufie Hostel';
