-- Sprint 22: Paystack split payments. Each kitchen has a Paystack subaccount (its Mobile Money or bank account),
-- and Paystack pays the kitchen its share of every order directly. CampusBite no longer holds kitchens' money,
-- so recorded payouts are gone. Run once, after 2026-11-23_remove_customer_admin.sql.

ALTER TABLE `brands`
  ADD COLUMN `payout_type` varchar(20) DEFAULT NULL AFTER `admin_paused`,
  ADD COLUMN `payout_bank_code` varchar(20) DEFAULT NULL AFTER `payout_type`,
  ADD COLUMN `payout_bank_name` varchar(100) DEFAULT NULL AFTER `payout_bank_code`,
  ADD COLUMN `payout_account_number` varchar(30) DEFAULT NULL AFTER `payout_bank_name`,
  ADD COLUMN `payout_account_name` varchar(100) DEFAULT NULL AFTER `payout_account_number`,
  ADD COLUMN `paystack_subaccount` varchar(40) DEFAULT NULL AFTER `payout_account_name`;

ALTER TABLE `payment`
  ADD COLUMN `subaccount_code` varchar(40) DEFAULT NULL AFTER `verified_at`,
  ADD COLUMN `kitchen_share` decimal(10,2) DEFAULT NULL AFTER `subaccount_code`,
  ADD COLUMN `campusbite_share` decimal(10,2) DEFAULT NULL AFTER `kitchen_share`;

-- Payouts recorded by hand (Sprint 16) are no longer used
DROP TABLE IF EXISTS `payouts`;
