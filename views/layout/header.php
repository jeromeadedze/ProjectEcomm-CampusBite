<?php
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/CartController.php';

$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';

$cartCtrl = new CartController();
$cartIp = get_ip();
$totalItems = $cartCtrl->getCartCount($cartIp, get_user_id());
$totalPrice = $cartCtrl->getCartTotal($cartIp, get_user_id());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CampusBite — Ashesi & Berekuso Campus Food Ordering</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- CampusBite Custom CSS -->
    <link rel="stylesheet" href="<?php echo $ROOT_DIR; ?>/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- Header Navigation -->
    <header class="cb-header">
        <div class="cb-navbar">
            <!-- Brand Logo -->
            <a href="<?php echo $ROOT_DIR; ?>/index.php" class="cb-brand">
                <i class="bi bi-fire"></i>
                <span>Campus<span class="cb-brand-accent">Bite</span></span>
            </a>

            <!-- Search Form -->
            <form action="<?php echo $ROOT_DIR; ?>/views/search_results.php" method="GET" class="cb-search-box">
                <i class="bi bi-search cb-search-icon"></i>
                <input type="search" name="q" class="cb-search-input" placeholder="Search Hallmark Jollof, Akonnor Banku, Munchees, Peckish..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" required>
            </form>

            <!-- Actions -->
            <nav class="cb-nav-actions">
                <!-- Cart Pill -->
                <a href="<?php echo $ROOT_DIR; ?>/views/cart.php" class="cb-cart-pill" title="View Cart">
                    <i class="bi bi-bag-check-fill"></i>
                    <span>GH₵ <?php echo number_format($totalPrice, 2); ?></span>
                    <span class="cb-badge"><?php echo $totalItems; ?></span>
                </a>

                <?php if (is_logged_in()): ?>
                    <a href="<?php echo $ROOT_DIR; ?>/views/my_orders.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-receipt"></i> Orders
                    </a>

                    <?php if (is_admin()): ?>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-dark rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-shield-lock-fill"></i> Admin
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><a class="dropdown-item" href="<?php echo $ROOT_DIR; ?>/views/admin/product.php"><i class="bi bi-egg-fried me-2"></i>Manage Meals</a></li>
                                <li><a class="dropdown-item" href="<?php echo $ROOT_DIR; ?>/views/admin/brand.php"><i class="bi bi-shop me-2"></i>Manage Kitchens</a></li>
                                <li><a class="dropdown-item" href="<?php echo $ROOT_DIR; ?>/views/admin/category.php"><i class="bi bi-grid me-2"></i>Categories</a></li>
                                <li><a class="dropdown-item" href="<?php echo $ROOT_DIR; ?>/views/admin/orders.php"><i class="bi bi-clipboard2-check me-2"></i>All Orders</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo $ROOT_DIR; ?>/views/customers.php"><i class="bi bi-people me-2"></i>Registered Users</a></li>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <a href="<?php echo $ROOT_DIR; ?>/logout.php" class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Log Out">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                <?php else: ?>
                    <a href="<?php echo $ROOT_DIR; ?>/views/login.php" class="btn btn-sm btn-outline-dark rounded-pill px-3">
                        Sign In
                    </a>
                    <a href="<?php echo $ROOT_DIR; ?>/views/register.php" class="cb-btn-primary btn btn-sm">
                        Sign Up
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container my-4">
        <!-- Flash messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
