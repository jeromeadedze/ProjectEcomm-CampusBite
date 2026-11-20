<?php
// One order: where it is now, what happened so far, where it's going and what was in it.
// Open to the customer who placed it, admins, and the kitchen it's from (who can also change its status).
require_once __DIR__ . '/../core/core.php';
require_login();

require_once __DIR__ . '/../controllers/OrderController.php';
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$orderCtrl = new OrderController();
$order = $orderCtrl->getOrderById($orderId);

$isOrderKitchen = get_kitchen_id() !== null && get_kitchen_id() === (int)($order['brand_id'] ?? 0);
if (!$order || ($order['customer_id'] != get_user_id() && !is_admin() && !$isOrderKitchen)) {
    $_SESSION['error'] = "Order not found or access restricted.";
    header("Location: " . $ROOT_DIR . "/index.php");
    exit;
}

$items = $orderCtrl->getOrderDetails($orderId);
$tracking = $orderCtrl->getTrackingHistory($orderId);
$steps = OrderController::STATUSES;
$currentStatus = $order['order_status'];
$currentIndex = array_search($currentStatus, array_keys($steps), true);
$stepIcons = ['Placed' => 'bi-receipt', 'Preparing' => 'bi-fire', 'Ready' => 'bi-bag-check', 'In Transit' => 'bi-bicycle', 'Delivered' => 'bi-house-check'];
$next = OrderController::NEXT_STEP[$currentStatus] ?? null;

$pageHeading = 'Order #' . $order['invoice_no'];
require_once __DIR__ . '/layout/header.php';

$mealTotal = 0;
foreach ($items as $it) {
    $mealTotal += (float)$it['unit_price'] * (int)$it['qty'];
}
?>

<p class="cb-sub">
    Placed <?php echo date('M d, Y · h:i A', strtotime($order['order_date'])); ?> ·
    <?php echo htmlspecialchars($order['brand_name'] ?? 'Kitchen'); ?> ·
    <span class="cb-zone <?php echo $order['delivery_zone'] === 'Zone B' ? 'cb-zone-b' : 'cb-zone-a'; ?>"><?php echo htmlspecialchars($order['delivery_zone']); ?></span>
</p>

<section class="cb-card cb-track">
    <div class="cb-track-now">
        <span class="cb-status cb-status-<?php echo strtolower(str_replace(' ', '-', $currentStatus)); ?>"><?php echo htmlspecialchars($currentStatus); ?></span>
        <h2><?php echo htmlspecialchars($steps[$currentStatus] ?? $currentStatus); ?></h2>
    </div>

    <ol class="cb-track-steps">
        <?php $i = 0; foreach ($steps as $key => $label): ?>
            <?php $state = $i < $currentIndex ? 'is-done' : ($i === $currentIndex ? 'is-now' : ''); ?>
            <li class="<?php echo $state; ?>" <?php echo $i === $currentIndex ? 'aria-current="step"' : ''; ?>>
                <span class="cb-track-dot" aria-hidden="true"><i class="bi <?php echo $stepIcons[$key] ?? 'bi-circle'; ?>"></i></span>
                <span class="cb-track-label"><?php echo htmlspecialchars($key); ?></span>
            </li>
        <?php $i++; endforeach; ?>
    </ol>

    <?php if (is_admin() || $isOrderKitchen): ?>
        <div class="cb-track-staff">
            <?php if ($next): ?>
                <form action="<?php echo $ROOT_DIR; ?>/actions/update_order_status_action.php" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="order_id" value="<?php echo (int)$order['order_id']; ?>">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($next['status']); ?>">
                    <button type="submit" class="cb-btn"><?php echo htmlspecialchars($next['label']); ?></button>
                </form>
            <?php endif; ?>
            <form class="cb-inline-form" action="<?php echo $ROOT_DIR; ?>/actions/update_order_status_action.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="order_id" value="<?php echo (int)$order['order_id']; ?>">
                <label for="track-status" class="cb-muted">Or set</label>
                <select id="track-status" name="status">
                    <?php foreach ($steps as $key => $label): ?>
                        <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $currentStatus === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($key); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="cb-btn cb-btn-quiet">Update</button>
            </form>
        </div>
    <?php endif; ?>
