<?php
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/OrderController.php';

require_login();

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$orderCtrl = new OrderController();

$order = $orderCtrl->getOrderById($orderId);

if (!$order || ($order['customer_id'] != get_user_id() && !is_admin())) {
    $_SESSION['error'] = "Order not found or access restricted.";
    header("Location: " . $ROOT_DIR . "/index.php");
    exit;
}

$items = $orderCtrl->getOrderDetails($orderId);
$tracking = $orderCtrl->getTrackingHistory($orderId);

// Pre-defined pipeline steps
$steps = [
    'Placed' => 'Order Received by Kitchen',
    'Preparing' => 'Kitchen Preparing Meal',
    'Ready' => 'Ready for Courier Pickup',
    'In Transit' => 'Student Courier on the Way',
    'Delivered' => 'Delivered to Drop Point'
];

$currentStatus = $order['order_status'];
?>

<div class="row g-4 my-3 justify-content-center">
    <div class="col-lg-8">
        <!-- Live Status Banner -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 mb-1">
                        Invoice #<?php echo htmlspecialchars($order['invoice_no']); ?>
                    </span>
                    <h3 class="fw-bold mb-0">Order Status: <span class="text-warning-emphasis"><?php echo htmlspecialchars($currentStatus); ?></span></h3>
                    <small class="text-muted">Placed on <?php echo date('M d, Y - h:i A', strtotime($order['order_date'])); ?></small>
                </div>
                <div>
                    <span class="badge <?php echo ($order['delivery_zone'] === 'Zone B') ? 'bg-warning text-dark' : 'bg-success text-white'; ?> rounded-pill px-3 py-2">
                        <?php echo htmlspecialchars($order['delivery_zone']); ?>
                    </span>
                </div>
            </div>

            <!-- Progress Bar -->
            <?php
                $statusOrder = ['Placed' => 20, 'Preparing' => 45, 'Ready' => 70, 'In Transit' => 90, 'Delivered' => 100];
                $percent = $statusOrder[$currentStatus] ?? 20;
            ?>
            <div class="progress mb-4" style="height: 10px; border-radius: 999px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" style="width: <?php echo $percent; ?>%"></div>
            </div>

            <!-- Timeline -->
            <h6 class="fw-bold text-uppercase text-muted small mb-3">Live Courier & Kitchen Timeline</h6>
            <div class="cb-timeline">
                <?php foreach ($tracking as $t): ?>
                    <div class="cb-timeline-step active">
                        <div class="cb-timeline-node"></div>
                        <div class="cb-timeline-time"><?php echo date('h:i A', strtotime($t['updated_at'])); ?></div>
                        <div class="cb-timeline-title"><?php echo htmlspecialchars($t['status']); ?></div>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($t['status_message']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (is_admin()): ?>
                <!-- Quick Status Update for Admin / Merchant demo -->
                <div class="mt-4 p-3 bg-light rounded-4 border">
                    <form action="<?php echo $ROOT_DIR; ?>/actions/update_order_status_action.php" method="POST" class="d-flex align-items-center gap-2 m-0">
                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                        <label class="small fw-bold text-nowrap">Admin Simulate Status:</label>
                        <select name="status" class="form-select form-select-sm rounded-pill">
                            <?php foreach ($steps as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($currentStatus === $key) ? 'selected' : ''; ?>><?php echo $key; ?> — <?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-dark rounded-pill px-3">Update</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- Drop location & Items ordered -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <h5 class="fw-bold mb-3"><i class="bi bi-geo-alt-fill text-danger me-2"></i>Drop Point & Contact</h5>
            <p class="mb-1 fw-semibold text-dark"><?php echo htmlspecialchars($order['drop_location']); ?></p>
            <p class="small text-muted mb-3">Recipient: <?php echo htmlspecialchars($order['customer_name']); ?> (<?php echo htmlspecialchars($order['customer_contact']); ?>)</p>
            <?php if (!empty($order['special_instructions'])): ?>
                <div class="alert alert-light border small rounded-3 p-2 mb-3">
                    <strong>Note:</strong> <?php echo htmlspecialchars($order['special_instructions']); ?>
                </div>
            <?php endif; ?>

            <h6 class="fw-bold text-uppercase text-muted small mt-2 mb-3">Items in This Order</h6>
            <ul class="list-group list-group-flush mb-3">
                <?php 
                    $totalMealCost = 0;
                    foreach ($items as $it): 
                        $itemSub = (float)$it['product_price'] * (int)$it['qty'];
                        $totalMealCost += $itemSub;
                ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                        <div>
                            <span class="fw-semibold"><?php echo htmlspecialchars($it['product_title']); ?></span>
                            <span class="text-muted small ms-2">x <?php echo $it['qty']; ?> (<?php echo htmlspecialchars($it['brand_name']); ?>)</span>
                        </div>
                        <span class="fw-bold">GH₵ <?php echo number_format($itemSub, 2); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="d-flex justify-content-between small text-muted mb-1">
                <span>Food Items:</span>
                <span>GH₵ <?php echo number_format($totalMealCost, 2); ?></span>
            </div>
            <div class="d-flex justify-content-between small text-muted mb-2">
                <span>Campus Courier Delivery:</span>
                <span>GH₵ <?php echo number_format($order['delivery_fee'], 2); ?></span>
            </div>
            <div class="d-flex justify-content-between fw-bold fs-5 text-dark border-top pt-2">
                <span>Total Paid:</span>
                <span class="text-danger">GH₵ <?php echo number_format($totalMealCost + (float)$order['delivery_fee'], 2); ?></span>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
