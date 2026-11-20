<?php
/**
 * Top bar: logo, pill navigation, search, cart and account.
 * Three sets of links: shoppers get the ordering pages, admins the admin console, kitchen accounts their kitchen portal.
 * On phones the shopper links become a tab bar fixed to the bottom of the screen; staff links scroll sideways under the bar.
 * Needs $currentPage, $ROOT_DIR and $panelCount from header.php.
 */
$icons = [
    'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9v11h14V9"/><path d="M10 20v-6h4v6"/>',
    'food' => '<path d="M7 3v8M5 3v5a2 2 0 0 0 4 0V3M7 11v10"/><path d="M17 21V3c-2.5 1.5-3.5 4-3.5 7 0 2 1 3 3.5 3"/>',
    'orders' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/>',
    'heart' => '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/>',
    'help' => '<path d="M4 5h16v11H8l-4 4z"/><path d="M8 9h8M8 12h5"/>',
    'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>',
    'grid' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
    'money' => '<rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9.5v5M18 9.5v5"/>',
    'shop' => '<path d="M4 10h16l-1.5-5h-13z"/><path d="M5 10v10h14V10"/><path d="M10 20v-5h4v5"/>',
    'tiles' => '<path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/>',
    'people' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c1-3.5 3.5-5.5 6.5-5.5s5.5 2 6.5 5.5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14.5c2 .7 3.2 2.5 3.8 5.5"/>',
    'bag' => '<path d="M5 8h14l-1 12H6z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
    'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
];
$icon = function ($name) use ($icons) {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true">' . $icons[$name] . '</svg>';
};

// Each link: [path, pages that light it up, label, icon, phone-only]
if (is_kitchen()) {
    $navLabel = 'Kitchen';
    $navLinks = [
        ['/views/kitchen/kitchen_dashboard.php', ['kitchen_dashboard'], 'Dashboard', 'grid'],
        ['/views/kitchen/kitchen_orders.php', ['kitchen_orders', 'order_tracking'], 'Orders', 'orders'],
        ['/views/kitchen/kitchen_menu.php', ['kitchen_menu'], 'Menu', 'food'],
        ['/views/kitchen/kitchen_messages.php', ['kitchen_messages'], 'Messages', 'help'],
        ['/views/kitchen/kitchen_info.php', ['kitchen_info'], 'Kitchen info', 'shop'],
    ];
} elseif (is_admin()) {
    $navLabel = 'Admin';
    $navLinks = [
        ['/views/admin/admin_dashboard.php', ['admin_dashboard'], 'Dashboard', 'grid'],
        ['/views/admin/orders.php', ['orders', 'order_tracking'], 'Orders', 'orders'],
        ['/views/admin/money.php', ['money'], 'Money', 'money'],
        ['/views/admin/brand.php', ['brand'], 'Kitchens', 'shop'],
        ['/views/admin/product.php', ['product'], 'Menu items', 'food'],
        ['/views/admin/category.php', ['category'], 'Categories', 'tiles'],
        ['/views/customers.php', ['customers'], 'Customers', 'people'],
        ['/views/admin/messages.php', ['messages'], 'Support', 'help'],
        ['/views/menu.php', ['menu', 'single_product', 'search_results'], 'View shop', 'home'],
    ];
} else {
    $navLabel = 'Main';
    $navLinks = [
        ['/index.php', ['index', 'home'], 'Home', 'home'],
        ['/views/menu.php', ['menu', 'single_product', 'search_results'], 'Food Order', 'food'],
    ];
    if (is_logged_in() && can_order()) {
        $navLinks[] = ['/views/my_orders.php', ['my_orders', 'order_tracking'], 'Orders', 'orders'];
        $navLinks[] = ['/views/favorites.php', ['favorites'], 'Favourites', 'heart'];
        $navLinks[] = ['/views/contact.php', ['contact'], 'Help', 'help'];
        $navLinks[] = ['/views/settings.php', ['settings'], 'Account', 'user', true];
    } else {
        $navLinks[] = ['/views/cart.php', ['cart', 'checkout'], 'Bills', 'bag', true];
        $navLinks[] = ['/views/login.php', ['login', 'register'], 'Sign in', 'user', true];
    }
}
$isShopperNav = !is_admin() && !is_kitchen();
?>
<header class="cb-bar">
    <a href="<?php echo $ROOT_DIR . home_path_for_role(); ?>" class="cb-logo">Campus<span>Bite</span></a>

    <nav class="cb-nav<?php echo $isShopperNav ? ' cb-nav-tabs' : ''; ?>" aria-label="<?php echo $navLabel; ?>">
        <?php foreach ($navLinks as $link): ?>
            <?php $active = in_array($currentPage, $link[1], true); ?>
            <a href="<?php echo $ROOT_DIR . $link[0]; ?>" class="<?php echo trim(($active ? 'is-active' : '') . (!empty($link[4]) ? ' cb-nav-phone' : '')); ?>" <?php echo $active ? 'aria-current="page"' : ''; ?>>
                <?php echo $icon($link[3]); ?><span><?php echo $link[2]; ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="cb-bar-actions">
        <?php if (can_order()): ?>
            <form class="cb-search" action="<?php echo $ROOT_DIR; ?>/views/search_results.php" method="GET" role="search">
                <?php echo $icon('search'); ?>
                <input type="search" name="q" placeholder="Search meals or kitchens" aria-label="Search meals or kitchens" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
            </form>
            <a class="cb-cart-pill" href="<?php echo $ROOT_DIR; ?>/views/cart.php" aria-label="Cart, <?php echo $panelCount; ?> items">
                <?php echo $icon('bag'); ?><span class="cb-cart-label">Cart</span>
                <?php if ($panelCount > 0): ?><span class="cb-cart-count"><?php echo $panelCount; ?></span><?php endif; ?>
            </a>
        <?php endif; ?>
        <?php if (is_logged_in()): ?>
            <a class="cb-avatar" href="<?php echo $ROOT_DIR; ?>/views/settings.php" aria-label="Account settings" title="Account settings"><?php echo htmlspecialchars(strtoupper(substr($_SESSION['customer_name'] ?? 'C', 0, 1))); ?></a>
        <?php else: ?>
            <a class="cb-signin" href="<?php echo $ROOT_DIR; ?>/views/login.php">Sign in</a>
        <?php endif; ?>
    </div>
</header>
