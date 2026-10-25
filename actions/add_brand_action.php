<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['brand_name'] ?? '');
    $zone = trim($_POST['zone'] ?? 'Zone A');
    $location = trim($_POST['location_desc'] ?? '');
    $comm = (float)($_POST['commission_rate'] ?? 0.10);

    if (!empty($name)) {
        $prodCtrl = new ProductController();
        $prodCtrl->addBrand($name, $zone, $location, $comm);
        $_SESSION['success'] = "Kitchen vendor registered successfully!";
    }
}

header("Location: ../views/admin/brand.php");
exit;
?>
