<?php
// Admin: one kitchen at a glance. Its money for a date range, its menu and categories (read-only:
// kitchens manage their own meals in the kitchen portal), support messages and staff logins.
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_admin();

require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../controllers/OrderController.php';
require_once __DIR__ . '/../../controllers/SupportController.php';
require_once __DIR__ . '/../../controllers/CustomerController.php';
require_once __DIR__ . '/../../controllers/PayoutController.php';

$productCtrl = new ProductController();
$orderCtrl = new OrderController();
$kitchenId = (int)($_GET['id'] ?? 0);
$kitchen = $kitchenId > 0 ? $productCtrl->getBrandById($kitchenId) : null;
if (!$kitchen) {
    $_SESSION['error'] = "That kitchen doesn't exist.";
    header('Location: brand.php');
    exit;
}

// Date range for the money figures, the same presets as the Money page
$readDate = function ($key, $fallback) {
    $value = $_GET[$key] ?? '';
    $d = DateTime::createFromFormat('Y-m-d', $value);
    return ($d && $d->format('Y-m-d') === $value) ? $value : $fallback;
};
$from = $readDate('from', date('Y-m-01'));
$to = $readDate('to', date('Y-m-d'));
if ($from > $to) {
    [$from, $to] = [$to, $from];
}
$presets = [
    'This month' => [date('Y-m-01'), date('Y-m-d')],
    'Last 7 days' => [date('Y-m-d', strtotime('-6 days')), date('Y-m-d')],
    'All time' => ['2020-01-01', date('Y-m-d')],
];

$report = $orderCtrl->getMoneyReport($from, $to, [$kitchen], false);
$figures = null;
foreach ($report['kitchens'] as $row) {
    if ((int)$row['brand_id'] === $kitchenId) {
        $figures = $row;
    }
}

$payoutCtrl = new PayoutController();
$balance = $payoutCtrl->getBalance($kitchen);
$payouts = $payoutCtrl->getPayouts($kitchenId, 5);

$meals = $productCtrl->getProductsForKitchen($kitchenId);
$categories = [];
foreach ($meals as $meal) {
    $name = $meal['cat_name'];
    $categories[$name] = $categories[$name] ?? ['meals' => 0, 'available' => 0];
    $categories[$name]['meals']++;
    $categories[$name]['available'] += (int)$meal['is_available'];
}
ksort($categories);
$soldOut = count(array_filter($meals, function ($m) { return !(int)$m['is_available']; }));

$byStatus = $orderCtrl->countKitchenOrdersByStatus($kitchenId);
$bestSellers = $orderCtrl->getKitchenBestSellers($kitchenId, 5);
$messages = (new SupportController())->getMessagesByKitchen($kitchenId);
$openMessages = count(array_filter($messages, function ($m) { return $m['status'] === 'Open'; }));
$staff = array_filter((new CustomerController())->getKitchenAccounts(), function ($a) use ($kitchenId) {
    return (int)$a['brand_id'] === $kitchenId;
});

$pageHeading = $kitchen['brand_name'];
require_once __DIR__ . '/../layout/header.php';

$money = function ($amount) { return 'GH₵ ' . number_format((float)$amount, 2); };
$plural = function ($n, $word) { return (int)$n . ' ' . $word . ((int)$n === 1 ? '' : 's'); };
$range = function ($a, $b) use ($kitchenId) { return 'kitchen.php?id=' . $kitchenId . '&amp;from=' . urlencode($a) . '&amp;to=' . urlencode($b); };
$inProgress = array_sum(array_intersect_key($byStatus, array_flip(['Placed', 'Preparing', 'Ready', 'In Transit'])));
?>

<p class="cb-sub"><a class="cb-link" href="brand.php">‹ All kitchens</a></p>
<?php
$facts = [$kitchen['zone'], $kitchen['location_desc'] ?: 'No location set', $kitchen['brand_phone'] ?: 'No phone', round($kitchen['commission_rate'] * 100) . '% commission'];
?>
<p class="cb-sub">
    <span class="cb-dot-status <?php echo $kitchen['is_active'] ? '' : 'off'; ?>"><?php echo $kitchen['is_active'] ? 'Active' : (!empty($kitchen['admin_paused']) ? 'Paused by you: hidden from shoppers' : 'Paused by the kitchen: hidden from shoppers'); ?></span>
    · <?php echo htmlspecialchars(implode(' · ', $facts)); ?>
