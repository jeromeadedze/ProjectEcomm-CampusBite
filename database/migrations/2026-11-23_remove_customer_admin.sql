-- Sprint 21: the super admin no longer manages customers, so account suspension is gone.
-- Removes the columns 2026-11-22_customer_admin.sql added. Run once, after that file.

ALTER TABLE `customer`
  DROP COLUMN `is_suspended`,
  DROP COLUMN `suspended_at`,
  DROP COLUMN `suspend_reason`,
  DROP COLUMN `created_at`;
