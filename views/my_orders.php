<?php
// The shopper's past orders, newest first, with what each one cost
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/OrderController.php';

require_login();

$myOrders = (new OrderController())->getOrdersByCustomer(get_user_id());
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
    <section class="cb-card">
        <div class="cb-table-wrap">
            <table class="cb-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Placed</th>
                        <th>Drop point</th>
                        <th class="num">Meals</th>
                        <th class="num">Total</th>
                        <th>Status</th>
                        <th><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myOrders as $ord): ?>
                        <tr>
                            <td class="cb-table-name">#<?php echo htmlspecialchars($ord['invoice_no']); ?></td>
                            <td><?php echo date('M d, Y · h:i A', strtotime($ord['order_date'])); ?></td>
                            <td>
                                <?php echo htmlspecialchars($ord['drop_location']); ?>
                                <span class="cb-table-sub"><?php echo htmlspecialchars($ord['delivery_zone']); ?> delivery</span>
                            </td>
                            <td class="num"><?php echo (int)$ord['item_count']; ?></td>
                            <td class="num"><strong>GH₵ <?php echo number_format((float)$ord['order_total'], 2); ?></strong></td>
                            <td><span class="cb-status cb-status-<?php echo strtolower(str_replace(' ', '-', $ord['order_status'])); ?>"><?php echo htmlspecialchars($ord['order_status']); ?></span></td>
                            <td>
                                <div class="cb-table-actions">
                                    <a href="<?php echo $ROOT_DIR; ?>/views/order_tracking.php?order_id=<?php echo (int)$ord['order_id']; ?>">Track ›</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
