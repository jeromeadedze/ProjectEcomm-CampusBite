<?php
// Kitchen admin home: how the kitchen is selling. Money lives here only; staff never see this page.
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../controllers/OrderController.php';

require_kitchen_admin();

$range = (int)($_GET['range'] ?? 30) === 7 ? 7 : 30;

$productCtrl = new ProductController();
$orderCtrl = new OrderController();
$kitchenId = get_kitchen_id();
$kitchen = $productCtrl->getBrandById($kitchenId);
$commissionPercent = round($kitchen['commission_rate'] * 100);
$payoutLabel = ProductController::payoutLabel($kitchen);
$refunds = $orderCtrl->getKitchenRefundBalance($kitchenId);
$since = date('Y-m-d 00:00:00', strtotime('-' . ($range - 1) . ' days'));

// This period and the one before it, for "vs previous 30 days"
$trend = $orderCtrl->getKitchenTrend($kitchenId, $range * 2);
$previous = array_slice($trend['days'], 0, $range, true);
$current = array_slice($trend['days'], $range, $range, true);
$sum = function ($days) {
    $t = ['orders' => 0, 'cancelled' => 0, 'meals' => 0, 'sales' => 0.0, 'commission' => 0.0, 'delivery' => 0.0, 'earnings' => 0.0];
    foreach ($days as $d) { foreach ($t as $k => $_) { $t[$k] += $d[$k]; } }
    $t['avg'] = $t['orders'] ? $t['sales'] / $t['orders'] : 0.0;
    // Cancelled share of all orders placed
    $t['cancelRate'] = ($t['orders'] + $t['cancelled']) ? $t['cancelled'] / ($t['orders'] + $t['cancelled']) * 100 : 0.0;
    return $t;
};
$now = $sum($current);
$before = $sum($previous);
$hours = $orderCtrl->getKitchenTrend($kitchenId, $range)['hoursAll'];

$topDishes = $orderCtrl->getKitchenBestSellers($kitchenId, 6, $since);
$categories = $orderCtrl->getKitchenCategorySplit($kitchenId, $since);
$customerStats = $orderCtrl->getKitchenCustomerStats($kitchenId, $since);
$recentOrders = array_slice($orderCtrl->getKitchenOrders($kitchenId, 'all'), 0, 8);
$salesSummary = $orderCtrl->getKitchenSummary($kitchenId, $kitchen['commission_rate']);

$pageHeading = 'Sales';
require_once __DIR__ . '/../layout/header.php';

// ---- helpers ----
$money = function ($n) { return 'GH₵ ' . number_format($n, 2); };
$plural = function ($n, $w) { return number_format($n) . ' ' . $w . ($n == 1 ? '' : 's'); };
$hourLabel = function ($h) { return date('g A', mktime($h, 0, 0)); };
// $lowerIsBetter: true = a drop is good, false = a rise is good, null = neither (shown neutral)
$change = function ($now, $before, $lowerIsBetter = false) {
    if ($before <= 0) {
        return $now > 0 ? ['text' => 'new this period', 'tone' => 'flat', 'arrow' => ''] : ['text' => 'no change', 'tone' => 'flat', 'arrow' => ''];
    }
    $pct = ($now - $before) / $before * 100;
    if (abs($pct) < 0.5) {
        return ['text' => 'no change', 'tone' => 'flat', 'arrow' => '→'];
    }
    $tone = $lowerIsBetter === null ? 'flat' : ((($lowerIsBetter ? $pct < 0 : $pct > 0)) ? 'good' : 'bad');
    return ['text' => ($pct > 0 ? '+' : '−') . number_format(abs($pct), 1) . '%', 'tone' => $tone, 'arrow' => $pct > 0 ? '↑' : '↓'];
};

