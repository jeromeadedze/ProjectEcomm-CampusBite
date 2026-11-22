<?php
// Translated from the theme's campusbite-dashboard section. index.php includes this file; opened on its own, go there.
if (!defined('ROOT_DIR')) {
    header('Location: ../index.php');
    exit;
}
require_once __DIR__ . '/../controllers/ProductController.php';
require_once __DIR__ . '/../controllers/OrderController.php';

$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';

// Admins and kitchen staff start in their own dashboards
if (!can_order()) {
    header("Location: " . $ROOT_DIR . home_path_for_role());
    exit;
}

$productController = new ProductController();
$kitchens = $productController->getBrandsWithCounts();
$categories = $productController->getCategoriesWithDishes();
$popularDishes = $productController->getFeaturedProducts(6);

$recentOrders = [];
if (is_logged_in()) {
    $orderController = new OrderController();
    $recentOrders = $orderController->getRecentOrdersWithSummary(get_user_id(), 3);
}

$firstName = trim(explode(' ', trim($_SESSION['customer_name'] ?? ''))[0]);
$pageHeading = is_logged_in() ? 'Hello' . ($firstName !== '' ? ', ' . $firstName : '') : 'Welcome to CampusBite';

require_once __DIR__ . '/layout/header.php';
?>

<?php if (!is_logged_in()): ?>
    <p class="cb-sub">Browse campus kitchens. <a class="cb-link" href="<?php echo $ROOT_DIR; ?>/views/login.php">Sign in</a> or <a class="cb-link" href="<?php echo $ROOT_DIR; ?>/views/register.php">create an account</a> to order.</p>
<?php endif; ?>

<div class="cb-hero">
    <div>
        <h3>Hungry between lectures?</h3>
        <p>Order from campus kitchens and get it delivered to your hall in minutes.</p>
        <a class="cb-hero-btn" href="<?php echo $ROOT_DIR; ?>/views/menu.php">Order now</a>
    </div>
    <div class="cb-hero-media">
        <span class="cb-hero-emoji" aria-hidden="true"><i class="bi bi-egg-fried"></i></span>
    </div>
</div>

<?php if (!empty($kitchens)): ?>
    <div class="cb-row">
        <h2 id="kitchens-title">Kitchens</h2>
        <div class="cb-row-end">
            <a class="cb-viewall" href="<?php echo $ROOT_DIR; ?>/views/menu.php">All dishes ›</a>
            <?php // Arrows for mouse users on wide screens; phones swipe (js/main.js hides them when everything fits) ?>
            <button type="button" class="cb-car-btn" data-carousel-prev="kitchens" aria-label="Previous kitchens" hidden><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
            <button type="button" class="cb-car-btn" data-carousel-next="kitchens" aria-label="More kitchens" hidden><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
        </div>
    </div>
    <div class="cb-vendors cb-carousel" data-carousel="kitchens" role="region" aria-labelledby="kitchens-title" tabindex="0">
        <?php foreach ($kitchens as $kitchen): ?>
            <?php require __DIR__ . '/layout/kitchen_card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($categories)): ?>
    <div class="cb-row"><h2>Category</h2><a class="cb-viewall" href="<?php echo $ROOT_DIR; ?>/views/menu.php">View all ›</a></div>
    <div class="cb-cats">
        <?php foreach ($categories as $category): ?>
            <a class="cb-cat" href="<?php echo $ROOT_DIR; ?>/views/menu.php?cat=<?php echo (int)$category['cat_id']; ?>">
                <div class="cb-cat-icon"><i class="bi <?php echo htmlspecialchars($category['cat_icon']); ?>" aria-hidden="true"></i></div>
                <?php echo htmlspecialchars($category['cat_name']); ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="cb-row"><h2>Popular Dishes</h2><a class="cb-viewall" href="<?php echo $ROOT_DIR; ?>/views/menu.php">View all ›</a></div>
<?php if (empty($popularDishes)): ?>
    <p class="cb-empty">No dishes are on the menu yet. Check back soon.</p>
<?php else: ?>
    <div class="cb-grid">
        <?php foreach ($popularDishes as $dish): ?>
            <?php require __DIR__ . '/layout/dish_card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="cb-row"><h2>Recent Order</h2><a class="cb-viewall" href="<?php echo $ROOT_DIR; ?>/views/my_orders.php">View all ›</a></div>
<?php if (!empty($recentOrders)): ?>
    <div class="cb-grid">
        <?php foreach ($recentOrders as $order): ?>
            <?php
            $orderImage = !empty($order['first_image']) && file_exists(__DIR__ . '/../images/products/' . $order['first_image'])
                ? $ROOT_DIR . '/images/products/' . htmlspecialchars($order['first_image'])
                : null;
            $orderTotal = $order['items_total'] + $order['delivery_fee'];
            $moreItems = (int)$order['item_count'] - 1;
            ?>
            <a class="cb-dish" href="<?php echo $ROOT_DIR; ?>/views/order_tracking.php?order_id=<?php echo (int)$order['order_id']; ?>">
                <div class="cb-dish-img">
                    <?php if ($orderImage): ?>
                        <img src="<?php echo $orderImage; ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <span class="cb-dish-emoji" aria-hidden="true"><i class="bi <?php echo meal_icon($order); ?>"></i></span>
                    <?php endif; ?>
                </div>
                <span class="cb-vchip"><?php echo htmlspecialchars($order['first_kitchen'] ?? ''); ?></span>
                <p class="cb-dish-name"><?php echo htmlspecialchars($order['first_title'] ?? 'Order'); ?><?php echo $moreItems > 0 ? ' + ' . $moreItems . ' more' : ''; ?></p>
                <span class="cb-recent-meta">#<?php echo (int)$order['invoice_no']; ?> · <?php echo htmlspecialchars(date('M d', strtotime($order['order_date']))); ?> · GH₵ <?php echo number_format($orderTotal, 2); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
<?php elseif (is_logged_in()): ?>
    <p class="cb-empty">No orders yet. Your last three orders will show here.</p>
<?php else: ?>
    <p class="cb-empty"><a class="cb-link" href="<?php echo $ROOT_DIR; ?>/views/login.php">Sign in</a> to see your recent orders.</p>
<?php endif; ?>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
