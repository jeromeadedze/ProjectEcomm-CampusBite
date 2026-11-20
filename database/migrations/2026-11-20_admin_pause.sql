-- ============================================================
-- Admin pause. Run once on an existing campusbite database, after 2026-11-20_paystack.sql.
-- Fresh installs from campusbite.sql don't need it.
-- ============================================================

-- 1 when the CampusBite admin paused the kitchen. The kitchen can't reopen itself until the admin sets it Active again.
ALTER TABLE `brands` ADD COLUMN `admin_paused` tinyint(1) NOT NULL DEFAULT 0 AFTER `is_active`;
