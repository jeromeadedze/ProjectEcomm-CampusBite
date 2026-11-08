<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/FavoriteController.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/favorites.php");
    exit;
}
require_csrf();

if (is_admin()) {
    header("Location: ../views/admin/product.php");
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$product = (new ProductController())->getProductById($productId);

if (!$product) {
    $_SESSION['error'] = "That meal no longer exists.";
} else {
    $saved = (new FavoriteController())->toggleFavorite(get_user_id(), $productId);
    $_SESSION['success'] = $saved
        ? "{$product['product_title']} saved to your favourites."
        : "{$product['product_title']} removed from your favourites.";
}

redirect_back('../views/favorites.php');
?>
