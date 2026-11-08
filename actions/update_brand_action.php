<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/admin/brand.php");
    exit;
}
require_csrf();

$controller = new ProductController();
$id = (int)($_POST['brand_id'] ?? 0);

if (!$controller->getBrandById($id)) {
    $_SESSION['error'] = "That kitchen no longer exists.";
    header("Location: ../views/admin/brand.php");
    exit;
}

$checked = $controller->checkBrandForm($_POST, $id);
$values = $checked['values'];

if ($checked['error']) {
    $_SESSION['error'] = $checked['error'];
    $_SESSION['brand_form'] = $values;
    header("Location: ../views/admin/brand.php?edit_id=" . $id);
    exit;
}

if ($controller->updateBrand($id, $values['brand_name'], $values['zone'], $values['location_desc'], (int)$values['commission_percent'] / 100, $values['is_active'])) {
    $_SESSION['success'] = "{$values['brand_name']} was updated.";
} else {
    $_SESSION['error'] = "The kitchen couldn't be saved. Try again.";
}

header("Location: ../views/admin/brand.php");
exit;
?>
