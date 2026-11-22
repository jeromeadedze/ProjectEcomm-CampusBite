-- ============================================================
-- Migration: Demo admin accounts for all six kitchens.
-- Run once on an existing database in phpMyAdmin > SQL tab.
-- Password for all kitchen accounts: Kitchen123!
-- ============================================================

INSERT INTO `cb_customer` (`customer_name`, `customer_email`, `customer_pass`, `customer_contact`, `user_role`, `brand_id`, `kitchen_role`)
SELECT 'Hallmark Kitchen', 'hallmark@campusbite.com', '$2y$12$ukKcIGJf/trm.WO9LgkYKOY/8vWRmTJ0LWqwi4x4uzrL5fNI0kBra', '0241002001', 4, 1, 'admin'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `cb_customer` WHERE `customer_email` = 'hallmark@campusbite.com');

INSERT INTO `cb_customer` (`customer_name`, `customer_email`, `customer_pass`, `customer_contact`, `user_role`, `brand_id`, `kitchen_role`)
SELECT 'Akonnor Kitchen', 'akonnor@campusbite.com', '$2y$12$ukKcIGJf/trm.WO9LgkYKOY/8vWRmTJ0LWqwi4x4uzrL5fNI0kBra', '0241002002', 4, 2, 'admin'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `cb_customer` WHERE `customer_email` = 'akonnor@campusbite.com');

INSERT INTO `cb_customer` (`customer_name`, `customer_email`, `customer_pass`, `customer_contact`, `user_role`, `brand_id`, `kitchen_role`)
SELECT 'Peckish Kitchen', 'peckish@campusbite.com', '$2y$12$ukKcIGJf/trm.WO9LgkYKOY/8vWRmTJ0LWqwi4x4uzrL5fNI0kBra', '0241002004', 4, 4, 'admin'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `cb_customer` WHERE `customer_email` = 'peckish@campusbite.com');

INSERT INTO `cb_customer` (`customer_name`, `customer_email`, `customer_pass`, `customer_contact`, `user_role`, `brand_id`, `kitchen_role`)
SELECT 'Eat Healthy Kitchen', 'eathealthy@campusbite.com', '$2y$12$ukKcIGJf/trm.WO9LgkYKOY/8vWRmTJ0LWqwi4x4uzrL5fNI0kBra', '0241002005', 4, 5, 'admin'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `cb_customer` WHERE `customer_email` = 'eathealthy@campusbite.com');

INSERT INTO `cb_customer` (`customer_name`, `customer_email`, `customer_pass`, `customer_contact`, `user_role`, `brand_id`, `kitchen_role`)
SELECT 'Hi5 Kitchen', 'hi5@campusbite.com', '$2y$12$ukKcIGJf/trm.WO9LgkYKOY/8vWRmTJ0LWqwi4x4uzrL5fNI0kBra', '0241002006', 4, 6, 'admin'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `cb_customer` WHERE `customer_email` = 'hi5@campusbite.com');
