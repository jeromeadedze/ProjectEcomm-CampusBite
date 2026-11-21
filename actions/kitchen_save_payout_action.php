<?php
// Kitchen portal: saves where Paystack pays the kitchen (its Mobile Money number or bank account)
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_kitchen();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/kitchen/kitchen_info.php#payout");
    exit;
}
require_csrf();

$controller = new ProductController();
$hadAccount = !empty($controller->getBrandById(get_kitchen_id())['paystack_subaccount']);
$result = $controller->savePayoutAccount(get_kitchen_id(), $_POST);

if ($result['error']) {
    $_SESSION['error'] = $result['error'];
    $_SESSION['payout_form'] = $result['values'];
} else {
    $label = ProductController::payoutLabel($controller->getBrandById(get_kitchen_id()));
    $_SESSION['success'] = $hadAccount
        ? "Saved. Paystack now pays your share of new orders to $label."
        : "Saved. Students can now pay for your meals, and Paystack pays your share of each order to $label.";
}

header("Location: ../views/kitchen/kitchen_info.php#payout");
exit;
?>
