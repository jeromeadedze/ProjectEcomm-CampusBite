<?php
/**
 * "Order again": puts the meals of one of the shopper's past orders back in their basket, then opens the bill.
 * POST: csrf_token, order_id, replace (1 = the basket holds another kitchen's meals and the shopper agreed to empty it),
 * from ('tracking' when sent from the order page, so a refusal returns there).
 * Meals that are sold out, or whose kitchen is paused, are left out and named. Prices are today's, because the
 * basket always reads the current price.
 */
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';
require_once __DIR__ . '/../controllers/OrderController.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_login();
if (!can_order()) {
    header("Location: .." . staff_orders_path());
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/my_orders.php");
    exit;
}
require_csrf();

$orderId = (int)($_POST['order_id'] ?? 0);
$replace = ($_POST['replace'] ?? '') === '1';
$back = ($_POST['from'] ?? '') === 'tracking' ? "../views/order_tracking.php?order_id=$orderId" : "../views/my_orders.php";

$orderCtrl = new OrderController();
$order = $orderId > 0 ? $orderCtrl->getOrderById($orderId) : null;
// Only the shopper's own orders
if (!$order || (int)$order['customer_id'] !== (int)get_user_id()) {
    $_SESSION['error'] = "We couldn't find that order.";
    header("Location: ../views/my_orders.php");
    exit;
}

$cartCtrl = new CartController();
$productCtrl = new ProductController();
$token = get_cart_token();
$userId = get_user_id();

// A basket holds one kitchen's meals. The button asks first when the basket is from another kitchen.
$basket = $cartCtrl->getCartItems($token, $userId);
if ($basket && (int)$basket[0]['product_brand'] !== (int)$order['brand_id']) {
    if (!$replace) {
        $_SESSION['error'] = "Your basket has meals from " . $basket[0]['brand_name'] . ". Each order comes from one kitchen, so empty your basket first to order again from " . ($order['brand_name'] ?? 'this kitchen') . ".";
        header("Location: $back");
        exit;
    }
    $cartCtrl->clearCart($token, $userId);
}

$added = 0;
$leftOut = [];
foreach ($orderCtrl->getOrderDetails($orderId) as $line) {
    $product = $productCtrl->getProductById((int)$line['product_id']);
    if (!$productCtrl->isOrderable($product)) {
        $leftOut[] = $line['product_title'];
        continue;
    }
    // Adds to what's already in the basket (capped at CartClass::MAX_QTY) and keeps the meal's note from last time
    $cartCtrl->addToCart((int)$line['product_id'], $token, $userId, max(1, (int)$line['qty']), $line['notes'] ?? null);
    $added += max(1, (int)$line['qty']);
}

if ($added === 0) {
    $_SESSION['error'] = "None of the meals from order #" . $order['invoice_no'] . " can be ordered right now. They're sold out or the kitchen is paused.";
    header("Location: $back");
    exit;
}

$message = "Added $added meal" . ($added === 1 ? '' : 's') . " from order #" . $order['invoice_no'] . " to your basket, at today's prices.";
if ($leftOut) {
    $message .= " Not available right now, so left out: " . implode(', ', array_unique($leftOut)) . ".";
}
$_SESSION['success'] = $message;
header("Location: ../views/cart.php");
exit;
