<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $prodCtrl = new ProductController();
    $prodCtrl->deleteCategory($id);
    $_SESSION['success'] = "Category removed.";
}

header("Location: ../views/admin/category.php");
exit;
?>
