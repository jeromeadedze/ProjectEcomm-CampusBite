-- ============================================================
-- Real Paystack payments. Run once on an existing campusbite database, after 2026-11-18_kitchen_support.sql.
-- Fresh installs from campusbite.sql don't need it.
-- ============================================================

-- When Paystack confirmed the payment (older, simulated payments stay NULL)
ALTER TABLE `payment` ADD COLUMN `verified_at` datetime DEFAULT NULL AFTER `payment_date`;

-- One Paystack reference can only pay for one order, even if the confirmation page is opened twice
ALTER TABLE `payment` ADD UNIQUE KEY `transaction_reference` (`transaction_reference`);
