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

// In progress: tickets to work through. All orders: every order, searchable (?view=done is the old name for it).
$view = in_array($_GET['view'] ?? '', ['all', 'done'], true) ? 'all' : 'active';
if ($view === 'all') {
    $result = $orderCtrl->searchKitchenOrders($kitchenId, $_GET);
    $f = $result['filters'];
    $orders = $result['orders'];
    $counts = $result['counts'];
    $allCount = array_sum($counts);
    $filtering = $f['q'] !== '' || $f['from'] !== '' || $f['to'] !== '';
    // The current filters as a query string, with some values changed (status chips and paging keep the rest)
    $filterUrl = function ($changes) use ($f) {
        $query = array_merge(['view' => 'all', 'q' => $f['q'], 'from' => $f['from'], 'to' => $f['to'], 'status' => $f['status']], $changes);
        if (isset($query['page']) && (int)$query['page'] <= 1) {
            unset($query['page']);
        }
        $query = array_filter($query, function ($v) { return (string)$v !== ''; });
        return 'kitchen_orders.php?' . htmlspecialchars(http_build_query($query));
    };
} else {
    $orders = $orderCtrl->getKitchenOrders($kitchenId, 'active');
}

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

        <?php if ($o['order_status'] === 'Cancelled'): ?>
        <p class="cb-ticket-cancelled"><span class="cb-status cb-status-cancelled">Cancelled</span> <?php echo htmlspecialchars($o['cancel_reason'] ?? ''); ?></p>
        <?php else: ?>
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
            <?php if ($orderCtrl->canCancel($o, 'kitchen')): ?>
                <a class="cb-link cb-ticket-reject" href="<?php echo $ROOT_DIR; ?>/views/order_tracking.php?order_id=<?php echo (int)$o['order_id']; ?>#cancel">Can't make it? Reject and refund ›</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </article>
    <?php
};
?>

<nav class="cb-admin-tabs" aria-label="Order views">
    <a href="kitchen_orders.php" <?php echo $view === 'active' ? 'class="is-active" aria-current="page"' : ''; ?>>In progress</a>
    <a href="kitchen_orders.php?view=all" <?php echo $view === 'all' ? 'class="is-active" aria-current="page"' : ''; ?>>All orders</a>
</nav>

<?php if ($view === 'all'): ?>
    <p class="cb-sub">
        <?php echo ($filtering || $f['status'] !== '') ? $result['total'] . ' matching order' . ($result['total'] === 1 ? '' : 's') . '.' : $allCount . ' order' . ($allCount === 1 ? '' : 's') . ' in all.'; ?>
        Open an order to see its timeline, change its status or reject it.
    </p>

    <form class="cb-card cb-money-range cb-order-filters" method="GET" action="kitchen_orders.php" role="search">
        <input type="hidden" name="view" value="all">
        <label class="cb-order-search">Search by invoice, name, email or phone
            <input type="search" name="q" value="<?php echo htmlspecialchars($f['q']); ?>" maxlength="100" placeholder="e.g. 900101 or Ama Mensah">
        </label>
        <label>From <input type="date" name="from" value="<?php echo htmlspecialchars($f['from']); ?>" max="<?php echo date('Y-m-d'); ?>"></label>
        <label>To <input type="date" name="to" value="<?php echo htmlspecialchars($f['to']); ?>" max="<?php echo date('Y-m-d'); ?>"></label>
        <?php if ($f['status'] !== ''): ?><input type="hidden" name="status" value="<?php echo htmlspecialchars($f['status']); ?>"><?php endif; ?>
        <button type="submit" class="cb-btn">Search</button>
        <?php if ($filtering || $f['status'] !== ''): ?>
            <a class="cb-btn cb-btn-quiet" href="kitchen_orders.php?view=all">Clear</a>
        <?php endif; ?>
    </form>

    <div class="cb-chips" role="group" aria-label="Filter by status">
        <a class="cb-chip <?php echo $f['status'] === '' ? 'is-on' : ''; ?>" href="<?php echo $filterUrl(['status' => '', 'page' => '']); ?>" <?php echo $f['status'] === '' ? 'aria-current="page"' : ''; ?>>All · <?php echo $allCount; ?></a>
        <?php foreach (array_merge(array_keys(OrderController::STATUSES), ['Cancelled']) as $st): ?>
            <a class="cb-chip <?php echo $f['status'] === $st ? 'is-on' : ''; ?>" href="<?php echo $filterUrl(['status' => $st, 'page' => '']); ?>" <?php echo $f['status'] === $st ? 'aria-current="page"' : ''; ?>><?php echo htmlspecialchars($st); ?> · <?php echo (int)($counts[$st] ?? 0); ?></a>
        <?php endforeach; ?>
    </div>

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
                        <th><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="6" class="cb-table-empty">
                            <?php if ($filtering || $f['status'] !== ''): ?>
                                No orders match these filters. <a class="cb-link" href="kitchen_orders.php?view=all">Clear filters</a>
                            <?php else: ?>
                                No orders yet. They appear here as soon as a student checks out.
                            <?php endif; ?>
                        </td></tr>
                    <?php endif; ?>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td class="cb-table-name">
                                #<?php echo htmlspecialchars($o['invoice_no']); ?>
                                <span class="cb-table-sub"><?php echo date('M d · h:i A', strtotime($o['order_date'])); ?></span>
                            </td>
                            <td>
                                <a class="cb-link-quiet" href="kitchen_customers.php?id=<?php echo (int)$o['customer_id']; ?>"><?php echo htmlspecialchars($o['customer_name']); ?></a>
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
                                <div class="cb-table-actions">
                                    <a href="<?php echo $ROOT_DIR; ?>/views/order_tracking.php?order_id=<?php echo (int)$o['order_id']; ?>" aria-label="Open order #<?php echo htmlspecialchars($o['invoice_no']); ?>">Open ›</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php if ($result['pages'] > 1): ?>
        <nav class="cb-pager" aria-label="Pages">
            <?php if ($result['page'] > 1): ?><a href="<?php echo $filterUrl(['page' => $result['page'] - 1]); ?>">‹ Newer</a><?php endif; ?>
            <span>Page <?php echo $result['page']; ?> of <?php echo $result['pages']; ?></span>
            <?php if ($result['page'] < $result['pages']): ?><a href="<?php echo $filterUrl(['page' => $result['page'] + 1]); ?>">Older ›</a><?php endif; ?>
        </nav>
    <?php endif; ?>

<?php else: ?>
<p class="cb-field-hint">Your kitchen delivers its own orders. Press the button on each ticket to move it to the next stage, and mark it delivered once the student has it.</p>

<?php if (empty($orders)): ?>
    <div class="cb-card cb-bill-empty">
        <span class="cb-bill-empty-icon" aria-hidden="true"><i class="bi bi-receipt"></i></span>
        <h2>No orders in progress</h2>
        <p>New orders for your kitchen appear here as soon as a student checks out. Delivered and cancelled orders are under <a class="cb-link" href="kitchen_orders.php?view=all">All orders</a>.</p>
    </div>
<?php else: ?>
    <?php foreach ($stages as $status => $label): ?>
        <?php if (empty($grouped[$status])) continue; ?>
        <div class="cb-row" id="status-<?php echo strtolower(str_replace(' ', '-', $status)); ?>"><h2><?php echo $label; ?> <span class="cb-muted">(<?php echo count($grouped[$status]); ?>)</span></h2></div>
        <div class="cb-tickets">
            <?php foreach ($grouped[$status] as $o) { $renderOrder($o); } ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
