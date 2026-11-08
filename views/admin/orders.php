<?php
// Admin: every order, newest first, with its courier and status
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../controllers/OrderController.php';
require_once __DIR__ . '/../../controllers/CustomerController.php';

require_admin();

$allOrders = (new OrderController())->getAllOrders();
$couriers = (new CustomerController())->getCouriers();
$activeCount = count(array_filter($allOrders, function ($o) {
    return $o['order_status'] !== 'Delivered';
}));
?>

<?php require __DIR__ . '/../layout/admin_nav.php'; ?>

<p class="cb-sub"><?php echo $activeCount; ?> in progress · <?php echo count($allOrders); ?> total</p>

<?php if (empty($couriers)): ?>
    <p class="cb-empty">No couriers yet. On the <a class="cb-link" href="<?php echo $ROOT_DIR; ?>/views/customers.php">Customers</a> tab, set an account's role to Courier to assign it orders.</p>
<?php endif; ?>

<section class="cb-card">
    <div class="cb-table-wrap">
        <table class="cb-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Customer</th>
                    <th>Drop point</th>
                    <th class="num">Total</th>
                    <th>Status</th>
                    <th>Courier</th>
                    <th><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($allOrders)): ?>
                    <tr><td colspan="7" class="cb-table-empty">No orders yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($allOrders as $o): ?>
                    <tr>
                        <td class="cb-table-name">
                            #<?php echo htmlspecialchars($o['invoice_no']); ?>
                            <span class="cb-table-sub"><?php echo date('M d · h:i A', strtotime($o['order_date'])); ?></span>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($o['customer_name']); ?>
                            <span class="cb-table-sub"><?php echo htmlspecialchars($o['customer_contact']); ?></span>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($o['drop_location']); ?>
                            <span class="cb-table-sub"><?php echo htmlspecialchars($o['delivery_zone']); ?></span>
                        </td>
                        <td class="num">
                            GH₵ <?php echo number_format($o['amt'] ?? ((float)$o['items_total'] + (float)$o['delivery_fee']), 2); ?>
                            <span class="cb-table-sub"><?php echo htmlspecialchars($o['payment_mode'] ?? 'No payment recorded'); ?></span>
                        </td>
                        <td><span class="cb-status cb-status-<?php echo strtolower(str_replace(' ', '-', $o['order_status'])); ?>"><?php echo htmlspecialchars($o['order_status']); ?></span></td>
                        <td>
                            <?php if (!empty($couriers)): ?>
                                <form class="cb-inline-form" action="<?php echo $ROOT_DIR; ?>/actions/assign_courier_action.php" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="order_id" value="<?php echo (int)$o['order_id']; ?>">
                                    <label class="visually-hidden" for="courier-<?php echo (int)$o['order_id']; ?>">Courier for order #<?php echo htmlspecialchars($o['invoice_no']); ?></label>
                                    <select id="courier-<?php echo (int)$o['order_id']; ?>" name="courier_id">
                                        <option value="0">Unassigned</option>
                                        <?php foreach ($couriers as $courier): ?>
                                            <option value="<?php echo (int)$courier['customer_id']; ?>" <?php echo (int)$o['courier_id'] === (int)$courier['customer_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($courier['customer_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="cb-btn cb-btn-quiet">Assign</button>
                                </form>
                            <?php else: ?>
                                <span class="cb-muted"><?php echo htmlspecialchars($o['courier_name'] ?? 'Unassigned'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="cb-table-actions">
                                <a href="<?php echo $ROOT_DIR; ?>/views/order_tracking.php?order_id=<?php echo (int)$o['order_id']; ?>">Manage ›</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
