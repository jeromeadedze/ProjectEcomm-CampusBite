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
$checked = $controller->checkBrandForm($_POST);
$values = $checked['values'];

if ($checked['error']) {
    $_SESSION['error'] = $checked['error'];
    $_SESSION['brand_form'] = $values;
} elseif ($controller->addBrand($values['brand_name'], $values['zone'], $values['location_desc'], (int)$values['commission_percent'] / 100, $values['is_active'])) {
    $_SESSION['success'] = "{$values['brand_name']} was added.";
} else {
    $_SESSION['error'] = "The kitchen couldn't be saved. Try again.";
    $_SESSION['brand_form'] = $values;
}

header("Location: ../views/admin/brand.php");
exit;
?>
