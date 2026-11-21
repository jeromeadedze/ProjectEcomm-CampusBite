<?php
// Admin: creates a sign-in for a kitchen's staff (role 4, linked to one kitchen)
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/admin/users.php");
    exit;
}
require_csrf();

$productCtrl = new ProductController();
$result = (new CustomerController())->createKitchenAccount($_POST, function ($brandId) use ($productCtrl) {
    return $brandId > 0 && (bool)$productCtrl->getBrandById($brandId);
});

if ($result['error']) {
    $_SESSION['error'] = $result['error'];
    $_SESSION['kitchen_login_form'] = $result['values'];
} else {
    $kitchen = $productCtrl->getBrandById($result['values']['brand_id']);
    $_SESSION['success'] = "{$result['values']['customer_name']} can now sign in to the {$kitchen['brand_name']} kitchen portal with {$result['values']['customer_email']}.";
}

header("Location: ../views/admin/users.php" . ($result['error'] ? '#new-login' : ''));
exit;
?>
