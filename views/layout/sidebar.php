<?php
/**
 * Left navigation, translated from the theme's cb-sidebar snippet.
 * Food Order, Favorite, Message and Setting join the nav when their pages are built.
 */
require_once __DIR__ . '/../../controllers/ProductController.php';

$sidebarController = new ProductController();
$sidebarKitchens = $sidebarController->getAllBrands();
$currentBrandId = $currentPage === 'menu' ? (int)($_GET['brand'] ?? 0) : 0;

$navLink = function ($pages) use ($currentPage) {
    return in_array($currentPage, $pages, true) ? 'class="is-active" aria-current="page"' : '';
};
$adminPages = ['brand', 'category', 'product', 'orders', 'customers'];
?>
<aside class="cb-side">
    <a href="<?php echo $ROOT_DIR; ?>/index.php" class="cb-logo">CampusBite<span>.</span></a>
    <nav class="cb-nav" aria-label="Main">
        <a href="<?php echo $ROOT_DIR; ?>/index.php" <?php echo $navLink(['index', 'home']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9v11h14V9"/><path d="M10 20v-6h4v6"/></svg>Home
        </a>
        <a href="<?php echo $ROOT_DIR; ?>/views/menu.php" <?php echo $currentPage === 'menu' && $currentBrandId === 0 ? 'class="is-active" aria-current="page"' : ''; ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M7 3v8M5 3v5a2 2 0 0 0 4 0V3M7 11v10"/><path d="M17 21V3c-2.5 1.5-3.5 4-3.5 7 0 2 1 3 3.5 3"/></svg>Food Order
        </a>
        <a href="<?php echo $ROOT_DIR; ?>/views/my_orders.php" <?php echo $navLink(['my_orders', 'order_tracking']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>Order History
        </a>
        <a href="<?php echo $ROOT_DIR; ?>/views/cart.php" <?php echo $navLink(['cart', 'checkout']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/></svg>Bills
        </a>
        <?php if (is_admin()): ?>
            <a href="<?php echo $ROOT_DIR; ?>/views/admin/product.php" <?php echo $navLink($adminPages); ?>>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7z"/><path d="M9 12l2 2 4-4"/></svg>Admin
            </a>
        <?php endif; ?>
    </nav>

    <p class="cb-side-label">Kitchens</p>
    <div class="cb-side-kitchens">
        <?php foreach ($sidebarKitchens as $kitchen): ?>
            <?php $isCurrent = $currentBrandId === (int)$kitchen['brand_id']; ?>
            <a href="<?php echo $ROOT_DIR; ?>/views/menu.php?brand=<?php echo (int)$kitchen['brand_id']; ?>" <?php echo $isCurrent ? 'class="is-active" aria-current="page"' : ''; ?>>
                <?php echo htmlspecialchars($kitchen['brand_name']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="cb-upgrade">
        <?php if (is_logged_in()): ?>
            <p>Get free deliveries with the CampusBite Pass</p>
            <a href="<?php echo $ROOT_DIR; ?>/views/pass.php">See the Pass</a>
        <?php else: ?>
            <p>Create an account to order from every campus kitchen</p>
            <a href="<?php echo $ROOT_DIR; ?>/views/register.php">Sign up</a>
        <?php endif; ?>
    </div>
</aside>
