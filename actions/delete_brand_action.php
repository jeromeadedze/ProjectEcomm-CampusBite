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
$brand = $controller->getBrandById($id);

if (!$brand) {
    $_SESSION['error'] = "That kitchen no longer exists.";
    header("Location: ../views/admin/brand.php");
    exit;
}

// The database would delete the kitchen's whole menu along with it, so refuse instead
$itemCount = $controller->countProductsByBrand($id);
if ($itemCount > 0) {
    $items = $itemCount === 1 ? '1 menu item. Remove it' : "$itemCount menu items. Remove them";
    $_SESSION['error'] = "{$brand['brand_name']} still has $items first, or set the kitchen to Paused to hide it.";
    header("Location: ../views/admin/brand.php");
    exit;
}

try {
    $controller->deleteBrand($id);
    $_SESSION['success'] = "{$brand['brand_name']} was deleted.";
} catch (PDOException $e) {
    $_SESSION['error'] = "{$brand['brand_name']} couldn't be deleted. Try again.";
}

header("Location: ../views/admin/brand.php");
exit;
?>
