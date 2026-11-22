<?php
// Saves the kitchen's location and open/paused state from the kitchen portal
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_kitchen_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/kitchen/kitchen_info.php");
    exit;
}
require_csrf();

$location = trim($_POST['location_desc'] ?? '');
$phone = trim($_POST['brand_phone'] ?? '');
$isActive = ($_POST['is_active'] ?? '1') === '0' ? 0 : 1;
// Opening hours: both times, or neither (no set hours)
$hours = ProductController::checkOpeningHours($_POST['opens_at'] ?? '', $_POST['closes_at'] ?? '');

if ($hours['error']) {
    $_SESSION['error'] = $hours['error'];
    $_SESSION['hours_form'] = ['opens_at' => (string)($_POST['opens_at'] ?? ''), 'closes_at' => (string)($_POST['closes_at'] ?? '')];
} elseif ($phone !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
    $_SESSION['error'] = "Enter a valid phone number for customer contact, such as 0241234567.";
} elseif (mb_strlen($location) > 255) {
    $_SESSION['error'] = "Keep the location to 255 characters or fewer.";
} elseif ((new ProductController())->updateBrandInfo(get_kitchen_id(), $location, $isActive, $phone ?: null)
          && (new ProductController())->setOpeningHours(get_kitchen_id(), $hours['opens'], $hours['closes']) !== false) {
    $adminPaused = (int)((new ProductController())->getBrandById(get_kitchen_id())['admin_paused'] ?? 0) === 1;
    if ($adminPaused) {
        $_SESSION['success'] = "Saved. Your kitchen stays paused because CampusBite paused it. Contact the CampusBite admin to reopen.";
    } else {
        $_SESSION['success'] = $isActive ? "Saved. Your kitchen is open to shoppers." : "Saved. Your kitchen is paused and hidden from shoppers.";
    }
    $_SESSION['success'] .= $hours['opens']
        ? ' Opening hours: ' . ProductController::openingHoursLabel(['opens_at' => $hours['opens'], 'closes_at' => $hours['closes']]) . '.'
        : ' No set opening hours.';
} else {
    $_SESSION['error'] = "Your details couldn't be saved. Try again.";
}

header("Location: ../views/kitchen/kitchen_info.php");
exit;
?>
