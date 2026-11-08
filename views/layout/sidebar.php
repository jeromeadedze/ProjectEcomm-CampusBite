<?php
/**
 * Left navigation, translated from the theme's cb-sidebar snippet.
 * Favourites, Help and Settings show for signed-in users; Bills is hidden from admins.
 */
require_once __DIR__ . '/../../controllers/ProductController.php';

$sidebarController = new ProductController();
$sidebarKitchens = $sidebarController->getAllBrands();
$currentBrandId = $currentPage === 'menu' ? (int)($_GET['brand'] ?? 0) : 0;

$navLink = function ($pages) use ($currentPage) {
    return in_array($currentPage, $pages, true) ? 'class="is-active" aria-current="page"' : '';
};
$adminPages = ['brand', 'category', 'product', 'orders', 'customers', 'messages'];
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
        <?php if (is_logged_in() && !is_admin()): ?>
        <a href="<?php echo $ROOT_DIR; ?>/views/favorites.php" <?php echo $navLink(['favorites']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/></svg>Favourites
        </a>
        <?php endif; ?>
        <?php if (!is_admin()): ?>
        <a href="<?php echo $ROOT_DIR; ?>/views/cart.php" <?php echo $navLink(['cart', 'checkout']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/></svg>Bills
        </a>
        <?php endif; ?>
        <?php if (is_logged_in() && !is_admin()): ?>
        <a href="<?php echo $ROOT_DIR; ?>/views/contact.php" <?php echo $navLink(['contact']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v11H8l-4 4z"/><path d="M8 9h8M8 12h5"/></svg>Help
        </a>
        <?php endif; ?>
        <?php if (is_logged_in()): ?>
        <a href="<?php echo $ROOT_DIR; ?>/views/settings.php" <?php echo $navLink(['settings']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg>Settings
        </a>
        <?php endif; ?>
        <?php if (is_admin()): ?>
            <a href="<?php echo $ROOT_DIR; ?>/views/admin/product.php" <?php echo $navLink($adminPages); ?>>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7z"/><path d="M9 12l2 2 4-4"/></svg>Admin
            </a>
        <?php endif; ?>
    </nav>

    <p class="cb-side-label">Kitchens</p>
    <div class="cb-side-kitchens">
        <?php // $sideKitchen, not $kitchen: this file runs inside each page, and menu.php uses $kitchen for the chosen kitchen ?>
        <?php foreach ($sidebarKitchens as $sideKitchen): ?>
            <?php $isCurrent = $currentBrandId === (int)$sideKitchen['brand_id']; ?>
            <a href="<?php echo $ROOT_DIR; ?>/views/menu.php?brand=<?php echo (int)$sideKitchen['brand_id']; ?>" <?php echo $isCurrent ? 'class="is-active" aria-current="page"' : ''; ?>>
                <?php echo htmlspecialchars($sideKitchen['brand_name']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!is_logged_in()): ?>
        <div class="cb-upgrade">
            <p>Create an account to order from every campus kitchen</p>
            <a href="<?php echo $ROOT_DIR; ?>/views/register.php">Sign up</a>
        </div>
    <?php elseif (PASS_ENABLED): ?>
        <div class="cb-upgrade">
            <p>Get free deliveries with the CampusBite Pass</p>
            <a href="<?php echo $ROOT_DIR; ?>/views/pass.php">See the Pass</a>
        </div>
    <?php endif; ?>
</aside>
