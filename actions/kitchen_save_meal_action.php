<?php
// Adds or updates a meal from the kitchen portal; the meal always belongs to the signed-in kitchen
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_kitchen();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/kitchen/kitchen_menu.php");
    exit;
}
require_csrf();

$controller = new ProductController();
$kitchenId = get_kitchen_id();
$productId = (int)($_POST['product_id'] ?? 0);
$existing = null;

if ($productId > 0) {
    $existing = $controller->getProductById($productId);
    if (!$existing || (int)$existing['product_brand'] !== $kitchenId) {
        $_SESSION['error'] = "That meal isn't on your menu.";
        header("Location: ../views/kitchen/kitchen_menu.php");
        exit;
    }
}

// The kitchen is never taken from the form
$input = $_POST;
$input['product_brand'] = (string)$kitchenId;
$file = $_FILES['product_image'] ?? null;
$checked = $controller->checkProductForm($input, $file, $productId);
$values = $checked['values'];
$back = "../views/kitchen/kitchen_menu.php" . ($existing ? "?edit_id=$productId" : '');

if ($checked['error']) {
    $_SESSION['error'] = $checked['error'];
    $_SESSION['product_form'] = $values;
    header("Location: $back");
    exit;
}

$image = $controller->saveProductImage($file);
$args = [(int)$values['product_cat'], $kitchenId, $values['product_title'], (float)$values['product_price'], $values['product_desc']];

$saved = $existing
    ? $controller->updateProduct($productId, ...array_merge($args, [$image, $values['product_keywords'], (int)$values['prep_time'], $values['is_available']]))
    : $controller->addProduct(...array_merge($args, [$image, $values['product_keywords'], (int)$values['prep_time'], $values['is_available']]));

if ($saved) {
    if ($existing && $image) {
        $controller->deleteProductImage($existing['product_image']);
    }
    $_SESSION['success'] = $existing ? "{$values['product_title']} was updated." : "{$values['product_title']} was added to your menu.";
    header("Location: ../views/kitchen/kitchen_menu.php");
} else {
    $controller->deleteProductImage($image);
    $_SESSION['error'] = "The meal couldn't be saved. Try again.";
    $_SESSION['product_form'] = $values;
    header("Location: $back");
}
exit;
?>
