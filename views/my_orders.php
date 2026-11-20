<?php
// The shopper's past orders, newest first, with what each one cost
require_once __DIR__ . '/../core/core.php';
require_login();

require_once __DIR__ . '/../controllers/OrderController.php';
$myOrders = (new OrderController())->getOrdersByCustomer(get_user_id());
require_once __DIR__ . '/layout/header.php';
?>

<?php if (empty($myOrders)): ?>
    <div class="cb-card cb-bill-empty">
        <span class="cb-bill-empty-icon" aria-hidden="true"><i class="bi bi-receipt"></i></span>
        <h2>No orders yet</h2>
        <p>When you place an order, you can follow it from the kitchen to your door here.</p>
        <a class="cb-btn" href="<?php echo $ROOT_DIR; ?>/views/menu.php">Browse the menu</a>
    </div>
<?php else: ?>
    <p class="cb-sub"><?php echo count($myOrders); ?> order<?php echo count($myOrders) === 1 ? '' : 's'; ?></p>
    <div class="cb-order-cards">
        <?php foreach ($myOrders as $ord): ?>
            <a class="cb-order-card" href="<?php echo $ROOT_DIR; ?>/views/order_tracking.php?order_id=<?php echo (int)$ord['order_id']; ?>">
                <div class="cb-order-card-head">
                    <b>#<?php echo htmlspecialchars($ord['invoice_no']); ?></b>
                    <span class="cb-status cb-status-<?php echo strtolower(str_replace(' ', '-', $ord['order_status'])); ?>"><?php echo htmlspecialchars($ord['order_status']); ?></span>
                </div>
                <p class="cb-order-card-kitchen"><?php echo htmlspecialchars($ord['brand_name'] ?? 'Kitchen'); ?></p>
                <p class="cb-muted"><?php echo date('M d, Y · h:i A', strtotime($ord['order_date'])); ?></p>
                <p class="cb-muted"><i class="bi bi-geo-alt" aria-hidden="true"></i> <?php echo htmlspecialchars($ord['drop_location']); ?></p>
                <div class="cb-order-card-foot">
                    <span><?php echo (int)$ord['item_count']; ?> meal<?php echo (int)$ord['item_count'] === 1 ? '' : 's'; ?> · <b class="cb-price">GH₵ <?php echo number_format((float)$ord['order_total'], 2); ?></b></span>
                    <span class="cb-order-card-go">Track ›</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
