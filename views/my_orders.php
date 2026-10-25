<?php
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/OrderController.php';

require_login();

$orderCtrl = new OrderController();
$myOrders = $orderCtrl->getOrdersByCustomer(get_user_id());
?>

<div class="row g-4 my-3 justify-content-center">
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold m-0"><i class="bi bi-clock-history text-warning me-2"></i>My Order History</h3>
            <span class="text-muted small"><?php echo count($myOrders); ?> orders</span>
        </div>

        <?php if (empty($myOrders)): ?>
            <div class="card border-0 shadow-sm rounded-4 text-center py-5">
                <i class="bi bi-receipt text-muted" style="font-size: 3rem;"></i>
                <h5 class="fw-bold mt-3">No orders yet</h5>
                <p class="text-muted">Place your first order from Hallmark, Akonnor or Peckish now!</p>
                <a href="<?php echo $ROOT_DIR; ?>/index.php" class="cb-btn-primary btn px-4 mx-auto">Browse Menus</a>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <table class="cb-table mb-0">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Date & Time</th>
                            <th>Drop Location</th>
                            <th>Zone</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myOrders as $ord): ?>
                            <tr>
                                <td class="fw-bold">#<?php echo htmlspecialchars($ord['invoice_no']); ?></td>
                                <td class="small text-muted"><?php echo date('M d, Y - h:i A', strtotime($ord['order_date'])); ?></td>
                                <td class="small"><?php echo htmlspecialchars($ord['drop_location']); ?></td>
                                <td>
                                    <span class="badge <?php echo ($ord['delivery_zone'] === 'Zone B') ? 'bg-warning text-dark' : 'bg-success text-white'; ?> rounded-pill">
                                        <?php echo htmlspecialchars($ord['delivery_zone']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">
                                        <?php echo htmlspecialchars($ord['order_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo $ROOT_DIR; ?>/views/order_tracking.php?order_id=<?php echo $ord['order_id']; ?>" class="btn btn-sm btn-outline-dark rounded-pill px-3">
                                        <i class="bi bi-eye"></i> Track
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
