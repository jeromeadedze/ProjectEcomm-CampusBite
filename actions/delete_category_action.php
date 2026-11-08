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
$category = $controller->getCategoryById($id);

if (!$category) {
    $_SESSION['error'] = "That category no longer exists.";
    header("Location: ../views/admin/category.php");
    exit;
}

// The database would delete every meal in the category along with it, so refuse instead
$itemCount = $controller->countProductsByCategory($id);
if ($itemCount > 0) {
    $items = $itemCount === 1 ? '1 menu item. Move or remove it' : "$itemCount menu items. Move or remove them";
    $_SESSION['error'] = "{$category['cat_name']} still has $items first.";
    header("Location: ../views/admin/category.php");
    exit;
}

try {
    $controller->deleteCategory($id);
    $_SESSION['success'] = "{$category['cat_name']} was deleted.";
} catch (PDOException $e) {
    $_SESSION['error'] = "{$category['cat_name']} couldn't be deleted. Try again.";
}

header("Location: ../views/admin/category.php");
exit;
?>
