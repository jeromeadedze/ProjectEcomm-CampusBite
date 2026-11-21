<?php
// Admin: every order, newest first. Search by invoice or customer, filter by kitchen, dates and status, 25 per page.
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_admin();

require_once __DIR__ . '/../../controllers/OrderController.php';
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../controllers/CustomerController.php';

$result = (new OrderController())->searchOrdersForAdmin($_GET);
$f = $result['filters'];
$orders = $result['orders'];
$counts = $result['counts'];
$kitchens = (new ProductController())->getBrandsForAdmin();
// Opened from a customer's page: only that customer's orders
$forCustomer = $f['customer_id'] ? (new CustomerController())->getCustomerById($f['customer_id']) : null;

// The current filters as a query string, with some values changed (status chips and paging keep everything else)
$filterUrl = function ($changes) use ($f) {
    $query = array_merge([
        'q' => $f['q'],
        'brand' => $f['brand_id'] ? (string)$f['brand_id'] : '',
        'customer' => $f['customer_id'] ? (string)$f['customer_id'] : '',
        'from' => $f['from'],
        'to' => $f['to'],
        'status' => $f['status'],
    ], $changes);
    if (isset($query['page']) && (int)$query['page'] <= 1) {
        unset($query['page']);
    }
    $query = array_filter($query, function ($v) { return (string)$v !== ''; });
    return 'orders.php' . ($query ? '?' . htmlspecialchars(http_build_query($query)) : '');
};
$filtering = $f['q'] !== '' || $f['brand_id'] || $f['customer_id'] || $f['from'] !== '' || $f['to'] !== '';
$allCount = array_sum($counts);
$activeCount = $allCount - (int)($counts['Delivered'] ?? 0) - (int)($counts['Cancelled'] ?? 0);

require_once __DIR__ . '/../layout/header.php';
?>

<p class="cb-sub">
    <?php if ($filtering || $f['status'] !== ''): ?>
        <?php echo $result['total']; ?> matching order<?php echo $result['total'] === 1 ? '' : 's'; ?>.
    <?php else: ?>
        <?php echo $activeCount; ?> in progress · <?php echo $allCount; ?> total.
    <?php endif; ?>
    Each kitchen moves its own orders along from its kitchen portal; open an order to see its timeline.
</p>
<?php if ($f['customer_id']): ?>
    <p class="cb-sub">
        Showing orders by <a class="cb-link" href="customer.php?id=<?php echo (int)$f['customer_id']; ?>"><?php echo htmlspecialchars($forCustomer['customer_name'] ?? 'an unknown account'); ?></a>.
        <a class="cb-link" href="<?php echo $filterUrl(['customer' => '', 'page' => '']); ?>">Show everyone's orders</a>
    </p>
<?php endif; ?>

<form class="cb-card cb-money-range cb-order-filters" method="GET" action="orders.php" role="search">
    <label class="cb-order-search">Search
        <input type="search" name="q" value="<?php echo htmlspecialchars($f['q']); ?>" maxlength="100" placeholder="Invoice, customer name, email or phone">
    </label>
    <label>Kitchen
        <select name="brand">
            <option value="">All kitchens</option>
            <?php foreach ($kitchens as $k): ?>
                <option value="<?php echo (int)$k['brand_id']; ?>" <?php echo (int)$f['brand_id'] === (int)$k['brand_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($k['brand_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>From <input type="date" name="from" value="<?php echo htmlspecialchars($f['from']); ?>" max="<?php echo date('Y-m-d'); ?>"></label>
    <label>To <input type="date" name="to" value="<?php echo htmlspecialchars($f['to']); ?>" max="<?php echo date('Y-m-d'); ?>"></label>
    <?php if ($f['status'] !== ''): ?><input type="hidden" name="status" value="<?php echo htmlspecialchars($f['status']); ?>"><?php endif; ?>
    <?php if ($f['customer_id']): ?><input type="hidden" name="customer" value="<?php echo (int)$f['customer_id']; ?>"><?php endif; ?>
    <button type="submit" class="cb-btn">Search</button>
    <?php if ($filtering || $f['status'] !== ''): ?>
        <a class="cb-btn cb-btn-quiet" href="orders.php">Clear</a>
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
                    <th>Kitchen</th>
                    <th>Customer</th>
                    <th>Drop point</th>
                    <th class="num">Total</th>
                    <th>Status</th>
                    <th><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="7" class="cb-table-empty"><?php echo ($filtering || $f['status'] !== '') ? 'No orders match these filters.' : 'No orders yet.'; ?></td></tr>
                <?php endif; ?>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td class="cb-table-name">
                            #<?php echo htmlspecialchars($o['invoice_no']); ?>
                            <span class="cb-table-sub"><?php echo date('M d · h:i A', strtotime($o['order_date'])); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($o['brand_name'] ?? '–'); ?></td>
                        <td>
                            <a class="cb-link-quiet" href="customer.php?id=<?php echo (int)$o['customer_id']; ?>"><?php echo htmlspecialchars($o['customer_name']); ?></a>
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
                                <a href="<?php echo $ROOT_DIR; ?>/views/order_tracking.php?order_id=<?php echo (int)$o['order_id']; ?>">View ›</a>
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

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
