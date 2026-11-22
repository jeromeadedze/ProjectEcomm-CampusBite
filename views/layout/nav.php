<?php
/**
 * Top bar: logo, pill navigation, search, cart and account.
 * Four sets of links: shoppers get the ordering pages, admins the admin console, kitchen admins sales/menu/staff,
 * and kitchen staff orders/menu/messages.
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
    'people' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c1-3.5 3.5-5.5 6.5-5.5s5.5 2 6.5 5.5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14.5c2 .7 3.2 2.5 3.8 5.5"/>',
    'bag' => '<path d="M5 8h14l-1 12H6z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
    'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
];
$icon = function ($name) use ($icons) {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true">' . $icons[$name] . '</svg>';
};

// Each link: [path, pages that light it up, label, icon, phone-only]
if (is_kitchen_admin()) {
    // The kitchen's owner or manager: sales, the menu and prices, staff logins, payouts. Orders are left to staff.
    $navLabel = 'Kitchen admin';
    $navLinks = [
        ['/views/kitchen/kitchen_sales.php', ['kitchen_sales', 'order_tracking'], 'Sales', 'money'],
        ['/views/kitchen/kitchen_menu.php', ['kitchen_menu'], 'Menu', 'food'],
        ['/views/kitchen/kitchen_staff.php', ['kitchen_staff'], 'Staff', 'people'],
        ['/views/kitchen/kitchen_customers.php', ['kitchen_customers'], 'Customers', 'user'],
        ['/views/kitchen/kitchen_info.php', ['kitchen_info'], 'Kitchen info', 'shop'],
    ];
} elseif (is_kitchen_staff()) {
    // Kitchen staff work the shift: orders, sold-out meals and customer messages. No money anywhere.
    $navLabel = 'Kitchen';
    $navLinks = [
        ['/views/kitchen/kitchen_dashboard.php', ['kitchen_dashboard'], 'Dashboard', 'grid'],
        ['/views/kitchen/kitchen_orders.php', ['kitchen_orders', 'order_tracking'], 'Orders', 'orders'],
        ['/views/kitchen/kitchen_menu.php', ['kitchen_menu'], 'Menu', 'food'],
        ['/views/kitchen/kitchen_messages.php', ['kitchen_messages'], 'Messages', 'help'],
    ];
} elseif (is_admin()) {
    $navLabel = 'Admin';
    $navLinks = [
        ['/views/admin/admin_dashboard.php', ['admin_dashboard'], 'Dashboard', 'grid'],
        ['/views/admin/brand.php', ['brand', 'kitchen', 'category'], 'Kitchens', 'shop'],
        ['/views/admin/money.php', ['money'], 'Money', 'money'],
        ['/views/admin/users.php', ['users'], 'Users', 'people'],
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

// Kitchens: how many new orders are waiting, and the newest order, so main.js can tell when another comes in
$kitchenOrders = null;
if (is_kitchen_staff()) {
    require_once __DIR__ . '/../../controllers/OrderController.php';
    $kitchenOrders = (new OrderController())->getKitchenNewOrderState(get_kitchen_id());
}
?>
<header class="cb-bar">
    <a href="<?php echo $ROOT_DIR . home_path_for_role(); ?>" class="cb-logo">Campus<span>Bite</span></a>

    <nav class="cb-nav<?php echo $isShopperNav ? ' cb-nav-tabs' : ''; ?>" aria-label="<?php echo $navLabel; ?>">
        <?php foreach ($navLinks as $link): ?>
            <?php $active = in_array($currentPage, $link[1], true); ?>
            <?php $watchOrders = $kitchenOrders && $link[0] === '/views/kitchen/kitchen_orders.php'; ?>
            <a href="<?php echo $ROOT_DIR . $link[0]; ?>" class="<?php echo trim(($active ? 'is-active' : '') . (!empty($link[4]) ? ' cb-nav-phone' : '')); ?>" <?php echo $active ? 'aria-current="page"' : ''; ?><?php if ($watchOrders): ?> data-order-watch="<?php echo $ROOT_DIR; ?>/actions/kitchen_order_poll.php" data-latest="<?php echo $kitchenOrders['latest']; ?>"<?php endif; ?>>
                <?php echo $icon($link[3]); ?><span><?php echo $link[2]; ?></span>
                <?php if ($watchOrders): ?><span class="cb-nav-badge" data-new-count <?php echo $kitchenOrders['placed'] ? '' : 'hidden'; ?>><?php echo $kitchenOrders['placed']; ?><span class="visually-hidden"> new</span></span><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php if ($kitchenOrders): ?><div class="visually-hidden" role="status" id="cb-order-live"></div><?php endif; ?>

    <div class="cb-bar-actions">
        <?php if (can_order()): ?>
            <form class="cb-search" action="<?php echo $ROOT_DIR; ?>/views/search_results.php" method="GET" role="search">
                <?php echo $icon('search'); ?>
                <input type="search" name="q" placeholder="Search meals or kitchens" aria-label="Search meals or kitchens" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
            </form>
            <a class="cb-cart-pill" href="<?php echo $ROOT_DIR; ?>/views/cart.php" aria-label="Cart, <?php echo $panelCount; ?> items" data-cart-pill>
                <?php echo $icon('bag'); ?><span class="cb-cart-label">Cart</span>
                <span class="cb-cart-count" data-cart-count <?php echo $panelCount > 0 ? '' : 'hidden'; ?>><?php echo $panelCount; ?></span>
            </a>
        <?php endif; ?>
        <?php if (is_logged_in()): ?>
            <a class="cb-avatar" href="<?php echo $ROOT_DIR; ?>/views/settings.php" aria-label="Account settings" title="Account settings"><?php echo htmlspecialchars(strtoupper(substr($_SESSION['customer_name'] ?? 'C', 0, 1))); ?></a>
        <?php else: ?>
            <a class="cb-signin" href="<?php echo $ROOT_DIR; ?>/views/login.php">Sign in</a>
        <?php endif; ?>
    </div>
</header>
