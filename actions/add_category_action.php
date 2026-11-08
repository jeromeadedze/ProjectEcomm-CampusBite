<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/admin/category.php");
    exit;
}
require_csrf();

$controller = new ProductController();
$checked = $controller->checkCategoryForm($_POST);
$values = $checked['values'];

if ($checked['error']) {
    $_SESSION['error'] = $checked['error'];
    $_SESSION['category_form'] = $values;
} elseif ($controller->addCategory($values['cat_name'], $values['cat_icon'])) {
    $_SESSION['success'] = "{$values['cat_name']} was added.";
} else {
    $_SESSION['error'] = "The category couldn't be saved. Try again.";
    $_SESSION['category_form'] = $values;
}

header("Location: ../views/admin/category.php");
exit;
?>
