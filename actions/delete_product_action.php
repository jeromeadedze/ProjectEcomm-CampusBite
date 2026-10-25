<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $prodCtrl = new ProductController();
    $prodCtrl->deleteProduct($id);
    $_SESSION['success'] = "Meal removed successfully.";
}

header("Location: ../views/admin/product.php");
exit;
?>
