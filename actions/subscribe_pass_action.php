<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/CustomerClass.php';

require_login();

if (!PASS_ENABLED) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/pass.php");
    exit;
}
require_csrf();

$userId = get_user_id();
$custClass = new CustomerClass();
$user = $custClass->getCustomerById($userId);

if (has_active_pass($user)) {
    $_SESSION['error'] = "Your CampusBite Pass is already active until " . $user['pass_expiry'] . ".";
    header("Location: ../views/pass.php");
    exit;
}

// TODO: charge GH₵ 35.00 through Paystack here and only continue once the payment is verified.
// Checkout is also simulated, so there is no payment gateway to call yet.

$expiry = date('Y-m-d', strtotime('+30 days'));
$custClass->updatePassStatus($userId, 1, $expiry);
$_SESSION['has_pass'] = 1;

$_SESSION['success'] = "Congratulations! You have activated CampusBite Pass! Enjoy free Zone A deliveries.";
header("Location: ../views/pass.php");
exit;
?>
