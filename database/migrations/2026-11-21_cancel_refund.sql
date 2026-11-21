-- ============================================================
-- Cancelling orders and Paystack refunds. Run once on an existing campusbite database, after 2026-11-20_admin_pause.sql.
-- Fresh installs from campusbite.sql don't need it.
-- ============================================================

-- Who cancelled an order, when and why (customer before the kitchen starts, kitchen rejecting it, or the admin)
ALTER TABLE `orders`
  ADD COLUMN `cancelled_at` datetime DEFAULT NULL AFTER `commission_rate`,
  ADD COLUMN `cancelled_by` varchar(20) DEFAULT NULL AFTER `cancelled_at`,
  ADD COLUMN `cancel_reason` varchar(255) DEFAULT NULL AFTER `cancelled_by`;

-- One refund per cancelled order: the full amount paid, sent back through Paystack
CREATE TABLE `refunds` (
  `refund_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `pay_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending', -- pending, processing, processed, failed, not_needed (the payment was simulated)
  `paystack_refund_id` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`refund_id`),
  UNIQUE KEY `refund_order` (`order_id`),
  KEY `pay_id` (`pay_id`),
  CONSTRAINT `refunds_o_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `refunds_p_fk` FOREIGN KEY (`pay_id`) REFERENCES `payment` (`pay_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
