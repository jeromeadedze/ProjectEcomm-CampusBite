-- Sprint 20: customer management. Admins can suspend a customer account; sign-up dates are recorded from now on.
-- Run once on an existing database, after 2026-11-22_support_replies.sql.

ALTER TABLE `customer`
  ADD COLUMN `is_suspended` tinyint(1) NOT NULL DEFAULT 0 AFTER `pass_expiry`,
  ADD COLUMN `suspended_at` datetime DEFAULT NULL AFTER `is_suspended`,
  ADD COLUMN `suspend_reason` varchar(255) DEFAULT NULL AFTER `suspended_at`,
  ADD COLUMN `created_at` datetime DEFAULT NULL AFTER `suspend_reason`;

-- Accounts that already exist keep an empty sign-up date (it was never recorded); new accounts get the time they sign up
ALTER TABLE `customer` MODIFY `created_at` datetime DEFAULT CURRENT_TIMESTAMP;
