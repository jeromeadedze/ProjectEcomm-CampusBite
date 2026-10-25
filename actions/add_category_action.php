<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['cat_name'] ?? '');
    $icon = trim($_POST['cat_icon'] ?? 'bi-egg-fried');

    if (!empty($name)) {
        $prodCtrl = new ProductController();
        $prodCtrl->addCategory($name, $icon);
        $_SESSION['success'] = "Category created!";
    }
}

header("Location: ../views/admin/category.php");
exit;
?>