</p>

<div class="cb-chips cb-money-presets" role="group" aria-label="Date range">
    <?php foreach ($presets as $label => [$a, $b]): ?>
        <a class="cb-chip <?php echo ($a === $from && $b === $to) ? 'is-on' : ''; ?>" href="<?php echo $range($a, $b); ?>"><?php echo $label; ?></a>
    <?php endforeach; ?>
</div>

<div class="cb-kpis">
    <section class="cb-card cb-kpi">
        <h2>Meal sales</h2>
        <p class="cb-kpi-num"><?php echo $money($figures['sales'] ?? 0); ?></p>
        <p class="cb-kpi-sub"><?php echo $plural($figures['orders'] ?? 0, 'delivered order'); ?></p>
    </section>
    <section class="cb-card cb-kpi">
        <h2>CampusBite earns</h2>
        <p class="cb-kpi-num"><?php echo $money($figures['commission'] ?? 0); ?></p>
        <p class="cb-kpi-sub">Commission on its meal sales</p>
    </section>
    <section class="cb-card cb-kpi">
        <h2>Kitchen is owed</h2>
        <p class="cb-kpi-num"><?php echo $money($figures['owed'] ?? 0); ?></p>
        <p class="cb-kpi-sub">Includes <?php echo $money($figures['delivery'] ?? 0); ?> in delivery fees</p>
    </section>
    <section class="cb-card cb-kpi">
        <h2>Still in progress</h2>
        <p class="cb-kpi-num"><?php echo $money($figures['pending_value'] ?? 0); ?></p>
        <p class="cb-kpi-sub"><?php echo $plural($figures['pending_orders'] ?? 0, 'order'); ?>, not owed until delivered</p>
    </section>
</div>
<p class="cb-field-hint">Orders placed <?php echo date('M j, Y', strtotime($from)); ?> – <?php echo date('M j, Y', strtotime($to)); ?>. The same figures as this kitchen's row on the <a class="cb-link" href="money.php?from=<?php echo urlencode($from); ?>&amp;to=<?php echo urlencode($to); ?>">Money</a> page.</p>

<div class="cb-row">
    <h2>Payouts</h2>
    <?php if ($balance['balance'] > 0): ?><a class="cb-viewall" href="money.php?pay=<?php echo $kitchenId; ?>#record-payout">Record payout ›</a><?php endif; ?>
</div>
<section class="cb-card">
    <dl class="cb-kpi-split cb-money-check">
        <div><dt>Earned on delivered orders, all time</dt><dd><?php echo $money($balance['earned']); ?></dd></div>
        <div><dt>Paid out by CampusBite</dt><dd>− <?php echo $money($balance['paid']); ?></dd></div>
        <div class="is-total"><dt>Balance owed</dt><dd><?php echo $money($balance['balance']); ?></dd></div>
    </dl>
    <?php if (empty($payouts)): ?>
        <p class="cb-muted">Nothing paid out yet.</p>
    <?php else: ?>
        <ul class="cb-kitchen-best cb-payout-list">
            <?php foreach ($payouts as $p): ?>
                <li>
                    <span><?php echo date('M j, Y', strtotime($p['paid_on'])); ?> · <?php echo htmlspecialchars($p['method']); ?><?php echo $p['reference'] ? ' · ' . htmlspecialchars($p['reference']) : ''; ?></span>
                    <b><?php echo $money($p['amount']); ?></b>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<div class="cb-row"><h2>Categories</h2></div>
<?php if (empty($categories)): ?>
    <p class="cb-muted">No meals yet, so no categories.</p>
<?php else: ?>
    <ul class="cb-kitchen-cats">
        <?php foreach ($categories as $name => $c): ?>
            <li>
                <b><?php echo htmlspecialchars($name); ?></b>
                <span><?php echo $plural($c['meals'], 'meal'); ?><?php echo $c['available'] < $c['meals'] ? ' · ' . ($c['meals'] - $c['available']) . ' sold out' : ''; ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<div class="cb-row">
    <h2>Menu</h2>
    <span class="cb-muted"><?php echo $plural(count($meals), 'meal'); ?><?php echo $soldOut ? ' · ' . $soldOut . ' sold out' : ''; ?></span>