$kpis = [
    ['You earned', $money($now['earnings']), $change($now['earnings'], $before['earnings']), 'Paid to your account after ' . $commissionPercent . '% commission, delivery fees included', true],
    ['Meal sales', $money($now['sales']), $change($now['sales'], $before['sales']), $plural($now['meals'], 'meal') . ' sold', false],
    ['Orders', number_format($now['orders']), $change($now['orders'], $before['orders']), number_format($now['orders'] / $range, 1) . ' a day on average', false],
    ['Average order', $money($now['avg']), $change($now['avg'], $before['avg']), 'Meal value per order', false],
    ['CampusBite commission', $money($now['commission']), $change($now['commission'], $before['commission'], null), $commissionPercent . '% of meal sales', false],
    ['Cancelled', number_format($now['cancelled']), $change($now['cancelRate'], $before['cancelRate'], true), number_format($now['cancelRate'], 1) . '% of orders, refunded in full', false],
];

$chartRows = [];
$i = 0;
foreach ($current as $date => $d) {
    $chartRows[] = ['label' => date('D j M', strtotime($date)), 'short' => date($range === 7 ? 'D' : 'j M', strtotime($date)),
                    'tick' => $range === 7 || $i % 5 === 0 || $i === $range - 1, 'orders' => $d['orders'], 'earnings' => round($d['earnings'], 2),
                    'highlight' => $date === date('Y-m-d')];
    $i++;
}
$hourRows = [];
foreach (range(6, 23) as $h) {
    $hourRows[] = ['label' => $hourLabel($h) . ' – ' . $hourLabel(($h + 1) % 24), 'short' => $hourLabel($h), 'tick' => $h % 3 === 0, 'orders' => $hours[$h]];
}
$peak = max($hours) > 0 ? array_search(max($hours), $hours, true) : null;
$catMax = $categories ? max(array_map('floatval', array_column($categories, 'sales'))) : 0;
?>

<div class="ka-pagehead">
    <p class="cb-sub ka-sub">
        <?php echo htmlspecialchars($kitchen['brand_name']); ?> ·
        <span class="cb-dot-status <?php echo $kitchen['is_active'] ? '' : 'off'; ?>"><?php echo $kitchen['is_active'] ? 'Open to students' : 'Paused'; ?></span>
        · <?php echo date('j M', strtotime($since)); ?> – <?php echo date('j M Y'); ?>
    </p>
    <nav class="ka-seg" aria-label="Period">
        <a href="kitchen_sales.php?range=7" <?php echo $range === 7 ? 'aria-current="page"' : ''; ?>>Last 7 days</a>
        <a href="kitchen_sales.php?range=30" <?php echo $range === 30 ? 'aria-current="page"' : ''; ?>>Last 30 days</a>
    </nav>
</div>

<?php if (!$payoutLabel): ?>
    <p class="ka-alert"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> Students can't pay you until you add a payout account. <a href="kitchen_info.php#payout">Add it under Kitchen info ›</a></p>
<?php endif; ?>

<div class="ka-kpis">
    <?php foreach ($kpis as [$label, $value, $cmp, $note, $lead]): ?>
        <section class="ka-kpi<?php echo $lead ? ' is-lead' : ''; ?>">
            <h2><?php echo $label; ?></h2>
            <p class="ka-kpi-num"><?php echo $value; ?></p>
            <p class="ka-kpi-foot">
                <span class="ka-change ka-<?php echo $cmp['tone']; ?>"><?php echo $cmp['arrow'] ? '<span aria-hidden="true">' . $cmp['arrow'] . '</span> ' : ''; ?><?php echo $cmp['text']; ?></span>
                <span class="ka-vs">vs previous <?php echo $range; ?> days</span>
            </p>
            <p class="ka-kpi-note"><?php echo htmlspecialchars($note); ?></p>
        </section>
    <?php endforeach; ?>
</div>

