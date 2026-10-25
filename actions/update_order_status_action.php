<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/OrderController.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'Placed');

    if ($orderId > 0) {
        $orderCtrl = new OrderController();
        $orderCtrl->updateOrderStatus($orderId, $status);
        $_SESSION['success'] = "Order #{$orderId} status updated to {$status}.";
    }
}

$ref = $_SERVER['HTTP_REFERER'] ?? '../views/admin/orders.php';
header("Location: " . $ref);
exit;
?>
