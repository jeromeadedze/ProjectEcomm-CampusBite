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

$file = $_FILES['product_image'] ?? null;
$checked = $controller->checkProductForm($_POST, $file, $id);
$values = $checked['values'];

if ($checked['error']) {
    $_SESSION['error'] = $checked['error'];
    $_SESSION['product_form'] = $values;
    header("Location: ../views/admin/product.php?edit_id=" . $id);
    exit;
}

$newImage = $controller->saveProductImage($file);

if ($controller->updateProduct($id, (int)$values['product_cat'], (int)$values['product_brand'], $values['product_title'], (float)$values['product_price'], $values['product_desc'], $newImage, $values['product_keywords'], (int)$values['prep_time'], $values['is_available'])) {
    if ($newImage) {
        $controller->deleteProductImage($product['product_image']);
    }
    $_SESSION['success'] = "{$values['product_title']} was updated.";
    header("Location: ../views/admin/product.php");
} else {
    $controller->deleteProductImage($newImage);
    $_SESSION['error'] = "The meal couldn't be saved. Try again.";
    $_SESSION['product_form'] = $values;
    header("Location: ../views/admin/product.php?edit_id=" . $id);
}
exit;
?>
