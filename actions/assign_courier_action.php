<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/OrderController.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/admin/orders.php");
    exit;
}
require_csrf();

$orderCtrl = new OrderController();
$orderId = (int)($_POST['order_id'] ?? 0);
$courierId = (int)($_POST['courier_id'] ?? 0);
$order = $orderCtrl->getOrderById($orderId);
$courier = $courierId > 0 ? (new CustomerController())->getCustomerById($courierId) : null;

if (!$order) {
    $_SESSION['error'] = "That order no longer exists.";
} elseif ($courierId > 0 && (!$courier || (int)$courier['user_role'] !== 3)) {
    $_SESSION['error'] = "Choose a courier from the list.";
} elseif ((int)$order['courier_id'] === $courierId) {
    // Nothing changed; don't add a timeline entry
} elseif ($orderCtrl->assignCourier($orderId, $courier ? $courierId : null, $courier['customer_name'] ?? null)) {
    $_SESSION['success'] = $courier
        ? "{$courier['customer_name']} is delivering order #{$order['invoice_no']}."
        : "Order #{$order['invoice_no']} no longer has a courier.";
} else {
    $_SESSION['error'] = "The courier couldn't be saved. Try again.";
}

redirect_back('../views/admin/orders.php');
?>
