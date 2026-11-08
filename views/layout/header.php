<?php
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/CartController.php';
require_once __DIR__ . '/../../controllers/FavoriteController.php';

$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';

// The script being served decides the page title and the active sidebar link
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php', '.php');
$pageTitles = [
    'index' => 'Home',
    'home' => 'Home',
    'menu' => 'Food Order',
    'search_results' => 'Search',
    'single_product' => 'Food Order',
    'cart' => 'Bills',
    'checkout' => 'Checkout',
    'my_orders' => 'Order History',
    'order_tracking' => 'Order Tracking',
    'pass' => 'CampusBite Pass',
    'login' => 'Sign In',
    'register' => 'Sign Up',
    'customers' => 'Customers',
    'brand' => 'Kitchens',
    'category' => 'Categories',
    'product' => 'Menu Items',
    'orders' => 'Orders',
    'favorites' => 'Favourites',
    'settings' => 'Settings',
    'contact' => 'Help & Support',
    'messages' => 'Support Messages',
];
$pageTitle = $pageTitles[$currentPage] ?? 'CampusBite';
// A page can set $pageHeading before including this file to show a different heading than its title
$pageHeadingText = $pageHeading ?? $pageTitle;

// Cart contents for the top-bar badge and the order panel
$headerCart = new CartController();
$panelItems = is_admin() ? [] : $headerCart->getCartItems(get_cart_token(), get_user_id());
$panelCount = array_sum(array_map(function ($item) { return (int)$item['qty']; }, $panelItems));

// The order panel only appears once something is in the cart, and never where it would repeat
// the page itself (Bills, checkout) or for admins, who don't order
$showPanel = $panelCount > 0 && !is_admin() && !in_array($currentPage, ['cart', 'checkout'], true);
// Changes whenever the cart changes, so a closed panel comes back after the next add
$panelSignature = md5(json_encode(array_map(function ($item) { return [$item['p_id'], $item['qty']]; }, $panelItems)));

// Meals the shopper has saved, so every dish card can show a filled heart
$favoriteIds = (is_logged_in() && !is_admin()) ? (new FavoriteController())->getFavoriteIds(get_user_id()) : [];

// Stamp stylesheets with their edit time so a change shows without a hard refresh
$cssVersion = function ($file) {
    return filemtime(__DIR__ . '/../../css/' . $file);
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle); ?> – CampusBite</title>
    <link rel="icon" href="data:,">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Pages not yet translated from the theme still use Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo $ROOT_DIR; ?>/css/style.css?v=<?php echo $cssVersion('style.css'); ?>">
    <link rel="stylesheet" href="<?php echo $ROOT_DIR; ?>/css/campusbite.css?v=<?php echo $cssVersion('campusbite.css'); ?>">
    <link rel="stylesheet" href="<?php echo $ROOT_DIR; ?>/css/auth.css?v=<?php echo $cssVersion('auth.css'); ?>">
    <link rel="stylesheet" href="<?php echo $ROOT_DIR; ?>/css/admin.css?v=<?php echo $cssVersion('admin.css'); ?>">
</head>
<body>
    <div class="cb<?php echo $showPanel ? ' has-panel' : ''; ?>" id="cb-app" data-panel-signature="<?php echo $panelSignature; ?>">
        <?php if ($showPanel): ?>
        <script>
            // Keep the panel closed if the shopper closed it and the cart hasn't changed since
            try {
                if (localStorage.getItem('cbPanelClosed') === '<?php echo $panelSignature; ?>') {
                    document.getElementById('cb-app').classList.remove('has-panel');
                }
            } catch (e) {}
        </script>
        <?php endif; ?>
        <?php require __DIR__ . '/flash.php'; ?>
        <?php require __DIR__ . '/sidebar.php'; ?>
        <main class="cb-main" id="MainContent">
            <div class="cb-top cb-page-title">
                <h1><?php echo htmlspecialchars($pageHeadingText); ?></h1>
                <div class="cb-top-actions">
                    <form class="cb-search" action="<?php echo $ROOT_DIR; ?>/views/search_results.php" method="GET" role="search">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        <input type="search" name="q" placeholder="Search meals or kitchens" aria-label="Search meals or kitchens" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                    </form>
                    <div class="cb-icons">
                        <?php if (is_logged_in()): ?>
                            <a href="<?php echo $ROOT_DIR; ?>/views/my_orders.php" aria-label="Order updates" title="Order updates">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 16V11a6 6 0 0 1 12 0v5l2 2H4z"/><path d="M10 21h4"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if (!is_admin()): ?>
                            <a href="<?php echo $ROOT_DIR; ?>/views/cart.php" aria-label="Cart, <?php echo $panelCount; ?> items" title="Cart">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 4h2l2 12h11l2-8H6"/><circle cx="9" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/></svg>
                                <?php if ($panelCount > 0): ?><span class="cb-dot"><?php echo $panelCount; ?></span><?php endif; ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php if (is_logged_in()): ?>
                        <a class="cb-avatar" href="<?php echo $ROOT_DIR; ?>/views/settings.php" aria-label="Account settings" title="Account settings"><?php echo htmlspecialchars(strtoupper(substr($_SESSION['customer_name'] ?? 'C', 0, 1))); ?></a>
                    <?php else: ?>
                        <a class="cb-signin" href="<?php echo $ROOT_DIR; ?>/views/login.php">Sign in</a>
                    <?php endif; ?>
                </div>
            </div>
