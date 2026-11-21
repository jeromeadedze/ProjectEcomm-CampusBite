<?php
// Admin home: how the whole platform is doing, every kitchen at a glance, and what needs attention
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/OrderController.php';
require_once __DIR__ . '/../../controllers/CustomerController.php';
require_once __DIR__ . '/../../controllers/SupportController.php';

require_admin();

const WAITING_MINUTES = 20; // a Placed order older than this hasn't been picked up by its kitchen

$orderCtrl = new OrderController();
$summary = $orderCtrl->getPlatformSummary();
$byStatus = $orderCtrl->countOrdersByStatus();
$kitchens = $orderCtrl->getKitchenOverview(date('Y-m-d 00:00:00', strtotime('-6 days')));
$waiting = $orderCtrl->getWaitingOrders(WAITING_MINUTES);
$recentOrders = array_slice($orderCtrl->getAllOrders(), 0, 6);

$accounts = (new CustomerController())->getAllCustomers();
$roleCount = array_count_values(array_map(function ($c) { return (int)$c['user_role']; }, $accounts));
$openMessages = count(array_filter((new SupportController())->getAllMessages(), function ($m) { return $m['status'] === 'Open'; }));

$noLogin = array_filter($kitchens, function ($k) { return (int)$k['logins'] === 0; });
$noMeals = array_filter($kitchens, function ($k) { return (int)$k['meals'] === 0; });

$pageHeading = 'CampusBite overview';
require_once __DIR__ . '/../layout/header.php';

$money = function ($amount) { return 'GH₵ ' . number_format($amount, 2); };
$plural = function ($n, $word) { return $n . ' ' . $word . ($n === 1 ? '' : 's'); };
$pipeline = ['Placed' => 'Waiting for kitchen', 'Preparing' => 'Preparing', 'Ready' => 'Ready to deliver', 'In Transit' => 'Out for delivery', 'Delivered' => 'Delivered'];
?>

<p class="cb-sub">
    <?php echo $plural(count($kitchens), 'kitchen'); ?> ·
    <?php echo $plural((int)($roleCount[2] ?? 0), 'customer'); ?> ·
    <?php echo $plural((int)($roleCount[4] ?? 0), 'kitchen login'); ?>
</p>

<div class="cb-kpis">
    <?php foreach (['today' => 'Today', 'week' => 'Last 7 days', 'all' => 'All time'] as $key => $label): ?>
        <section class="cb-card cb-kpi">
            <h2><?php echo $label; ?></h2>
            <p class="cb-kpi-num"><?php echo $money($summary[$key]['sales']); ?></p>
            <p class="cb-kpi-sub"><?php echo $plural($summary[$key]['orders'], 'order'); ?> · meal sales</p>
            <dl class="cb-kpi-split">
                <div><dt>Commission</dt><dd><?php echo $money($summary[$key]['commission']); ?></dd></div>
                <div><dt>Delivery fees (kitchens keep)</dt><dd><?php echo $money($summary[$key]['delivery']); ?></dd></div>
                <div class="is-total"><dt>CampusBite earns</dt><dd><?php echo $money($summary[$key]['earned']); ?></dd></div>
            </dl>
        </section>
    <?php endforeach; ?>
</div>

<?php
// Each issue: icon, headline, a muted detail line, and the place to fix it
$issues = [];
if ($waiting) {
    $n = count($waiting);
    $issues[] = ['bi-clock-history', $plural($n, 'order') . ($n === 1 ? ' is' : ' are') . ' still waiting for a kitchen',
        'Placed over ' . WAITING_MINUTES . ' minutes ago: ' . implode(', ', array_map(function ($w) use ($ROOT_DIR) {
            return '<a class="cb-link-quiet" href="' . $ROOT_DIR . '/views/order_tracking.php?order_id=' . (int)$w['order_id'] . '">#' . htmlspecialchars($w['invoice_no']) . '</a> (' . htmlspecialchars($w['brand_name'] ?? 'no kitchen') . ')';
        }, array_slice($waiting, 0, 3))) . ($n > 3 ? ' and ' . ($n - 3) . ' more' : ''),
        'View orders', 'orders.php?status=Placed'];
}
if ($noLogin) {
    $n = count($noLogin);
    $issues[] = ['bi-person-x', $plural($n, 'kitchen') . ($n === 1 ? ' has' : ' have') . ' no login',
        "Nobody there can receive orders: " . htmlspecialchars(implode(', ', array_column($noLogin, 'brand_name'))),
        'Create logins', 'brand.php#kitchen-logins'];
}
if ($noMeals) {
    $n = count($noMeals);
    $first = reset($noMeals);
    $issues[] = ['bi-journal-x', $plural($n, 'kitchen') . ($n === 1 ? ' has' : ' have') . ' an empty menu',
        htmlspecialchars(implode(', ', array_column($noMeals, 'brand_name'))) . ($n === 1 ? ' needs' : ' need') . ' to add meals from the kitchen portal',
        $n === 1 ? 'Open kitchen' : 'View kitchens', $n === 1 ? 'kitchen.php?id=' . (int)$first['brand_id'] : 'brand.php'];
}
if ($openMessages) {
    $issues[] = ['bi-chat-left-dots', $plural($openMessages, 'support message') . ($openMessages === 1 ? ' is' : ' are') . ' open',
        'Customers are waiting for a reply', 'Open Support', 'messages.php'];
}
?>
<?php if ($issues): ?>
    <section class="cb-card cb-attention" aria-labelledby="attention-title">
        <div class="cb-attention-head">
            <h2 id="attention-title">Needs attention</h2>
            <span class="cb-attention-count"><?php echo count($issues); ?></span>
        </div>
        <ul>
            <?php foreach ($issues as [$icon, $title, $detail, $label, $href]): ?>
                <li>
                    <span class="cb-attention-icon" aria-hidden="true"><i class="bi <?php echo $icon; ?>"></i></span>
                    <div class="cb-attention-text">
                        <b><?php echo $title; ?></b>
                        <span><?php echo $detail; ?></span>
                    </div>
                    <a class="cb-btn cb-btn-quiet" href="<?php echo $href; ?>"><?php echo $label; ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<div class="cb-row"><h2>Orders right now</h2><a class="cb-viewall" href="orders.php">All orders ›</a></div>
