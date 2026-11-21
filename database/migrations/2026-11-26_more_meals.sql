-- Sprint 24: more meals for every kitchen (shawarma, croissants, cakes, soft drinks, smoothies) and three new
-- categories. Matches kitchens and categories by name and skips anything already there, so it is safe to run twice.
-- The photos are in images/products/ (credits in images/products/CREDITS.md).

UPDATE `categories` SET `cat_name` = 'Grills & Suya' WHERE `cat_name` = 'Grills & Shawarma';

INSERT INTO `categories` (`cat_name`, `cat_icon`) SELECT 'Shawarma & Wraps', 'bi-basket' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `cat_name` = 'Shawarma & Wraps');
INSERT INTO `categories` (`cat_name`, `cat_icon`) SELECT 'Desserts & Cakes', 'bi-cake2' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `cat_name` = 'Desserts & Cakes');
INSERT INTO `categories` (`cat_name`, `cat_icon`) SELECT 'Soft Drinks', 'bi-cup' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `cat_name` = 'Soft Drinks');

-- The beef shawarma wrap moves to the new Shawarma & Wraps category
UPDATE `products` SET `product_cat` = (SELECT `cat_id` FROM `categories` WHERE `cat_name` = 'Shawarma & Wraps')
WHERE `product_title` = 'Munchees Beef Shawarma Wrap (Extra Cheese)';

