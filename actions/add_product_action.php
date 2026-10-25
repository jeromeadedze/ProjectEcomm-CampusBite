<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cat = (int)$_POST['product_cat'];
    $brand = (int)$_POST['product_brand'];
    $title = trim($_POST['product_title'] ?? '');
    $price = (float)($_POST['product_price'] ?? 0);
    $desc = trim($_POST['product_desc'] ?? '');
    $keywords = trim($_POST['product_keywords'] ?? '');
    $prep = (int)($_POST['prep_time'] ?? 15);

    $imageName = null;
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $imageName = uniqid() . '.' . $ext;
        $target = __DIR__ . '/../images/products/' . $imageName;
        move_uploaded_file($_FILES['product_image']['tmp_name'], $target);
    }

    $prodCtrl = new ProductController();
    $prodCtrl->addProduct($cat, $brand, $title, $price, $desc, $imageName, $keywords, $prep);
    $_SESSION['success'] = "Meal added to the campus menu!";
}

header("Location: ../views/admin/product.php");
exit;
?>
