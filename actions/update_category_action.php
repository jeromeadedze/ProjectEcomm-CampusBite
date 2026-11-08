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
$id = (int)($_POST['cat_id'] ?? 0);

if (!$controller->getCategoryById($id)) {
    $_SESSION['error'] = "That category no longer exists.";
    header("Location: ../views/admin/category.php");
    exit;
}

$checked = $controller->checkCategoryForm($_POST, $id);
$values = $checked['values'];

if ($checked['error']) {
    $_SESSION['error'] = $checked['error'];
    $_SESSION['category_form'] = $values;
    header("Location: ../views/admin/category.php?edit_id=" . $id);
    exit;
}

if ($controller->updateCategory($id, $values['cat_name'], $values['cat_icon'])) {
    $_SESSION['success'] = "{$values['cat_name']} was updated.";
} else {
    $_SESSION['error'] = "The category couldn't be saved. Try again.";
}

header("Location: ../views/admin/category.php");
exit;
?>
