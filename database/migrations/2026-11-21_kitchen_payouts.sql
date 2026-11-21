-- Sprint 16: record money CampusBite pays out to kitchens.
-- A kitchen's balance = what its delivered orders earned (meal sales - commission + delivery fees) - its payouts.
-- Run once on an existing database, after 2026-11-21_cancel_refund.sql.

CREATE TABLE `payouts` (
  `payout_id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` varchar(30) NOT NULL,            -- Mobile Money, Bank transfer, Cash
  `reference` varchar(100) DEFAULT NULL,    -- MoMo transaction ID, bank reference, receipt number
  `note` varchar(255) DEFAULT NULL,
  `paid_on` date NOT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payout_id`),
  KEY `brand_id` (`brand_id`),
  KEY `recorded_by` (`recorded_by`),
  CONSTRAINT `payouts_b_fk` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`brand_id`),
  CONSTRAINT `payouts_c_fk` FOREIGN KEY (`recorded_by`) REFERENCES `customer` (`customer_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
