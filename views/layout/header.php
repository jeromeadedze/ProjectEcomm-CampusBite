<?php
require_once __DIR__ . '/../../core/core.php';

$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CampusBite — E-Commerce Template</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $ROOT_DIR; ?>/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- Minimal Header Navigation Template -->
    <header class="border-bottom bg-white py-3 sticky-top">
        <div class="container d-flex align-items-center justify-content-between">
            <a href="<?php echo $ROOT_DIR; ?>/index.php" class="navbar-brand fw-bold fs-4 text-dark m-0">
                <i class="bi bi-fire text-warning me-1"></i>Campus<span class="text-warning">Bite</span>
            </a>

            <!-- Search Slot -->
            <div class="w-50 mx-4 d-none d-md-block">
                <form action="<?php echo $ROOT_DIR; ?>/views/search_results.php" method="GET">
                    <input type="search" name="q" class="form-control rounded-pill px-3" placeholder="Search meals, kitchens, vendors...">
                </form>
            </div>

            <!-- Action Slots (Auth & Cart) -->
            <div class="d-flex align-items-center gap-2">
                <a href="<?php echo $ROOT_DIR; ?>/views/cart.php" class="btn btn-outline-dark rounded-pill px-3">
                    <i class="bi bi-cart3 me-1"></i> Cart
                </a>
                <?php if (is_logged_in()): ?>
                    <a href="<?php echo $ROOT_DIR; ?>/logout.php" class="btn btn-light rounded-pill px-3">Logout</a>
                <?php else: ?>
                    <a href="<?php echo $ROOT_DIR; ?>/views/login.php" class="btn btn-light rounded-pill px-3">Login</a>
                    <a href="<?php echo $ROOT_DIR; ?>/views/register.php" class="btn btn-dark rounded-pill px-3">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="container my-5">
