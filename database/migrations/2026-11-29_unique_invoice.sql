-- ============================================================
-- Migration: every order gets its own invoice number
-- Invoice numbers used to be picked at random with no check, so two orders could share one.
-- Any duplicates already there are renumbered (the oldest order keeps its number; the others get
-- 1000000 + their order ID, which can't clash with the 6-digit random numbers), then the column is made unique.
-- Run once, after 2026-11-28_first_login_password.sql.
-- ============================================================

UPDATE `cb_orders` o
JOIN (
    SELECT o2.order_id
    FROM `cb_orders` o2
    JOIN `cb_orders` first ON first.invoice_no = o2.invoice_no AND first.order_id < o2.order_id
    GROUP BY o2.order_id
) dup ON dup.order_id = o.order_id
SET o.invoice_no = 1000000 + o.order_id;

ALTER TABLE `cb_orders` ADD UNIQUE KEY `invoice_no` (`invoice_no`);
