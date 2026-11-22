-- ============================================================
-- Kitchen admins and kitchen staff. Run once on an existing campusbite database (phpMyAdmin > SQL tab),
-- after the earlier migrations. Fresh installs don't need it.
--
-- A kitchen account (user_role = 4) is now either:
--   'admin' : the kitchen's owner or manager. Sees sales, manages staff logins, menu and prices, payout account.
--   'staff' : works the shift. Handles orders and customer messages, marks meals sold out. Never sees money.
-- ============================================================

ALTER TABLE `cb_customer` ADD COLUMN `kitchen_role` varchar(10) DEFAULT NULL AFTER `brand_id`;

-- Everyone who already had a kitchen login keeps full access
UPDATE `cb_customer` SET `kitchen_role` = 'admin' WHERE `user_role` = 4;

-- Demo staff login for Munchees Grills (local testing only, like the other demo accounts)
INSERT INTO `cb_customer` (`customer_name`, `customer_email`, `customer_pass`, `customer_contact`, `user_role`, `brand_id`, `kitchen_role`)
SELECT 'Munchees Staff', 'staff@campusbite.com', '$2y$10$pljQtJ7HI79D60r/zk5hEuC1DgYDmFC6PMn7YRe9JuoT5x2TKX6ES', '0241002013', 4, 3, 'staff' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `cb_customer` WHERE `customer_email` = 'staff@campusbite.com');
