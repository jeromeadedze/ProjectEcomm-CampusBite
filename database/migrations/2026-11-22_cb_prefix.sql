-- ============================================================
-- Migration: Add cb_ prefix to all CampusBite tables
-- Allows CampusBite tables to coexist in the same shared database
-- (e.g. ecommerce_2026A_jerome_adedze) alongside ecomlab without
-- table name collisions or foreign key conflicts.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

RENAME TABLE
    `customer` TO `cb_customer`,
    `brands` TO `cb_brands`,
    `categories` TO `cb_categories`,
    `products` TO `cb_products`,
    `cart` TO `cb_cart`,
    `orders` TO `cb_orders`,
    `orderdetails` TO `cb_orderdetails`,
    `payment` TO `cb_payment`,
    `refunds` TO `cb_refunds`,
    `password_resets` TO `cb_password_resets`,
    `order_tracking` TO `cb_order_tracking`,
    `favorites` TO `cb_favorites`,
    `support_messages` TO `cb_support_messages`,
    `support_replies` TO `cb_support_replies`;

SET FOREIGN_KEY_CHECKS = 1;
