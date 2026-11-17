<?php
// Flips one of the kitchen's own meals between available and sold out
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_kitchen();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/kitchen/kitchen_menu.php");
    exit;
}
require_csrf();

$controller = new ProductController();
$meal = $controller->getProductById((int)($_POST['product_id'] ?? 0));

if (!$meal || (int)$meal['product_brand'] !== get_kitchen_id()) {
    $_SESSION['error'] = "That meal isn't on your menu.";
} else {
    $nowAvailable = (int)$meal['is_available'] === 1 ? 0 : 1;
    $controller->updateProduct($meal['product_id'], $meal['product_cat'], $meal['product_brand'], $meal['product_title'], $meal['product_price'], $meal['product_desc'], null, $meal['product_keywords'] ?? '', $meal['prep_time_minutes'], $nowAvailable);
    $_SESSION['success'] = $meal['product_title'] . ($nowAvailable ? " is back in stock." : " is marked sold out.");
}

header("Location: ../views/kitchen/kitchen_menu.php");
exit;
?>
