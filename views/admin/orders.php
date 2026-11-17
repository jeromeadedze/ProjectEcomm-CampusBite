<?php
// Admin: every order, newest first, with its kitchen, courier and status
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../controllers/OrderController.php';

require_admin();

$allOrders = (new OrderController())->getAllOrders();
$activeCount = count(array_filter($allOrders, function ($o) {
    return $o['order_status'] !== 'Delivered';
}));
?>


<p class="cb-sub"><?php echo $activeCount; ?> in progress · <?php echo count($allOrders); ?> total</p>

<p class="cb-field-hint">Each kitchen moves its own orders along and assigns couriers from its kitchen portal. You can still change any order's status from Manage.</p>

<section class="cb-card">
    <div class="cb-table-wrap">
        <table class="cb-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Kitchen</th>
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
                    <tr><td colspan="8" class="cb-table-empty">No orders yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($allOrders as $o): ?>
                    <tr>
                        <td class="cb-table-name">
                            #<?php echo htmlspecialchars($o['invoice_no']); ?>
                            <span class="cb-table-sub"><?php echo date('M d · h:i A', strtotime($o['order_date'])); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($o['brand_name'] ?? '–'); ?></td>
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
                        <td><?php echo htmlspecialchars($o['courier_name'] ?? 'Unassigned'); ?></td>
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
