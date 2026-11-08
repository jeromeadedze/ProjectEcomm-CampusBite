<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/OrderController.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'Placed');

    if ($orderId > 0) {
        $orderCtrl = new OrderController();
        if (!array_key_exists($status, OrderController::STATUSES)) {
            $_SESSION['error'] = "Choose a status from the list.";
        } elseif (!$orderCtrl->getOrderById($orderId)) {
            $_SESSION['error'] = "That order no longer exists.";
        } else {
            $orderCtrl->updateOrderStatus($orderId, $status);
            $_SESSION['success'] = "Order #{$orderId} status updated to {$status}.";
        }
    }
}

redirect_back('../views/admin/orders.php');
?>
