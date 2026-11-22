-- ============================================================
-- Sprint 31: meal labels for the menu's filter chips (Spicy, Mild, Vegetarian, Vegan, Healthy).
-- "Quick" is not stored: it comes from prep_time_minutes (15 minutes or less).
-- Run once on an existing database, after 2026-11-30_delivery_drop_points.sql.
-- Fresh installs don't need it: database/campusbite.sql already includes it.
-- ============================================================

ALTER TABLE `cb_products`
  ADD COLUMN `labels` set('spicy','mild','vegetarian','vegan','healthy') NOT NULL DEFAULT '' AFTER `prep_time_minutes`;

-- Labels for the seed meals. Meals a kitchen added itself stay unlabelled until the kitchen ticks them.
UPDATE `cb_products` SET `labels` = 'mild' WHERE `product_title` = 'Hallmark Special Jollof + Grilled Chicken' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'spicy' WHERE `product_title` = 'Akonnor Fried Rice + Spicy Gizzard' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'mild' WHERE `product_title` = 'Munchees Beef Shawarma Wrap (Extra Cheese)' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'spicy' WHERE `product_title` = 'Akonnor Banku with Grilled Tilapia & Hot Pepper' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'mild,healthy' WHERE `product_title` = 'Eat Healthy Grilled Chicken & Avocado Salad' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegetarian' WHERE `product_title` = 'Peckish Loaded Fried Plantain & Beans (Red Red)' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'spicy' WHERE `product_title` = 'Munchees Late-Night Suya Platter' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegan,healthy' WHERE `product_title` = 'Fresh Pineapple Ginger Juice (500ml)' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'mild' WHERE `product_title` = 'Hallmark Classic Chicken Shawarma' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegetarian' WHERE `product_title` = 'Hallmark Butter Croissant' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegetarian' WHERE `product_title` = 'Hallmark Chocolate Fudge Cake Slice' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegan' WHERE `product_title` = 'Coca-Cola Glass Bottle (300ml)' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegetarian,healthy' WHERE `product_title` = 'Hallmark Mango Smoothie' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'spicy' WHERE `product_title` = 'Akonnor Spicy Beef Shawarma with Shito' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegan,healthy' WHERE `product_title` = 'Akonnor Sobolo (Hibiscus Ginger)' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegetarian' WHERE `product_title` = 'Akonnor Coconut Cake Slice' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegan' WHERE `product_title` = 'Fanta Orange Can (330ml)' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'spicy' WHERE `product_title` = 'Munchees Peri-Peri Chicken Wings (8 pcs)' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegan' WHERE `product_title` = 'Coca-Cola Can (330ml)' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegetarian' WHERE `product_title` = 'Munchees Oreo Milkshake' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegetarian' WHERE `product_title` = 'Munchees Red Velvet Cupcake' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'spicy,vegan' WHERE `product_title` = 'Peckish Spicy Kelewele' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegetarian' WHERE `product_title` = 'Peckish Banana Bread Slice' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegan' WHERE `product_title` = 'Sprite Can (330ml)' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'mild,healthy' WHERE `product_title` = 'Eat Healthy Grilled Chicken Wholewheat Wrap' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegetarian,healthy' WHERE `product_title` = 'Eat Healthy Berry Protein Smoothie' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegan,healthy' WHERE `product_title` = 'Eat Healthy Green Detox Smoothie' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegetarian,healthy' WHERE `product_title` = 'Eat Healthy Greek Yoghurt Parfait' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegan,healthy' WHERE `product_title` = 'Eat Healthy Quinoa Power Bowl' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'mild' WHERE `product_title` = 'Hi5 Lamb Shawarma Platter' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegetarian' WHERE `product_title` = 'Hi5 Chocolate Croissant' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegetarian' WHERE `product_title` = 'Hi5 Strawberry Cheesecake Slice' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegetarian' WHERE `product_title` = 'Hi5 Coke Float' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'vegan,healthy' WHERE `product_title` = 'Hi5 Tropical Passion Smoothie' AND `labels` = '';
UPDATE `cb_products` SET `labels` = 'mild' WHERE `product_title` = 'Hi5 Jollof with Chicken Kebabs' AND `labels` = '';
