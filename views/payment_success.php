<?php
// After a verified Paystack payment: the order is placed and the kitchen has it
require_once __DIR__ . '/../core/core.php';
require_login();

require_once __DIR__ . '/../controllers/OrderController.php';
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';

$orderCtrl = new OrderController();
$order = $orderCtrl->getOrderById((int)($_GET['order_id'] ?? 0));
if (!$order || (int)$order['customer_id'] !== get_user_id()) {
    header('Location: ' . $ROOT_DIR . '/views/my_orders.php');
    exit;
}
$items = $orderCtrl->getOrderDetails($order['order_id']);
$mealTotal = 0;
foreach ($items as $it) {
    $mealTotal += (float)$it['unit_price'] * (int)$it['qty'];
}

$hidePageTitle = true;
require_once __DIR__ . '/layout/header.php';
?>

<section class="cb-card cb-result">
    <span class="cb-result-icon is-ok" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
    <h1>Payment received</h1>
    <p>Order <b>#<?php echo htmlspecialchars($order['invoice_no']); ?></b> is with <b><?php echo htmlspecialchars($order['brand_name'] ?? 'the kitchen'); ?></b>. They'll start cooking and deliver it to <b><?php echo htmlspecialchars($order['drop_location']); ?></b>.</p>

    <ul class="cb-checkout-items">
        <?php foreach ($items as $it): ?>
            <li>
                <span class="cb-ticket-qty"><?php echo (int)$it['qty']; ?>×</span>
                <span><?php echo htmlspecialchars($it['product_title']); ?></span>
                <span class="cb-tray-price">GH₵ <?php echo number_format((float)$it['unit_price'] * (int)$it['qty'], 2); ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="cb-totals">
        <div><span>Meals</span><span>GH₵ <?php echo number_format($mealTotal, 2); ?></span></div>
        <div><span>Delivery</span><span>GH₵ <?php echo number_format((float)$order['delivery_fee'], 2); ?></span></div>
        <div class="total"><span>Paid</span><span>GH₵ <?php echo number_format((float)($order['amt'] ?? $mealTotal + (float)$order['delivery_fee']), 2); ?></span></div>
    </div>
    <?php if (!empty($order['transaction_reference'])): ?>
        <p class="cb-field-hint">Paystack reference <?php echo htmlspecialchars($order['transaction_reference']); ?><?php echo !empty($order['payment_mode']) ? ' · ' . htmlspecialchars($order['payment_mode']) : ''; ?></p>
    <?php endif; ?>

    <div class="cb-result-actions">
        <a class="cb-checkout cb-checkout-link" href="<?php echo $ROOT_DIR; ?>/views/order_tracking.php?order_id=<?php echo (int)$order['order_id']; ?>">Track my order</a>
        <a class="cb-btn cb-btn-quiet" href="<?php echo $ROOT_DIR; ?>/views/menu.php">Order something else</a>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