<div class="ka-grid ka-grid-wide">
    <section class="cb-card ka-card" aria-labelledby="trend-title">
        <div class="ka-card-head">
            <div>
                <h2 id="trend-title">Daily sales</h2>
                <p class="ka-card-sub" id="trend-sum"></p>
            </div>
            <div class="ka-seg ka-seg-sm" role="group" aria-label="Show">
                <button type="button" data-metric="earnings" aria-pressed="true">Earnings</button>
                <button type="button" data-metric="orders" aria-pressed="false">Orders</button>
            </div>
        </div>
        <div class="ka-chart" id="trend-chart"></div>
    </section>

    <section class="cb-card ka-card" aria-labelledby="top-title">
        <div class="ka-card-head"><h2 id="top-title">Top dishes</h2><a class="cb-link ka-link" href="kitchen_menu.php">Menu ›</a></div>
        <?php if (!$topDishes): ?>
            <p class="ka-empty">No sales in this period yet.</p>
        <?php else: ?>
            <ol class="ka-dishes">
                <?php foreach ($topDishes as $i => $dish): ?>
                    <?php $img = !empty($dish['product_image']) && is_file(__DIR__ . '/../../images/products/' . $dish['product_image']) ? $ROOT_DIR . '/images/products/' . rawurlencode($dish['product_image']) : null; ?>
                    <li>
                        <span class="ka-dish-img"><?php if ($img): ?><img src="<?php echo htmlspecialchars($img); ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-egg-fried" aria-hidden="true"></i><?php endif; ?><b><?php echo $i + 1; ?></b></span>
                        <span class="ka-dish-name"><b><?php echo htmlspecialchars($dish['product_title']); ?></b><small><?php echo number_format((int)$dish['meals']); ?> sold</small></span>
                        <span class="ka-dish-money"><?php echo $money($dish['sales']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>
</div>

<div class="ka-grid ka-grid-three">
    <section class="cb-card ka-card" aria-labelledby="cat-title">
        <div class="ka-card-head"><h2 id="cat-title">Sales by category</h2></div>
        <?php if (!$categories): ?>
            <p class="ka-empty">No sales in this period yet.</p>
        <?php else: ?>
            <ul class="ka-hbars">
                <?php foreach ($categories as $cat): ?>
                    <li>
                        <span class="ka-hbar-label"><?php echo htmlspecialchars($cat['cat_name']); ?></span>
                        <span class="ka-hbar-value"><?php echo $money($cat['sales']); ?></span>
                        <span class="ka-hbar-track" title="<?php echo (int)$cat['meals']; ?> meals"><span style="width:<?php echo max(2, round((float)$cat['sales'] / $catMax * 100)); ?>%"></span></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="cb-card ka-card" aria-labelledby="hours-title">
        <div class="ka-card-head">
            <div>
                <h2 id="hours-title">Busiest hours</h2>
                <p class="ka-card-sub"><?php echo $peak === null ? 'Not enough orders yet' : 'Peak ' . $hourLabel($peak) . ' – ' . $hourLabel(($peak + 1) % 24) . '. Plan staff around it.'; ?></p>
            </div>
        </div>
        <div class="ka-chart" id="hours-chart"></div>
    </section>

    <section class="cb-card ka-card" aria-labelledby="cust-title">
        <div class="ka-card-head"><h2 id="cust-title">Customers</h2><a class="cb-link ka-link" href="kitchen_customers.php">All ›</a></div>
        <dl class="ka-facts">
            <div><dt>Ordered from you</dt><dd><?php echo number_format($customerStats['customers']); ?></dd></div>
            <div><dt>Came back for more</dt><dd><?php echo number_format($customerStats['returners']); ?><small><?php echo $customerStats['customers'] ? round($customerStats['returners'] / $customerStats['customers'] * 100) . '% of customers' : ''; ?></small></dd></div>
            <div><dt>New in this period</dt><dd><?php echo number_format($customerStats['new_recently']); ?></dd></div>
        </dl>
    </section>
</div>

<div class="ka-grid ka-grid-wide">
    <section class="cb-card ka-card" aria-labelledby="recent-title">
        <div class="ka-card-head"><h2 id="recent-title">Recent orders</h2><span class="ka-card-sub">Your staff handle these</span></div>
        <div class="cb-table-wrap">
            <table class="cb-table">
                <thead><tr><th>Order</th><th>Customer</th><th class="num">Meals</th><th class="num">Total</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (!$recentOrders): ?>
                        <tr><td colspan="5" class="cb-table-empty">No orders yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recentOrders as $o): ?>
                        <tr>
                            <td class="cb-table-name"><a href="<?php echo $ROOT_DIR; ?>/views/order_tracking.php?order_id=<?php echo (int)$o['order_id']; ?>">#<?php echo htmlspecialchars($o['invoice_no']); ?></a><span class="cb-table-sub"><?php echo date('j M · g:i A', strtotime($o['order_date'])); ?></span></td>
                            <td><?php echo htmlspecialchars($o['customer_name']); ?><span class="cb-table-sub"><?php echo htmlspecialchars($o['drop_location']); ?></span></td>
                            <td class="num"><?php echo (int)$o['item_count']; ?></td>
                            <td class="num"><?php echo $money((float)$o['items_total'] + (float)$o['delivery_fee']); ?></td>
                            <td><span class="cb-status cb-status-<?php echo strtolower(str_replace(' ', '-', $o['order_status'])); ?>"><?php echo htmlspecialchars($o['order_status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="cb-card ka-card" aria-labelledby="pay-title">
        <div class="ka-card-head"><h2 id="pay-title">Payouts</h2><a class="cb-link ka-link" href="kitchen_info.php#payout"><?php echo $payoutLabel ? 'Change' : 'Add account'; ?> ›</a></div>
        <?php if ($payoutLabel): ?>
            <p class="ka-pay-to"><span>Paid to</span><strong><?php echo htmlspecialchars($payoutLabel); ?></strong></p>
        <?php endif; ?>
        <dl class="ka-facts">
            <div><dt>All-time earnings</dt><dd><?php echo $money($salesSummary['all']['payout']); ?></dd></div>
            <div><dt>Each order you get</dt><dd>Meals − <?php echo $commissionPercent; ?>% + delivery</dd></div>
            <div><dt>Refunds still to take back</dt><dd class="<?php echo $refunds['owed'] > 0 ? 'ka-bad' : ''; ?>"><?php echo $money(max(0, $refunds['owed'])); ?></dd></div>
        </dl>
        <p class="ka-fine">Paystack pays your share of each order straight to your account, usually the next working day.</p>
    </section>
</div>

<script src="<?php echo $ROOT_DIR; ?>/js/kitchen-charts.js?v=<?php echo filemtime(__DIR__ . '/../../js/kitchen-charts.js'); ?>"></script>
<script>
(function () {
    var rows = <?php echo json_encode($chartRows, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
    var hourRows = <?php echo json_encode($hourRows, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
    var trend = document.getElementById('trend-chart'), sum = document.getElementById('trend-sum');
    var detail = function (r) { return '<span>' + r.orders + ' order' + (r.orders === 1 ? '' : 's') + '</span><span>' + CBCharts.money(r.earnings) + ' earned</span>'; };
    function draw(metric) {
        CBCharts.bars(trend, rows, { value: metric, money: metric === 'earnings', label: 'Daily ' + metric, detail: detail, maxBar: 30 });
        var total = rows.reduce(function (t, r) { return t + r[metric]; }, 0);
        sum.textContent = metric === 'earnings' ? CBCharts.money(total) + ' earned in <?php echo $range; ?> days' : total.toLocaleString() + ' orders in <?php echo $range; ?> days';
    }
    document.querySelectorAll('[data-metric]').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('[data-metric]').forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
            draw(b.dataset.metric);
        });
    });
    draw('earnings');
    CBCharts.bars(document.getElementById('hours-chart'), hourRows, { value: 'orders', label: 'Orders by hour of day', height: 180, maxBar: 16,
        detail: function (r) { return '<span>' + r.orders + ' orders in <?php echo $range; ?> days</span>'; } });
})();
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
