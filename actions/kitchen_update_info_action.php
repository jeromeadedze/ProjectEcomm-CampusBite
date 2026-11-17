<?php
// Saves the kitchen's location and open/paused state from the kitchen portal
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_kitchen();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/kitchen/kitchen_info.php");
    exit;
}
require_csrf();

$location = trim($_POST['location_desc'] ?? '');
$isActive = ($_POST['is_active'] ?? '1') === '0' ? 0 : 1;

if (mb_strlen($location) > 255) {
    $_SESSION['error'] = "Keep the location to 255 characters or fewer.";
} elseif ((new ProductController())->updateBrandInfo(get_kitchen_id(), $location, $isActive)) {
    $_SESSION['success'] = $isActive ? "Saved. Your kitchen is open to shoppers." : "Saved. Your kitchen is paused and hidden from shoppers.";
} else {
    $_SESSION['error'] = "Your details couldn't be saved. Try again.";
}

header("Location: ../views/kitchen/kitchen_info.php");
exit;
?>