</div>
<section class="cb-card">
    <p class="cb-card-lead">The kitchen manages its own meals, prices and stock from its kitchen portal. This list is view-only.</p>
    <div class="cb-table-wrap">
        <table class="cb-table">
            <thead><tr><th>Meal</th><th>Category</th><th class="num">Price</th><th class="num">Prep</th><th class="num">Sold</th><th>Status</th></tr></thead>
            <tbody>
                <?php if (empty($meals)): ?>
                    <tr><td colspan="6" class="cb-table-empty">This kitchen hasn't added any meals yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($meals as $meal): ?>
                    <?php $available = (int)$meal['is_available'] === 1; ?>
                    <tr>
                        <td class="cb-table-name">
                            <?php if ($available && $kitchen['is_active']): ?>
                                <a href="<?php echo $ROOT_DIR; ?>/views/single_product.php?id=<?php echo (int)$meal['product_id']; ?>"><?php echo htmlspecialchars($meal['product_title']); ?></a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($meal['product_title']); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($meal['cat_name']); ?></td>
                        <td class="num"><?php echo $money($meal['product_price']); ?></td>
                        <td class="num"><?php echo (int)$meal['prep_time_minutes']; ?> min</td>
                        <td class="num"><?php echo (int)$meal['meals_sold']; ?></td>
                        <td><span class="cb-dot-status <?php echo $available ? '' : 'off'; ?>"><?php echo $available ? 'Available' : 'Sold out'; ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="cb-bill">
    <div>
        <div class="cb-row">
            <h2>Support messages</h2>
            <a class="cb-viewall" href="messages.php">All messages ›</a>
        </div>
        <section class="cb-card">
            <?php if (empty($messages)): ?>
                <p class="cb-muted">No messages from customers.</p>
            <?php else: ?>
                <p class="cb-card-lead"><?php echo $plural($openMessages, 'open message'); ?> of <?php echo count($messages); ?>. The kitchen answers these from its portal.</p>
                <ul class="cb-kitchen-msgs">
                    <?php foreach (array_slice($messages, 0, 4) as $m): ?>
                        <li>
                            <span class="cb-dot-status <?php echo $m['status'] === 'Open' ? '' : 'off'; ?>"><?php echo htmlspecialchars($m['status']); ?></span>
                            <b><?php echo htmlspecialchars($m['topic']); ?></b> from <?php echo htmlspecialchars($m['customer_name']); ?>
                            <?php if (!empty($m['invoice_no'])): ?> · #<?php echo htmlspecialchars($m['invoice_no']); ?><?php endif; ?>
                            <span class="cb-table-sub"><?php echo date('M d · h:i A', strtotime($m['created_at'])); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <aside>
        <div class="cb-row"><h2>Best sellers</h2></div>
        <section class="cb-card">
            <?php if (empty($bestSellers)): ?>
                <p class="cb-muted">Nothing sold yet.</p>
            <?php else: ?>
                <ol class="cb-kitchen-best">
                    <?php foreach ($bestSellers as $b): ?>
                        <li><span><?php echo htmlspecialchars($b['product_title']); ?></span><span class="cb-muted"><?php echo (int)$b['meals']; ?> sold</span></li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>

        <div class="cb-row"><h2>Staff logins</h2></div>
        <section class="cb-card">
            <?php if (empty($staff)): ?>
                <p class="cb-muted">No staff login yet. <a class="cb-link" href="users.php?kitchen=<?php echo $kitchenId; ?>#new-login">Create one</a> so the kitchen can manage its menu and orders.</p>
            <?php else: ?>
                <ul class="cb-kitchen-best">
                    <?php foreach ($staff as $s): ?>
                        <li><span><?php echo htmlspecialchars($s['customer_name']); ?></span><span class="cb-muted"><?php echo htmlspecialchars($s['customer_email']); ?></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </aside>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
