<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/admin/product.php");
    exit;
}

$controller = new ProductController();
$file = $_FILES['product_image'] ?? null;
$checked = $controller->checkProductForm($_POST, $file);
$values = $checked['values'];

if ($checked['error']) {
    $_SESSION['error'] = $checked['error'];
    $_SESSION['product_form'] = $values;
    header("Location: ../views/admin/product.php");
    exit;
}

$imageName = $controller->saveProductImage($file);

if ($controller->addProduct((int)$values['product_cat'], (int)$values['product_brand'], $values['product_title'], (float)$values['product_price'], $values['product_desc'], $imageName, $values['product_keywords'], (int)$values['prep_time'], $values['is_available'])) {
    $_SESSION['success'] = "{$values['product_title']} was added to the menu.";
} else {
    $controller->deleteProductImage($imageName);
    $_SESSION['error'] = "The meal couldn't be saved. Try again.";
    $_SESSION['product_form'] = $values;
}

header("Location: ../views/admin/product.php");
exit;
?>