INSERT INTO `products` (`product_cat`, `product_brand`, `product_title`, `product_price`, `product_desc`, `product_image`, `product_keywords`, `is_available`, `prep_time_minutes`)
SELECT c.cat_id, b.brand_id, 'Hallmark Classic Chicken Shawarma', 28.00, 'Grilled chicken strips, garlic sauce, pickles, fries and lettuce rolled in toasted pita bread.', 'chicken_shawarma.jpg', 'shawarma, chicken, hallmark, wrap', 1, 10 FROM categories c, brands b WHERE c.cat_name = 'Shawarma & Wraps' AND b.brand_name = 'Hallmark Cafeteria'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Hallmark Classic Chicken Shawarma')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Hallmark Butter Croissant', 12.00, 'Flaky all-butter croissant baked fresh every morning and served warm.', 'butter_croissant.jpg', 'croissant, pastry, breakfast, hallmark', 1, 5 FROM categories c, brands b WHERE c.cat_name = 'Pastries & Quick Bites' AND b.brand_name = 'Hallmark Cafeteria'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Hallmark Butter Croissant')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Hallmark Chocolate Fudge Cake Slice', 18.00, 'Layered chocolate sponge with rich fudge frosting.', 'choc_cake.jpg', 'cake, chocolate, dessert, hallmark', 1, 5 FROM categories c, brands b WHERE c.cat_name = 'Desserts & Cakes' AND b.brand_name = 'Hallmark Cafeteria'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Hallmark Chocolate Fudge Cake Slice')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Coca-Cola Glass Bottle (300ml)', 8.00, 'Ice-cold Coke in the classic glass bottle, with a glass of ice on the side.', 'coke_bottle.jpg', 'coke, coca-cola, soda, drink', 1, 2 FROM categories c, brands b WHERE c.cat_name = 'Soft Drinks' AND b.brand_name = 'Hallmark Cafeteria'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Coca-Cola Glass Bottle (300ml)')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Hallmark Mango Smoothie', 18.00, 'Ripe mango blended with yoghurt and a touch of honey.', 'mango_smoothie.jpg', 'smoothie, mango, drink, hallmark', 1, 5 FROM categories c, brands b WHERE c.cat_name = 'Drinks & Fresh Smoothies' AND b.brand_name = 'Hallmark Cafeteria'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Hallmark Mango Smoothie')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Akonnor Waakye Special', 30.00, 'Rice and beans cooked with sorghum leaves, with fried plantain, boiled egg, gari, shito and stew.', 'waakye.jpg', 'waakye, beans, local, akonnor', 1, 15 FROM categories c, brands b WHERE c.cat_name = 'Local Staples (Fufu & Banku)' AND b.brand_name = 'Akonnor Cafeteria'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Akonnor Waakye Special')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Akonnor Spicy Beef Shawarma with Shito', 30.00, 'Peppered beef, crunchy cabbage and shito mayo in a pita, toasted until crisp.', 'beef_shawarma_spicy.jpg', 'shawarma, beef, spicy, shito, akonnor', 1, 12 FROM categories c, brands b WHERE c.cat_name = 'Shawarma & Wraps' AND b.brand_name = 'Akonnor Cafeteria'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Akonnor Spicy Beef Shawarma with Shito')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Akonnor Sobolo (Hibiscus Ginger)', 10.00, 'Chilled hibiscus drink brewed with ginger, cloves and pineapple peel.', 'sobolo.jpg', 'sobolo, hibiscus, drink, local', 1, 2 FROM categories c, brands b WHERE c.cat_name = 'Drinks & Fresh Smoothies' AND b.brand_name = 'Akonnor Cafeteria'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Akonnor Sobolo (Hibiscus Ginger)')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Akonnor Coconut Cake Slice', 15.00, 'Moist coconut sponge with a coconut cream topping.', 'coconut_cake.jpg', 'cake, coconut, dessert, akonnor', 1, 5 FROM categories c, brands b WHERE c.cat_name = 'Desserts & Cakes' AND b.brand_name = 'Akonnor Cafeteria'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Akonnor Coconut Cake Slice')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Fanta Orange Can (330ml)', 8.00, 'A chilled can of Fanta Orange.', 'fanta.jpg', 'fanta, soda, orange, drink', 1, 2 FROM categories c, brands b WHERE c.cat_name = 'Soft Drinks' AND b.brand_name = 'Akonnor Cafeteria'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Fanta Orange Can (330ml)')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Munchees Mixed Shawarma Platter', 42.00, 'Chicken and beef shawarma meat over fries with garlic sauce, chilli and salad. No wrap.', 'shawarma_platter.jpg', 'shawarma, platter, fries, munchees', 1, 15 FROM categories c, brands b WHERE c.cat_name = 'Shawarma & Wraps' AND b.brand_name = 'Munchees Grills'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Munchees Mixed Shawarma Platter')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Munchees Peri-Peri Chicken Wings (8 pcs)', 35.00, 'Crispy wings tossed in hot peri-peri sauce, with a ranch dip.', 'chicken_wings.jpg', 'wings, chicken, grill, peri-peri, munchees', 1, 15 FROM categories c, brands b WHERE c.cat_name = 'Grills & Suya' AND b.brand_name = 'Munchees Grills'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Munchees Peri-Peri Chicken Wings (8 pcs)')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Coca-Cola Can (330ml)', 8.00, 'An ice-cold can of Coca-Cola.', 'coke_can.jpg', 'coke, coca-cola, soda, drink', 1, 2 FROM categories c, brands b WHERE c.cat_name = 'Soft Drinks' AND b.brand_name = 'Munchees Grills'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Coca-Cola Can (330ml)')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Munchees Oreo Milkshake', 22.00, 'Vanilla ice cream blended with Oreo cookies and milk, topped with cream.', 'oreo_shake.jpg', 'milkshake, oreo, shake, munchees', 1, 5 FROM categories c, brands b WHERE c.cat_name = 'Drinks & Fresh Smoothies' AND b.brand_name = 'Munchees Grills'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Munchees Oreo Milkshake')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Munchees Red Velvet Cupcake', 12.00, 'Red velvet cupcake with cream cheese frosting.', 'red_velvet.jpg', 'cupcake, red velvet, dessert, munchees', 1, 3 FROM categories c, brands b WHERE c.cat_name = 'Desserts & Cakes' AND b.brand_name = 'Munchees Grills'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Munchees Red Velvet Cupcake')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Peckish Spicy Kelewele', 18.00, 'Ripe plantain cubes fried with ginger, chilli and spices, served with roasted groundnuts.', 'kelewele.jpg', 'kelewele, plantain, local, peckish', 1, 10 FROM categories c, brands b WHERE c.cat_name = 'Local Staples (Fufu & Banku)' AND b.brand_name = 'Peckish Berekuso'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Peckish Spicy Kelewele')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Peckish Sausage & Cheese Croissant', 20.00, 'Warm croissant filled with grilled sausage, melted cheese, tomato and lettuce.', 'sausage_croissant.jpg', 'croissant, sausage, breakfast, peckish', 1, 8 FROM categories c, brands b WHERE c.cat_name = 'Pastries & Quick Bites' AND b.brand_name = 'Peckish Berekuso'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Peckish Sausage & Cheese Croissant')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Peckish Banana Bread Slice', 12.00, 'Homemade banana bread with walnuts, baked daily.', 'banana_bread.jpg', 'banana bread, cake, dessert, peckish', 1, 3 FROM categories c, brands b WHERE c.cat_name = 'Desserts & Cakes' AND b.brand_name = 'Peckish Berekuso'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Peckish Banana Bread Slice')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Sprite Can (330ml)', 8.00, 'A chilled can of Sprite.', 'sprite.jpg', 'sprite, soda, lemon, drink', 1, 2 FROM categories c, brands b WHERE c.cat_name = 'Soft Drinks' AND b.brand_name = 'Peckish Berekuso'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Sprite Can (330ml)')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Peckish Shawarma Fries', 28.00, 'Crispy fries loaded with chicken shawarma, cheese sauce and spring onion.', 'shawarma_fries.jpg', 'shawarma, fries, chicken, peckish', 1, 12 FROM categories c, brands b WHERE c.cat_name = 'Shawarma & Wraps' AND b.brand_name = 'Peckish Berekuso'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Peckish Shawarma Fries')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Eat Healthy Grilled Chicken Wholewheat Wrap', 30.00, 'Grilled chicken breast, hummus, spinach and cucumber in a wholewheat wrap.', 'chicken_wrap.jpg', 'wrap, chicken, healthy, eat healthy', 1, 10 FROM categories c, brands b WHERE c.cat_name = 'Shawarma & Wraps' AND b.brand_name = 'Eat Healthy Spot'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Eat Healthy Grilled Chicken Wholewheat Wrap')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Eat Healthy Berry Protein Smoothie', 25.00, 'Mixed berries, banana, oats, Greek yoghurt and a scoop of protein.', 'berry_smoothie.jpg', 'smoothie, berry, protein, healthy', 1, 5 FROM categories c, brands b WHERE c.cat_name = 'Drinks & Fresh Smoothies' AND b.brand_name = 'Eat Healthy Spot'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Eat Healthy Berry Protein Smoothie')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Eat Healthy Green Detox Smoothie', 22.00, 'Spinach, cucumber, green apple, kiwi, ginger and lemon. No added sugar.', 'green_smoothie.jpg', 'smoothie, green, detox, healthy', 1, 5 FROM categories c, brands b WHERE c.cat_name = 'Drinks & Fresh Smoothies' AND b.brand_name = 'Eat Healthy Spot'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Eat Healthy Green Detox Smoothie')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Eat Healthy Greek Yoghurt Parfait', 20.00, 'Greek yoghurt layered with granola, strawberries and blueberries.', 'parfait.jpg', 'parfait, yoghurt, dessert, healthy', 1, 3 FROM categories c, brands b WHERE c.cat_name = 'Desserts & Cakes' AND b.brand_name = 'Eat Healthy Spot'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Eat Healthy Greek Yoghurt Parfait')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Eat Healthy Quinoa Power Bowl', 35.00, 'Quinoa with roasted vegetables, chickpeas, greens and a lemon tahini dressing.', 'quinoa_bowl.jpg', 'quinoa, bowl, vegan, healthy', 1, 12 FROM categories c, brands b WHERE c.cat_name = 'Healthy Bowls & Salads' AND b.brand_name = 'Eat Healthy Spot'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Eat Healthy Quinoa Power Bowl')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Hi5 Lamb Shawarma Platter', 48.00, 'Spiced lamb shawarma with rice, salad, flatbread and garlic sauce.', 'lamb_platter.jpg', 'shawarma, lamb, platter, hi5', 1, 18 FROM categories c, brands b WHERE c.cat_name = 'Shawarma & Wraps' AND b.brand_name = 'Hi5 Lounge & Kitchen'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Hi5 Lamb Shawarma Platter')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Hi5 Chocolate Croissant', 15.00, 'Buttery pain au chocolat with dark chocolate inside.', 'choc_croissant.jpg', 'croissant, chocolate, pastry, hi5', 1, 3 FROM categories c, brands b WHERE c.cat_name = 'Pastries & Quick Bites' AND b.brand_name = 'Hi5 Lounge & Kitchen'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Hi5 Chocolate Croissant')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Hi5 Strawberry Cheesecake Slice', 25.00, 'Creamy baked cheesecake with strawberry glaze.', 'cheesecake.jpg', 'cheesecake, strawberry, dessert, hi5', 1, 3 FROM categories c, brands b WHERE c.cat_name = 'Desserts & Cakes' AND b.brand_name = 'Hi5 Lounge & Kitchen'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Hi5 Strawberry Cheesecake Slice')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Hi5 Coke Float', 18.00, 'Coca-Cola poured over two scoops of vanilla ice cream.', 'coke_float.jpg', 'coke float, ice cream, soda, hi5', 1, 5 FROM categories c, brands b WHERE c.cat_name = 'Soft Drinks' AND b.brand_name = 'Hi5 Lounge & Kitchen'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Hi5 Coke Float')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Hi5 Tropical Passion Smoothie', 22.00, 'Passion fruit, mango and pineapple blended with ice.', 'tropical_smoothie.jpg', 'smoothie, passion fruit, tropical, hi5', 1, 5 FROM categories c, brands b WHERE c.cat_name = 'Drinks & Fresh Smoothies' AND b.brand_name = 'Hi5 Lounge & Kitchen'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Hi5 Tropical Passion Smoothie')
UNION ALL
SELECT c.cat_id, b.brand_id, 'Hi5 Jollof with Chicken Kebabs', 40.00, 'Party-style jollof rice topped with grilled chicken and pepper kebabs.', 'jollof_kebab.jpg', 'jollof, kebab, chicken, hi5', 1, 18 FROM categories c, brands b WHERE c.cat_name = 'Rice & Jollof Bowls' AND b.brand_name = 'Hi5 Lounge & Kitchen'
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.product_brand = b.brand_id AND p.product_title = 'Hi5 Jollof with Chicken Kebabs');
