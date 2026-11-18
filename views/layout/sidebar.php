<?php
/**
 * Left navigation, translated from the theme's cb-sidebar snippet.
 * Three views: shoppers get the ordering links, admins the admin console, kitchen accounts their kitchen portal.
 */
require_once __DIR__ . '/../../controllers/ProductController.php';

$sidebarController = new ProductController();
$sidebarKitchens = $sidebarController->getAllBrands();
$currentBrandId = $currentPage === 'menu' ? (int)($_GET['brand'] ?? 0) : 0;

$navLink = function ($pages) use ($currentPage) {
    return in_array($currentPage, $pages, true) ? 'class="is-active" aria-current="page"' : '';
};
?>
<aside class="cb-side">
    <a href="<?php echo $ROOT_DIR . home_path_for_role(); ?>" class="cb-logo">CampusBite<span>.</span></a>
    <?php if (is_kitchen()): ?>
    <p class="cb-side-label">Your kitchen</p>
    <nav class="cb-nav" aria-label="Kitchen">
        <a href="<?php echo $ROOT_DIR; ?>/views/kitchen/kitchen_dashboard.php" <?php echo $navLink(['kitchen_dashboard']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>Dashboard
        </a>
        <a href="<?php echo $ROOT_DIR; ?>/views/kitchen/kitchen_orders.php" <?php echo $navLink(['kitchen_orders', 'order_tracking']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/></svg>Orders
        </a>
        <a href="<?php echo $ROOT_DIR; ?>/views/kitchen/kitchen_menu.php" <?php echo $navLink(['kitchen_menu']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><path d="M7 3v8M5 3v5a2 2 0 0 0 4 0V3M7 11v10"/><path d="M17 21V3c-2.5 1.5-3.5 4-3.5 7 0 2 1 3 3.5 3"/></svg>Menu
        </a>
        <a href="<?php echo $ROOT_DIR; ?>/views/kitchen/kitchen_info.php" <?php echo $navLink(['kitchen_info']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><path d="M4 10h16l-1.5-5h-13z"/><path d="M5 10v10h14V10"/><path d="M10 20v-5h4v5"/></svg>Kitchen info
        </a>
        <a href="<?php echo $ROOT_DIR; ?>/views/settings.php" <?php echo $navLink(['settings']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/></svg>My account
        </a>
    </nav>
    <?php elseif (is_admin()): ?>
    <p class="cb-side-label">CampusBite admin</p>
    <nav class="cb-nav" aria-label="Admin">
        <a href="<?php echo $ROOT_DIR; ?>/views/admin/admin_dashboard.php" <?php echo $navLink(['admin_dashboard']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>Dashboard
        </a>
        <a href="<?php echo $ROOT_DIR; ?>/views/admin/orders.php" <?php echo $navLink(['orders', 'order_tracking']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/></svg>Orders
        </a>
        <a href="<?php echo $ROOT_DIR; ?>/views/admin/money.php" <?php echo $navLink(['money']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9.5v5M18 9.5v5"/></svg>Money
        </a>
        <a href="<?php echo $ROOT_DIR; ?>/views/admin/brand.php" <?php echo $navLink(['brand']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><path d="M4 10h16l-1.5-5h-13z"/><path d="M5 10v10h14V10"/><path d="M10 20v-5h4v5"/></svg>Kitchens
        </a>
        <a href="<?php echo $ROOT_DIR; ?>/views/admin/product.php" <?php echo $navLink(['product']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><path d="M7 3v8M5 3v5a2 2 0 0 0 4 0V3M7 11v10"/><path d="M17 21V3c-2.5 1.5-3.5 4-3.5 7 0 2 1 3 3.5 3"/></svg>Menu items
        </a>
        <a href="<?php echo $ROOT_DIR; ?>/views/admin/category.php" <?php echo $navLink(['category']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/></svg>Categories
        </a>
        <a href="<?php echo $ROOT_DIR; ?>/views/customers.php" <?php echo $navLink(['customers']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c1-3.5 3.5-5.5 6.5-5.5s5.5 2 6.5 5.5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14.5c2 .7 3.2 2.5 3.8 5.5"/></svg>Customers
        </a>
        <a href="<?php echo $ROOT_DIR; ?>/views/admin/messages.php" <?php echo $navLink(['messages']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><path d="M4 5h16v11H8l-4 4z"/><path d="M8 9h8M8 12h5"/></svg>Support
        </a>
        <a href="<?php echo $ROOT_DIR; ?>/views/settings.php" <?php echo $navLink(['settings']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/></svg>My account
        </a>
        <a href="<?php echo $ROOT_DIR; ?>/views/menu.php" <?php echo $navLink(['menu', 'single_product', 'search_results']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9v11h14V9"/></svg>View shop
        </a>
    </nav>
    <?php else: ?>
    <nav class="cb-nav" aria-label="Main">
        <a href="<?php echo $ROOT_DIR; ?>/index.php" <?php echo $navLink(['index', 'home']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9v11h14V9"/><path d="M10 20v-6h4v6"/></svg>Home
        </a>
        <a href="<?php echo $ROOT_DIR; ?>/views/menu.php" <?php echo $currentPage === 'menu' && $currentBrandId === 0 ? 'class="is-active" aria-current="page"' : ''; ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M7 3v8M5 3v5a2 2 0 0 0 4 0V3M7 11v10"/><path d="M17 21V3c-2.5 1.5-3.5 4-3.5 7 0 2 1 3 3.5 3"/></svg>Food Order
        </a>
        <?php if (can_order()): ?>
        <a href="<?php echo $ROOT_DIR; ?>/views/my_orders.php" <?php echo $navLink(['my_orders', 'order_tracking']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>Order History
        </a>
        <?php endif; ?>
        <?php if (is_logged_in() && can_order()): ?>
        <a href="<?php echo $ROOT_DIR; ?>/views/favorites.php" <?php echo $navLink(['favorites']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/></svg>Favourites
        </a>
        <?php endif; ?>
        <?php if (can_order()): ?>
        <a href="<?php echo $ROOT_DIR; ?>/views/cart.php" <?php echo $navLink(['cart', 'checkout']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/></svg>Bills
        </a>
        <?php endif; ?>
        <?php if (is_logged_in() && can_order()): ?>
        <a href="<?php echo $ROOT_DIR; ?>/views/contact.php" <?php echo $navLink(['contact']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v11H8l-4 4z"/><path d="M8 9h8M8 12h5"/></svg>Help
        </a>
        <?php endif; ?>
        <?php if (is_logged_in()): ?>
        <a href="<?php echo $ROOT_DIR; ?>/views/settings.php" <?php echo $navLink(['settings']); ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg>Settings
        </a>
        <?php endif; ?>
    </nav>

    <?php if (can_order()): ?>
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
    <?php endif; ?>
    <?php endif; ?>

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
