<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';
require_once __DIR__ . '/../controllers/OrderController.php';

require_once __DIR__ . '/../controllers/CustomerController.php';

require_login();

if (is_admin()) {
    $_SESSION['error'] = "Admin accounts can't place orders.";
    header("Location: ../views/admin/orders.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/cart.php");
    exit;
}
require_csrf();

$userId = get_user_id();
$cartIp = get_cart_token();

$cartCtrl = new CartController();
$orderCtrl = new OrderController();

$cartItems = $cartCtrl->getCartItems($cartIp, $userId);

if (empty($cartItems)) {
    $_SESSION['error'] = "Your bag is empty. Please add items to checkout.";
    header("Location: ../views/cart.php");
    exit;
}

// Zone and fee are worked out here from the cart, never taken from the form
$user = (new CustomerController())->getCustomerById($userId);
$deliveryZone = $cartCtrl->deliveryZoneFor($cartItems);
$deliveryFee = $cartCtrl->calculateDeliveryFee($cartItems, has_active_pass($user));

$paymentModes = ['Paystack - MTN MoMo', 'Paystack - Telecel Cash', 'Paystack - Bank Card'];
$paymentMode = $_POST['payment_mode'] ?? '';
$dropLocation = trim($_POST['drop_location'] ?? '');
$notes = trim($_POST['notes'] ?? '');

$formError = null;
if (!in_array($paymentMode, $paymentModes, true)) {
    $formError = "Choose a payment method from the list.";
} elseif ($dropLocation === '' || mb_strlen($dropLocation) > 150) {
    $formError = "Enter a drop point of up to 150 characters.";
} elseif (mb_strlen($notes) > 255) {
    $formError = "Keep delivery instructions to 255 characters or fewer.";
}
if ($formError) {
    $_SESSION['error'] = $formError;
    header("Location: ../views/checkout.php");
    exit;
}

$unavailable = $cartCtrl->unavailableItems($cartItems);
if ($unavailable) {
    $names = implode(', ', array_column($unavailable, 'product_title'));
    $_SESSION['error'] = "Some meals are no longer available: $names. Remove them to check out.";
    header("Location: ../views/cart.php");
    exit;
}

$subtotal = $cartCtrl->getCartTotal($cartIp, $userId);
$grandTotal = $subtotal + $deliveryFee;
$invoiceNo = mt_rand(100000, 999999);
$txnRef = "CB-" . strtoupper(bin2hex(random_bytes(5)));

// The order, its items, the payment and emptying the cart all succeed together or not at all
try {
    $orderCtrl->beginTransaction();

    $orderId = $orderCtrl->createOrder($userId, $invoiceNo, $deliveryZone, $deliveryFee, $dropLocation, $notes);
    if (!$orderId) {
        throw new RuntimeException('Order row was not created');
    }
    foreach ($cartItems as $item) {
        $orderCtrl->addOrderDetail($orderId, $item['p_id'], $item['qty'], $item['notes']);
    }
    $orderCtrl->recordPayment($grandTotal, $userId, $orderId, $paymentMode, $txnRef);
    $cartCtrl->clearCart($cartIp, $userId);

    $orderCtrl->commit();
} catch (Exception $e) {
    $orderCtrl->rollBack();
    error_log('Checkout failed: ' . $e->getMessage());
    $_SESSION['error'] = "Failed to place your order. Your bag is unchanged, so please try again.";
    header("Location: ../views/checkout.php");
    exit;
}

$_SESSION['success'] = "Order placed successfully! Track your meal progress below.";
header("Location: ../views/order_tracking.php?order_id=" . $orderId);
exit;
?>
