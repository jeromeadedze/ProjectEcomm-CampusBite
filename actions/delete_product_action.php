<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/admin/product.php");
    exit;
}
require_csrf();

$controller = new ProductController();
$id = (int)($_POST['product_id'] ?? 0);
$product = $controller->getProductById($id);

if (!$product) {
    $_SESSION['error'] = "That meal no longer exists.";
    header("Location: ../views/admin/product.php");
    exit;
}

// Past orders still point at the meal, so hide it instead of deleting it
if ($controller->countOrdersForProduct($id) > 0) {
    $_SESSION['error'] = "{$product['product_title']} appears in past orders and can't be deleted. Edit it and set it to Sold out to hide it.";
    header("Location: ../views/admin/product.php");
    exit;
}

try {
    $controller->deleteProduct($id);
    $controller->deleteProductImage($product['product_image']);
    $_SESSION['success'] = "{$product['product_title']} was deleted.";
} catch (PDOException $e) {
    $_SESSION['error'] = "{$product['product_title']} couldn't be deleted. Try again.";
}

header("Location: ../views/admin/product.php");
exit;
?>
