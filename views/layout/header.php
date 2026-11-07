<?php
require_once __DIR__ . '/../../core/core.php';

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
];
$pageTitle = $pageTitles[$currentPage] ?? 'CampusBite';
// A page can set $pageHeading before including this file to show a different heading than its title
$pageHeadingText = $pageHeading ?? $pageTitle;

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
    <div class="cb" id="cb-app">
        <?php require __DIR__ . '/flash.php'; ?>
        <?php require __DIR__ . '/sidebar.php'; ?>
        <main class="cb-main" id="MainContent">
            <div class="cb-top cb-page-title">
                <h1><?php echo htmlspecialchars($pageHeadingText); ?></h1>
                <form class="cb-search" action="<?php echo $ROOT_DIR; ?>/views/search_results.php" method="GET" role="search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="search" name="q" placeholder="Search meals or kitchens" aria-label="Search meals or kitchens" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                </form>
            </div>
