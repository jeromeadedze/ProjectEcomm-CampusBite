<?php
// Cancels an order and refunds it in full through Paystack.
// Customers can cancel their own order before the kitchen starts it, the order's kitchen can reject it
// until it's packed (with a reason), and the admin can cancel any order that isn't delivered (with a reason).
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/OrderController.php';

require_login();

$orderId = (int)($_POST['order_id'] ?? 0);
$back = '../views/order_tracking.php?order_id=' . $orderId;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}
require_csrf();

$orderCtrl = new OrderController();
$order = $orderId > 0 ? $orderCtrl->getOrderById($orderId) : null;

// Who is cancelling decides what they're allowed to cancel
$by = null;
if ($order) {
    if (is_admin()) {
        $by = 'admin';
    } elseif (is_kitchen() && get_kitchen_id() === (int)$order['brand_id']) {
        $by = 'kitchen';
    } elseif (can_order() && (int)$order['customer_id'] === (int)get_user_id()) {
        $by = 'customer';
    }
}
if (!$by) {
    $_SESSION['error'] = "That order isn't yours to cancel.";
    header('Location: ../index.php');
    exit;
}

// Kitchens pick a reason (or write one); admins must write one; customers may add one
$reason = trim($_POST['reason'] ?? '');
if ($by === 'kitchen') {
    // A reason from the list, or the kitchen's own words after choosing "Something else"
    $reason = $reason === 'other' ? trim($_POST['reason_other'] ?? '') : (in_array($reason, OrderController::KITCHEN_REJECT_REASONS, true) ? $reason : '');
}
if (($by === 'kitchen' || $by === 'admin') && $reason === '') {
    $_SESSION['error'] = $by === 'kitchen' ? "Choose why you're rejecting the order, so the customer knows." : "Write a reason for cancelling. The customer and the kitchen will see it.";
    header("Location: $back#cancel");
    exit;
}
if (mb_strlen($reason) > 255) {
    $_SESSION['error'] = "Keep the reason to 255 characters or fewer.";
    header("Location: $back#cancel");
    exit;
}

$result = $orderCtrl->cancelOrder($order, $by, $reason);
$_SESSION[$result['ok'] ? 'success' : 'error'] = $result['message'];
if ($result['ok']) {
    require_once __DIR__ . '/../controllers/OrderEmails.php';
    (new OrderEmails())->cancelled($orderId);
}
header("Location: $back");
exit;
