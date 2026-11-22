<?php
// Kitchen portal: deletes one of the signed-in kitchen's meals. Meals that appear in past orders are kept
// (the order history points at them), so the kitchen marks those sold out instead.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_kitchen_admin();

$back = '../views/kitchen/kitchen_menu.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $back");
    exit;
}
require_csrf();

$controller = new ProductController();
$id = (int)($_POST['product_id'] ?? 0);
$product = $controller->getProductById($id);

if (!$product || (int)$product['product_brand'] !== get_kitchen_id()) {
    $_SESSION['error'] = "That meal isn't on your menu.";
    header("Location: $back");
    exit;
}

if ($controller->countOrdersForProduct($id) > 0) {
    $_SESSION['error'] = "{$product['product_title']} appears in past orders and can't be deleted. Mark it sold out to hide it from shoppers.";
    header("Location: $back");
    exit;
}

try {
    $controller->deleteProduct($id);
    $controller->deleteProductImage($product['product_image']);
    $_SESSION['success'] = "{$product['product_title']} was deleted.";
} catch (PDOException $e) {
    $_SESSION['error'] = "{$product['product_title']} couldn't be deleted. Try again.";
}

header("Location: $back");
exit;
