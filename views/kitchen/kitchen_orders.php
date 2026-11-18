<?php
// Kitchen portal: incoming orders by stage. The kitchen delivers its own orders and marks them delivered.
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../controllers/OrderController.php';

require_kitchen();

$orderCtrl = new OrderController();
$kitchenId = get_kitchen_id();
$kitchen = (new ProductController())->getBrandById($kitchenId);

$view = ($_GET['view'] ?? 'active') === 'done' ? 'done' : 'active';
$orders = $orderCtrl->getKitchenOrders($kitchenId, $view);

// Active orders are grouped by stage, oldest first within a stage so the longest wait is on top
$stages = ['Placed' => 'New orders', 'Preparing' => 'Preparing', 'Ready' => 'Ready to deliver', 'In Transit' => 'Out for delivery'];
$grouped = [];
foreach (array_reverse($orders) as $o) {
    $grouped[$o['order_status']][] = $o;
}

$pageHeading = $kitchen['brand_name'] . ' orders';
require_once __DIR__ . '/../layout/header.php';

$renderOrder = function ($o) use ($orderCtrl, $ROOT_DIR) {
    // Only this kitchen's meals (orders from before the one-kitchen rule can mix kitchens)
    $items = array_filter($orderCtrl->getOrderDetails($o['order_id']), function ($it) use ($o) {
        return (int)$it['product_brand'] === (int)$o['brand_id'];
    });
    $statusClass = strtolower(str_replace(' ', '-', $o['order_status']));
    ?>
    <article class="cb-card cb-ticket">
        <header class="cb-ticket-head">
            <div>
                <h3>#<?php echo htmlspecialchars($o['invoice_no']); ?></h3>
                <span class="cb-muted"><?php echo date('M d · h:i A', strtotime($o['order_date'])); ?></span>
            </div>
            <span class="cb-status cb-status-<?php echo $statusClass; ?>"><?php echo htmlspecialchars($o['order_status']); ?></span>
        </header>

        <div class="cb-ticket-who">
            <b><?php echo htmlspecialchars($o['customer_name']); ?></b> · <?php echo htmlspecialchars($o['customer_contact']); ?><br>
            <i class="bi bi-geo-alt" aria-hidden="true"></i> <?php echo htmlspecialchars($o['drop_location']); ?>
            <?php if (!empty($o['special_instructions'])): ?>
                <span class="cb-ticket-note"><i class="bi bi-info-circle" aria-hidden="true"></i> <?php echo htmlspecialchars($o['special_instructions']); ?></span>
            <?php endif; ?>
        </div>

        <ul class="cb-ticket-items">
            <?php foreach ($items as $it): ?>
                <li>
                    <span class="cb-ticket-qty"><?php echo (int)$it['qty']; ?>×</span>
                    <span>
                        <?php echo htmlspecialchars($it['product_title']); ?>
                        <?php if (!empty($it['notes'])): ?><small><i class="bi bi-chat-left-text" aria-hidden="true"></i> <?php echo htmlspecialchars($it['notes']); ?></small><?php endif; ?>
                    </span>
                    <span class="cb-ticket-price">GH₵ <?php echo number_format((float)$it['unit_price'] * (int)$it['qty'], 2); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="cb-ticket-total"><span>Meals total</span><b>GH₵ <?php echo number_format((float)$o['items_total'], 2); ?></b></p>

        <div class="cb-ticket-actions">
            <?php $next = OrderController::NEXT_STEP[$o['order_status']] ?? null; ?>
            <?php if ($next): ?>
                <form action="<?php echo $ROOT_DIR; ?>/actions/update_order_status_action.php" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="order_id" value="<?php echo (int)$o['order_id']; ?>">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($next['status']); ?>">
                    <button type="submit" class="cb-btn"><?php echo htmlspecialchars($next['label']); ?></button>
                </form>
            <?php endif; ?>
            <details class="cb-ticket-more">
            <summary>Set another status</summary>
            <form class="cb-inline-form" action="<?php echo $ROOT_DIR; ?>/actions/update_order_status_action.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="order_id" value="<?php echo (int)$o['order_id']; ?>">
                <label for="status-<?php echo (int)$o['order_id']; ?>">Status</label>
                <select id="status-<?php echo (int)$o['order_id']; ?>" name="status">
                    <?php foreach (OrderController::STATUSES as $statusValue => $statusLabel): ?>
                        <option value="<?php echo htmlspecialchars($statusValue); ?>" <?php echo $o['order_status'] === $statusValue ? 'selected' : ''; ?>><?php echo htmlspecialchars($statusValue); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="cb-btn cb-btn-quiet">Update</button>
            </form>
            </details>
        </div>
    </article>
    <?php
};
?>

<nav class="cb-admin-tabs" aria-label="Order views">
    <a href="kitchen_orders.php" <?php echo $view === 'active' ? 'class="is-active" aria-current="page"' : ''; ?>>In progress</a>
    <a href="kitchen_orders.php?view=done" <?php echo $view === 'done' ? 'class="is-active" aria-current="page"' : ''; ?>>Delivered</a>
</nav>

<p class="cb-field-hint">Your kitchen delivers its own orders. Press the button on each ticket to move it to the next stage, and mark it delivered once the student has it.</p>

<?php if (empty($orders)): ?>
    <div class="cb-card cb-bill-empty">
        <span class="cb-bill-empty-icon" aria-hidden="true"><i class="bi bi-receipt"></i></span>
        <h2><?php echo $view === 'active' ? 'No orders in progress' : 'No delivered orders yet'; ?></h2>
        <p><?php echo $view === 'active' ? 'New orders for your kitchen appear here as soon as a student checks out.' : 'Orders you mark as Delivered move here.'; ?></p>
    </div>
<?php elseif ($view === 'active'): ?>
    <?php foreach ($stages as $status => $label): ?>
        <?php if (empty($grouped[$status])) continue; ?>
        <div class="cb-row" id="status-<?php echo strtolower(str_replace(' ', '-', $status)); ?>"><h2><?php echo $label; ?> <span class="cb-muted">(<?php echo count($grouped[$status]); ?>)</span></h2></div>
        <div class="cb-tickets">
            <?php foreach ($grouped[$status] as $o) { $renderOrder($o); } ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="cb-tickets">
        <?php foreach ($orders as $o) { $renderOrder($o); } ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
