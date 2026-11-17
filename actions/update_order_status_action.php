<?php
// Moves an order through Placed → Preparing → Ready → In Transit → Delivered.
// Allowed for admins and for the kitchen the order is from.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/OrderController.php';

require_login();
if (!is_admin() && !is_kitchen()) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');

    $orderCtrl = new OrderController();
    $order = $orderId > 0 ? $orderCtrl->getOrderById($orderId) : null;

    if (!$order) {
        $_SESSION['error'] = "That order no longer exists.";
    } elseif (is_kitchen() && (int)$order['brand_id'] !== get_kitchen_id()) {
        $_SESSION['error'] = "That order isn't from your kitchen.";
    } elseif (!array_key_exists($status, OrderController::STATUSES)) {
        $_SESSION['error'] = "Choose a status from the list.";
    } elseif ($status === $order['order_status']) {
        // Nothing changed; don't add a timeline entry
    } else {
        $orderCtrl->updateOrderStatus($orderId, $status);
        $_SESSION['success'] = "Order #{$order['invoice_no']} is now {$status}.";
    }
}

redirect_back('..' . staff_orders_path());
?>
