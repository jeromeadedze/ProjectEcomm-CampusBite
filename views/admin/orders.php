<?php
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '../..';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../controllers/OrderController.php';

require_admin();

$orderCtrl = new OrderController();
$allOrders = $orderCtrl->getAllOrders();
?>

<div class="my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold m-0"><i class="bi bi-clipboard2-check text-warning me-2"></i>Campus Orders Management</h3>
            <span class="text-muted small">Monitor kitchen order queues & courier handoffs</span>
        </div>
        <span class="badge bg-dark rounded-pill fs-6"><?php echo count($allOrders); ?> total orders</span>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <table class="cb-table mb-0">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Customer & Contact</th>
                    <th>Zone & Drop Point</th>
                    <th>Total</th>
                    <th>Payment Mode</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allOrders as $o): ?>
                    <tr>
                        <td class="fw-bold">#<?php echo $o['invoice_no']; ?></td>
                        <td>
                            <div class="fw-semibold"><?php echo htmlspecialchars($o['customer_name']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($o['customer_contact']); ?></small>
                        </td>
                        <td>
                            <span class="badge <?php echo ($o['delivery_zone'] === 'Zone B') ? 'bg-warning text-dark' : 'bg-success text-white'; ?> rounded-pill mb-1">
                                <?php echo htmlspecialchars($o['delivery_zone']); ?>
                            </span>
                            <div class="small text-truncate" style="max-width: 180px;"><?php echo htmlspecialchars($o['drop_location']); ?></div>
                        </td>
                        <td class="fw-bold text-danger">GH₵ <?php echo number_format($o['amt'] ?? ($o['delivery_fee'] + 30), 2); ?></td>
                        <td>
                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($o['payment_mode'] ?? 'Paystack'); ?></span>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">
                                <?php echo htmlspecialchars($o['order_status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo $ROOT_DIR; ?>/views/order_tracking.php?order_id=<?php echo $o['order_id']; ?>" class="btn btn-sm btn-outline-dark rounded-pill px-3">
                                <i class="bi bi-sliders"></i> Manage
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
