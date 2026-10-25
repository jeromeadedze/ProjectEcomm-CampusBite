<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';
require_once __DIR__ . '/../controllers/OrderController.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/cart.php");
    exit;
}

$userId = get_user_id();
$cartIp = get_ip();

$cartCtrl = new CartController();
$orderCtrl = new OrderController();

$cartItems = $cartCtrl->getCartItems($cartIp, $userId);

if (empty($cartItems)) {
    $_SESSION['error'] = "Your bag is empty. Please add items to checkout.";
    header("Location: ../views/cart.php");
    exit;
}

$deliveryZone = trim($_POST['delivery_zone'] ?? 'Zone A');
$deliveryFee = (float)($_POST['delivery_fee'] ?? 4.00);
$dropLocation = trim($_POST['drop_location'] ?? 'Ashesi Campus');
$notes = trim($_POST['notes'] ?? '');
$paymentMode = trim($_POST['payment_mode'] ?? 'Paystack - MTN MoMo');

$subtotal = $cartCtrl->getCartTotal($cartIp, $userId);
$grandTotal = $subtotal + $deliveryFee;

// Generate random invoice number
$invoiceNo = mt_rand(100000, 999999);

// Create the Order in database
$orderId = $orderCtrl->createOrder($userId, $invoiceNo, $deliveryZone, $deliveryFee, $dropLocation, $notes);

if ($orderId) {
    // Insert order items
    foreach ($cartItems as $item) {
        $orderCtrl->addOrderDetail($orderId, $item['p_id'], $item['qty']);
    }

    // Generate transaction reference
    $txnRef = "CB-" . strtoupper(bin2hex(random_bytes(5)));

    // Record payment entry
    $orderCtrl->recordPayment($grandTotal, $userId, $orderId, $paymentMode, $txnRef);

    // Empty user cart
    $cartCtrl->clearCart($cartIp, $userId);

    $_SESSION['success'] = "Order placed successfully! Track your meal progress below.";
    header("Location: ../views/order_tracking.php?order_id=" . $orderId);
    exit;
} else {
    $_SESSION['error'] = "Failed to place your order. Please try again.";
    header("Location: ../views/checkout.php");
    exit;
}
?>
