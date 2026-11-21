-- Sprint 18: replies to support messages, so customers, kitchens and admins can talk inside the site.
-- Run once on an existing database, after 2026-11-22_password_resets.sql.

CREATE TABLE `support_replies` (
  `reply_id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `author_id` int(11) DEFAULT NULL,
  `author_role` varchar(10) NOT NULL,       -- customer, kitchen, admin
  `body` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reply_id`),
  KEY `message_id` (`message_id`),
  KEY `author_id` (`author_id`),
  CONSTRAINT `support_replies_m_fk` FOREIGN KEY (`message_id`) REFERENCES `support_messages` (`message_id`) ON DELETE CASCADE,
  CONSTRAINT `support_replies_a_fk` FOREIGN KEY (`author_id`) REFERENCES `customer` (`customer_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