<div class="cb-queue">
    <?php foreach ($pipeline as $status => $label): ?>
        <a class="cb-card cb-queue-step" href="orders.php?status=<?php echo urlencode($status); ?>">
            <span class="cb-queue-num"><?php echo (int)($byStatus[$status] ?? 0); ?></span>
            <span class="cb-status cb-status-<?php echo strtolower(str_replace(' ', '-', $status)); ?>"><?php echo $label; ?></span>
        </a>
    <?php endforeach; ?>
</div>

<div class="cb-row"><h2>Kitchens</h2><a class="cb-viewall" href="brand.php">Manage kitchens ›</a></div>
<section class="cb-card">
    <div class="cb-table-wrap">
        <table class="cb-table">
            <thead>
                <tr>
                    <th>Kitchen</th>
                    <th>Status</th>
                    <th>Login</th>
                    <th class="num">Menu</th>
                    <th class="num">Active orders</th>
                    <th class="num">Orders (7 days)</th>
                    <th class="num">Sales (7 days)</th>
                    <th class="num">Commission (7 days)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($kitchens)): ?>
                    <tr><td colspan="8" class="cb-table-empty">No kitchens yet. <a class="cb-link" href="brand.php">Add the first one</a>.</td></tr>
                <?php endif; ?>
                <?php foreach ($kitchens as $k): ?>
                    <tr>
                        <td class="cb-table-name"><a href="kitchen.php?id=<?php echo (int)$k['brand_id']; ?>"><?php echo htmlspecialchars($k['brand_name']); ?></a><span class="cb-table-sub"><?php echo htmlspecialchars($k['zone']); ?> · <?php echo round($k['commission_rate'] * 100); ?>% commission</span></td>
                        <td><span class="cb-dot-status <?php echo $k['is_active'] ? '' : 'off'; ?>"><?php echo $k['is_active'] ? 'Open' : 'Paused'; ?></span></td>
                        <td><?php echo (int)$k['logins'] > 0 ? $plural((int)$k['logins'], 'login') : '<a class="cb-link" href="brand.php#kitchen-logins">Add login</a>'; ?></td>
                        <td class="num"><?php echo (int)$k['meals']; ?><?php if ((int)$k['sold_out'] > 0): ?><span class="cb-table-sub"><?php echo (int)$k['sold_out']; ?> sold out</span><?php endif; ?></td>
                        <td class="num"><?php echo (int)$k['active']; ?></td>
                        <td class="num"><?php echo (int)$k['orders']; ?></td>
                        <td class="num"><?php echo $money($k['sales']); ?></td>
                        <td class="num"><?php echo $money($k['commission']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="cb-row"><h2>Latest orders</h2><a class="cb-viewall" href="orders.php">All orders ›</a></div>
<section class="cb-card">
    <div class="cb-table-wrap">
        <table class="cb-table">
            <thead><tr><th>Invoice</th><th>Kitchen</th><th>Customer</th><th class="num">Total</th><th>Status</th></tr></thead>
            <tbody>
                <?php if (empty($recentOrders)): ?>
                    <tr><td colspan="5" class="cb-table-empty">No orders yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($recentOrders as $o): ?>
                    <tr>
                        <td class="cb-table-name"><a href="<?php echo $ROOT_DIR; ?>/views/order_tracking.php?order_id=<?php echo (int)$o['order_id']; ?>">#<?php echo htmlspecialchars($o['invoice_no']); ?></a><span class="cb-table-sub"><?php echo date('M d · h:i A', strtotime($o['order_date'])); ?></span></td>
                        <td><?php echo htmlspecialchars($o['brand_name'] ?? '–'); ?></td>
                        <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                        <td class="num"><?php echo $money($o['amt'] ?? ((float)$o['items_total'] + (float)$o['delivery_fee'])); ?></td>
                        <td><span class="cb-status cb-status-<?php echo strtolower(str_replace(' ', '-', $o['order_status'])); ?>"><?php echo htmlspecialchars($o['order_status']); ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