</section>

<div class="cb-bill">
    <div>
        <section class="cb-card">
            <h2>Timeline</h2>
            <ol class="cb-timeline">
                <?php foreach (array_reverse($tracking) as $t): ?>
                    <li>
                        <time datetime="<?php echo htmlspecialchars(date('c', strtotime($t['updated_at']))); ?>"><?php echo date('M d · h:i A', strtotime($t['updated_at'])); ?></time>
                        <b><?php echo htmlspecialchars($t['status']); ?></b>
                        <?php if (!empty($t['status_message'])): ?><p><?php echo htmlspecialchars($t['status_message']); ?></p><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>

        <section class="cb-card">
            <h2>Delivery</h2>
            <dl class="cb-facts">
                <div><dt>Drop point</dt><dd><?php echo htmlspecialchars($order['drop_location']); ?></dd></div>
                <div><dt>For</dt><dd><?php echo htmlspecialchars($order['customer_name']); ?> · <?php echo htmlspecialchars($order['customer_contact']); ?></dd></div>
                <div>
                    <dt>Delivered by</dt>
                    <dd>
                        <b><?php echo htmlspecialchars($order['brand_name'] ?? 'the kitchen'); ?></b>
                        <?php if (!empty($order['kitchen_contact'])): ?>
                            <div class="cb-contact-pills">
                                <a href="<?php echo phone_tel_url($order['kitchen_contact']); ?>" class="cb-btn cb-btn-quiet">
                                    <i class="bi bi-telephone-fill" aria-hidden="true"></i> Call <?php echo htmlspecialchars($order['kitchen_contact']); ?>
                                </a>
                                <a href="<?php echo phone_wa_url($order['kitchen_contact'], 'Hi ' . ($order['brand_name'] ?? 'kitchen') . ', I am checking on my order #' . $order['invoice_no']); ?>" target="_blank" rel="noopener" class="cb-btn cb-btn-quiet">
                                    <i class="bi bi-whatsapp" aria-hidden="true"></i> WhatsApp
                                </a>
                            </div>
                        <?php endif; ?>
                    </dd>
                </div>
                <?php if (!empty($order['special_instructions'])): ?>
                    <div><dt>Note</dt><dd><?php echo htmlspecialchars($order['special_instructions']); ?></dd></div>
                <?php endif; ?>
            </dl>
            <?php if (can_order()): ?>
                <p class="cb-track-help"><a class="cb-link" href="<?php echo $ROOT_DIR; ?>/views/contact.php?order_id=<?php echo (int)$order['order_id']; ?>">Send a message to <?php echo htmlspecialchars($order['brand_name'] ?? 'the kitchen'); ?> ›</a></p>
            <?php endif; ?>
        </section>
    </div>

    <aside class="cb-card cb-bill-summary" aria-label="What was ordered">
        <h2>What you ordered</h2>
        <ul class="cb-checkout-items">
            <?php foreach ($items as $it): ?>
                <li>
                    <span class="cb-ticket-qty"><?php echo (int)$it['qty']; ?>×</span>
                    <span>
                        <?php echo htmlspecialchars($it['product_title']); ?>
                        <?php if (!empty($it['notes'])): ?><small><i class="bi bi-chat-left-text" aria-hidden="true"></i> <?php echo htmlspecialchars($it['notes']); ?></small><?php endif; ?>
                    </span>
                    <span class="cb-tray-price">GH₵ <?php echo number_format((float)$it['unit_price'] * (int)$it['qty'], 2); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="cb-totals">
            <div><span>Meals</span><span>GH₵ <?php echo number_format($mealTotal, 2); ?></span></div>
            <div><span>Delivery</span><span>GH₵ <?php echo number_format((float)$order['delivery_fee'], 2); ?></span></div>
            <div class="total"><span>Total</span><span>GH₵ <?php echo number_format($mealTotal + (float)$order['delivery_fee'], 2); ?></span></div>
        </div>
    </aside>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
