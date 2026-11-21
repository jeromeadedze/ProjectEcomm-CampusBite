<?php
// One order: where it is now, what happened so far, where it's going and what was in it.
// Open to the customer who placed it, admins (view only), and the kitchen it's from, the only one that changes its status.
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

$isCancelled = $order['order_status'] === 'Cancelled';
if ($isCancelled && !empty($order['refund_id'])) {
    $order['refund_status'] = $orderCtrl->refreshRefund($order);
}
// Which cancel form this person gets, if any
$cancelAs = null;
if (!$isCancelled) {
    if (is_admin()) {
        $cancelAs = 'admin';
    } elseif ($isOrderKitchen) {
        $cancelAs = 'kitchen';
    } elseif (can_order() && (int)$order['customer_id'] === (int)get_user_id()) {
        $cancelAs = 'customer';
    }
}
$canCancel = $cancelAs && $orderCtrl->canCancel($order, $cancelAs);
$cancelledBy = ['customer' => 'the customer', 'kitchen' => $order['brand_name'] ?? 'the kitchen', 'admin' => 'CampusBite'][$order['cancelled_by'] ?? ''] ?? 'CampusBite';
if (can_order() && $order['cancelled_by'] === 'customer') {
    $cancelledBy = 'you';
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

<?php if ($isCancelled): ?>
<section class="cb-card cb-track cb-cancelled">
    <div class="cb-track-now">
        <span class="cb-status cb-status-cancelled">Cancelled</span>
        <h2>This order was cancelled</h2>
    </div>
    <p>
        Cancelled by <?php echo htmlspecialchars($cancelledBy); ?>
        <?php if (!empty($order['cancelled_at'])): ?>on <?php echo date('M d · h:i A', strtotime($order['cancelled_at'])); ?><?php endif; ?><?php echo !empty($order['cancel_reason']) ? ': ' . htmlspecialchars($order['cancel_reason']) : '.'; ?>
    </p>
    <?php if (!empty($order['refund_id']) && $order['refund_status'] !== 'not_needed'): ?>
        <p class="cb-refund cb-refund-<?php echo htmlspecialchars($order['refund_status']); ?>">
            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
            <b><?php echo htmlspecialchars(OrderController::REFUND_LABELS[$order['refund_status']] ?? ucfirst($order['refund_status'])); ?></b>
            · GH₵ <?php echo number_format((float)$order['refund_amount'], 2); ?> back to the <?php echo htmlspecialchars(str_replace('Paystack - ', '', $order['payment_mode'] ?? 'Paystack')); ?> account that paid.
            <?php if (in_array($order['refund_status'], ['pending', 'processing'], true)): ?>Mobile Money refunds usually arrive within a day; cards can take a few days.<?php endif; ?>
            <?php if (in_array($order['refund_status'], ['failed', 'needs-attention'], true)): ?>CampusBite will sort this out<?php echo is_admin() ? ': check it in the Paystack dashboard' : ''; ?>.<?php endif; ?>
        </p>
    <?php else: ?>
        <p class="cb-muted">No payment was taken through Paystack for this order, so there's nothing to refund.</p>
    <?php endif; ?>
</section>
<?php else: ?>
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

    <?php if ($isOrderKitchen): ?>
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

    <?php if ($canCancel): ?>
        <details class="cb-cancel" id="cancel">
            <summary><?php echo $cancelAs === 'kitchen' ? 'Reject this order' : 'Cancel this order'; ?></summary>
            <form action="<?php echo $ROOT_DIR; ?>/actions/cancel_order_action.php" method="POST" data-confirm="<?php echo $cancelAs === 'kitchen' ? 'Reject' : 'Cancel'; ?> order #<?php echo htmlspecialchars($order['invoice_no']); ?>?<?php echo !empty($order['verified_at']) ? ' GH₵ ' . number_format((float)$order['amt'], 2) . ' will be refunded to the customer.' : ''; ?> This can't be undone.">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="order_id" value="<?php echo (int)$order['order_id']; ?>">
                <?php if ($cancelAs === 'kitchen'): ?>
                    <div class="cb-field">
                        <label for="cancel-reason">Why can't you make it?</label>
                        <select id="cancel-reason" name="reason" required>
                            <option value="" disabled selected>Choose a reason</option>
                            <?php foreach (OrderController::KITCHEN_REJECT_REASONS as $r): ?>
                                <option value="<?php echo htmlspecialchars($r); ?>"><?php echo htmlspecialchars($r); ?></option>
                            <?php endforeach; ?>
                            <option value="other">Something else</option>
                        </select>
                    </div>
                    <div class="cb-field">
                        <label for="cancel-other">Details <span class="cb-muted">(needed for "Something else")</span></label>
                        <input type="text" id="cancel-other" name="reason_other" maxlength="255">
                    </div>
                <?php else: ?>
                    <div class="cb-field">
                        <label for="cancel-reason"><?php echo $cancelAs === 'admin' ? 'Reason (the customer and kitchen will see it)' : 'Reason'; ?> <?php echo $cancelAs === 'customer' ? '<span class="cb-muted">(optional)</span>' : ''; ?></label>
                        <input type="text" id="cancel-reason" name="reason" maxlength="255" <?php echo $cancelAs === 'admin' ? 'required' : ''; ?> placeholder="<?php echo $cancelAs === 'admin' ? 'e.g. Kitchen closed unexpectedly' : 'e.g. Ordered by mistake'; ?>">
                    </div>
                <?php endif; ?>
                <p class="cb-field-hint">
                    <?php if (!empty($order['verified_at'])): ?>
                        The full GH₵ <?php echo number_format((float)$order['amt'], 2); ?> goes back to the <?php echo htmlspecialchars(str_replace('Paystack - ', '', $order['payment_mode'] ?? 'Paystack')); ?> account that paid, through Paystack.
                    <?php else: ?>
                        This order's payment wasn't taken through Paystack, so there's nothing to refund.
                    <?php endif; ?>
                </p>
                <button type="submit" class="cb-btn cb-btn-danger"><?php echo ($cancelAs === 'kitchen' ? 'Reject order' : 'Cancel order') . (!empty($order['verified_at']) ? ' and refund' : ''); ?></button>
            </form>
        </details>
    <?php elseif ($cancelAs === 'customer' && $currentStatus !== 'Delivered'): ?>
        <p class="cb-track-help cb-muted">The kitchen has started your order, so it can't be cancelled here. Message or call the kitchen below if something's wrong.</p>
    <?php endif; ?>
</section>
<?php endif; ?>

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
                                    <i class="bi bi-telephone" aria-hidden="true"></i> Call <?php echo htmlspecialchars($order['kitchen_contact']); ?>
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
            <?php if ($isCancelled && !empty($order['refund_amount']) && (float)$order['refund_amount'] > 0): ?>
                <div class="cb-refund-row"><span>Refunded</span><span>− GH₵ <?php echo number_format((float)$order['refund_amount'], 2); ?></span></div>
            <?php endif; ?>
        </div>
    </aside>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
