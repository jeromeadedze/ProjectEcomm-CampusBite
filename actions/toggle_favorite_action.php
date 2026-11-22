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

if (!can_order()) {
    header("Location: .." . staff_orders_path());
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$product = (new ProductController())->getProductById($productId);
if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
    header('Content-Type: application/json');

    $desiredState = $_POST['saved'] ?? null;

    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'This meal is no longer available.']);
        exit;
    }

    if (!in_array($desiredState, ['0', '1'], true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid favourite state.']);
        exit;
    }

    $saved = (new FavoriteController())->setFavorite(
        get_user_id(),
        $productId,
        $desiredState === '1'
    );

    echo json_encode(['saved' => $saved]);
    exit;
}

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
