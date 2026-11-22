<?php
// One-tap switch on the kitchen dashboards: opens or pauses the signed-in kitchen without touching its other details.
// A kitchen paused by the CampusBite admin can't reopen itself.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_kitchen();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: .." . home_path_for_role());
    exit;
}
require_csrf();

$controller = new ProductController();
$kitchen = $controller->getBrandById(get_kitchen_id());
$open = ($_POST['open'] ?? '') === '1' ? 1 : 0;

if ($open && (int)($kitchen['admin_paused'] ?? 0) === 1) {
    $_SESSION['error'] = "CampusBite has paused your kitchen. Contact the CampusBite admin to reopen.";
} elseif ($controller->updateBrandInfo($kitchen['brand_id'], $kitchen['location_desc'], $open, $kitchen['brand_phone'] ?? null)) {
    $_SESSION['success'] = $open ? "Your kitchen is open. Students can order now." : "Your kitchen is paused. Students can't order until you reopen.";
} else {
    $_SESSION['error'] = "That didn't save. Try again.";
}

redirect_back('..' . home_path_for_role());
?>
