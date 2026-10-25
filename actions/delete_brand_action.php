<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $prodCtrl = new ProductController();
    $prodCtrl->deleteBrand($id);
    $_SESSION['success'] = "Kitchen vendor removed.";
}

header("Location: ../views/admin/brand.php");
exit;
?>
