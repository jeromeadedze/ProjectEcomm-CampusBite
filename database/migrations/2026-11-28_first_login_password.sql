-- ============================================================
-- Kitchen logins get a starting password made for them (like Munchees@Kwame47), which is easy to guess.
-- This flag makes those accounts set their own password the first time they sign in.
-- Run once on an existing campusbite database, after the earlier migrations. Fresh installs don't need it.
-- ============================================================
ALTER TABLE `cb_customer` ADD COLUMN `must_change_password` tinyint(1) NOT NULL DEFAULT 0 AFTER `kitchen_role`;
