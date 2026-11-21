-- Refunds on split payments: Paystack takes a refund from CampusBite's main balance, including the part it had already
-- paid the kitchen. CampusBite takes that part back from the kitchen's next orders through the split, and each payment
-- records how much it took back. Run once, after 2026-11-24_paystack_split.sql.

ALTER TABLE `payment`
  ADD COLUMN `refund_recovery` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `campusbite_share`;
