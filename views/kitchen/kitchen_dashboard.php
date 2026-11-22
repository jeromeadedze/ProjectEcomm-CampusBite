<?php
// Kitchen staff home: what needs doing on this shift. No money anywhere on this page.
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../controllers/OrderController.php';
require_once __DIR__ . '/../../controllers/SupportController.php';

require_kitchen_staff();

const LATE_AFTER_MINUTES = 15; // a new order waiting longer than this is flagged

$productCtrl = new ProductController();
$orderCtrl = new OrderController();
$kitchenId = get_kitchen_id();
$kitchen = $productCtrl->getBrandById($kitchenId);
$adminPaused = (int)($kitchen['admin_paused'] ?? 0) === 1;

// The queue, oldest first so the longest wait is on top ($queue, not $active: nav.php uses $active)
$queue = array_reverse($orderCtrl->getKitchenOrders($kitchenId, 'active'));
$byStatus = ['Placed' => [], 'Preparing' => [], 'Ready' => [], 'In Transit' => []];
foreach ($queue as $o) {
    $byStatus[$o['order_status']][] = $o;
}
$minutesWaiting = function ($o) { return (int)floor((time() - strtotime($o['order_date'])) / 60); };
$oldestNew = $byStatus['Placed'] ? $minutesWaiting($byStatus['Placed'][0]) : null;

$meals = $productCtrl->getProductsForKitchen($kitchenId);
$soldOut = array_values(array_filter($meals, function ($m) { return !(int)$m['is_available']; }));
$needReply = count(array_filter((new SupportController())->getMessagesByKitchen($kitchenId), function ($m) {
    return SupportController::waitingOn($m) === 'staff';
}));

$today = $orderCtrl->getKitchenTrend($kitchenId, 1);
$todayRow = reset($today['days']);
$activeToday = count(array_filter($queue, function ($o) { return substr($o['order_date'], 0, 10) === date('Y-m-d'); }));
$deliveredToday = $todayRow['orders'] - $activeToday; // today's orders that have left the queue
$sellingToday = $orderCtrl->getKitchenBestSellers($kitchenId, 5, date('Y-m-d 00:00:00'));

$pageHeading = $kitchen['brand_name'];
$liveRefresh = true; // orders and messages change on their own: the page updates itself every 5 s
require_once __DIR__ . '/../layout/header.php';

$plural = function ($n, $w) { return $n . ' ' . $w . ($n === 1 ? '' : 's'); };
$waitText = function ($m) use ($plural) {
    if ($m < 1) { return 'just now'; }
    if ($m < 60) { return $m . ' min'; }
    if ($m < 1440) { return floor($m / 60) . ' h ' . ($m % 60) . ' min'; }
    return $plural((int)floor($m / 1440), 'day');
};
$hourLabel = function ($h) { return date('g A', mktime($h, 0, 0)); };
$nowHour = (int)date('G');
$hourRows = [];
foreach (range(6, 23) as $h) {
    $hourRows[] = ['label' => $hourLabel($h) . ' – ' . $hourLabel(($h + 1) % 24), 'short' => $hourLabel($h), 'tick' => $h % 3 === 0,
                   'orders' => $today['hoursToday'][$h]['orders'], 'highlight' => $h === $nowHour];
}
$statusLabel = ['Placed' => 'New', 'Preparing' => 'Preparing', 'Ready' => 'Ready', 'In Transit' => 'Out for delivery'];
?>

<p class="cb-sub ka-sub">
    <?php echo date('l j F'); ?> ·
    <span class="cb-dot-status <?php echo $kitchen['is_active'] ? '' : 'off'; ?>"><?php echo $kitchen['is_active'] ? 'Taking orders' : ($adminPaused ? 'Paused by CampusBite' : 'Paused'); ?></span>
</p>

<section class="ka-now" aria-label="Right now">
    <a class="ka-now-tile <?php echo $oldestNew !== null && $oldestNew > LATE_AFTER_MINUTES ? 'is-late' : ''; ?>" href="kitchen_orders.php#status-placed">
        <span class="ka-now-label">New orders</span>
        <span class="ka-now-num"><?php echo count($byStatus['Placed']); ?></span>
        <span class="ka-now-sub"><?php echo $oldestNew === null ? 'Nothing waiting' : 'Oldest waiting ' . $waitText($oldestNew) . ($oldestNew > LATE_AFTER_MINUTES ? ' · late' : ''); ?></span>
    </a>
    <a class="ka-now-tile" href="kitchen_orders.php">
        <span class="ka-now-label">In progress</span>
        <span class="ka-now-num"><?php echo count($byStatus['Preparing']) + count($byStatus['Ready']) + count($byStatus['In Transit']); ?></span>
        <span class="ka-now-sub"><?php echo count($byStatus['Preparing']); ?> preparing · <?php echo count($byStatus['Ready']); ?> ready · <?php echo count($byStatus['In Transit']); ?> out</span>
    </a>
    <form class="ka-now-tile ka-now-switch" action="<?php echo $ROOT_DIR; ?>/actions/kitchen_toggle_open_action.php" method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="open" value="<?php echo $kitchen['is_active'] ? '0' : '1'; ?>">
        <span class="ka-now-label">Kitchen</span>
        <span class="ka-now-row">
            <span class="ka-now-state"><?php echo $kitchen['is_active'] ? 'Open' : 'Paused'; ?></span>
            <?php if (!$adminPaused): ?>
                <button type="submit" class="ka-switch <?php echo $kitchen['is_active'] ? 'is-on' : ''; ?>" role="switch" aria-checked="<?php echo $kitchen['is_active'] ? 'true' : 'false'; ?>" aria-label="<?php echo $kitchen['is_active'] ? 'Pause the kitchen' : 'Open the kitchen'; ?>"><span></span></button>
            <?php endif; ?>
        </span>
        <span class="ka-now-sub"><?php echo $adminPaused ? 'CampusBite paused it' : ($kitchen['is_active'] ? 'Students can order' : 'Hidden from students'); ?></span>
    </form>
    <?php if ($soldOut): ?>
        <details class="ka-now-tile ka-now-soldout">
            <summary>
                <span class="ka-now-label">Sold out</span>
                <span class="ka-now-num"><?php echo count($soldOut); ?></span>
                <span class="ka-now-sub">Show meals</span>
            </summary>
            <ul>
                <?php foreach ($soldOut as $m): ?>
                    <li>
                        <span><?php echo htmlspecialchars($m['product_title']); ?></span>
                        <form action="<?php echo $ROOT_DIR; ?>/actions/kitchen_toggle_meal_action.php" method="POST">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="product_id" value="<?php echo (int)$m['product_id']; ?>">
                            <button type="submit" class="cb-btn cb-btn-quiet ka-btn-sm">Back in stock</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        </details>
    <?php else: ?>
        <a class="ka-now-tile" href="kitchen_menu.php">
            <span class="ka-now-label">Menu</span>
            <span class="ka-now-num ka-now-ok"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
            <span class="ka-now-sub">All <?php echo count($meals); ?> meals available</span>
        </a>
    <?php endif; ?>
    <a class="ka-now-tile <?php echo $needReply ? 'is-attention' : ''; ?>" href="kitchen_messages.php">
        <span class="ka-now-label">Messages</span>
        <span class="ka-now-num"><?php echo $needReply; ?></span>
        <span class="ka-now-sub"><?php echo $needReply ? ($needReply === 1 ? 'needs a reply' : 'need a reply') : 'All answered'; ?></span>
    </a>
</section>

<div class="ka-grid ka-grid-wide">
    <section class="cb-card ka-card" aria-labelledby="queue-title">
        <div class="ka-card-head">
            <div><h2 id="queue-title">Order queue</h2><p class="ka-card-sub">Oldest first. Open an order to move it along.</p></div>
            <a class="cb-btn cb-btn-quiet ka-btn-sm" href="kitchen_orders.php">All orders</a>
        </div>
        <?php if (!$queue): ?>
            <div class="ka-empty ka-empty-big"><i class="bi bi-cup-hot" aria-hidden="true"></i><b>You're all caught up</b><span>New orders appear here the moment a student pays.</span></div>
        <?php else: ?>
            <ul class="ka-queue">
                <?php foreach (array_slice($queue, 0, 8) as $o): ?>
                    <?php $wait = $minutesWaiting($o); $late = $o['order_status'] === 'Placed' && $wait > LATE_AFTER_MINUTES; ?>
                    <li class="<?php echo $late ? 'is-late' : ''; ?>">
                        <a href="<?php echo $ROOT_DIR; ?>/views/order_tracking.php?order_id=<?php echo (int)$o['order_id']; ?>">
                            <span class="cb-status cb-status-<?php echo strtolower(str_replace(' ', '-', $o['order_status'])); ?>"><?php echo $statusLabel[$o['order_status']]; ?></span>
                            <span class="ka-queue-main"><b>#<?php echo htmlspecialchars($o['invoice_no']); ?> · <?php echo htmlspecialchars(explode(' ', trim($o['customer_name']))[0]); ?></b><small><?php echo htmlspecialchars($o['drop_location']); ?> · <?php echo $plural((int)$o['item_count'], 'meal'); ?></small></span>
                            <span class="ka-queue-wait"><?php echo $waitText($wait); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if (count($queue) > 8): ?><p class="ka-fine">And <?php echo count($queue) - 8; ?> more on the Orders page.</p><?php endif; ?>
        <?php endif; ?>
    </section>

    <section class="cb-card ka-card" aria-labelledby="today-title">
        <div class="ka-card-head"><h2 id="today-title">Today so far</h2></div>
        <dl class="ka-today">
            <div><dt>Orders</dt><dd><?php echo $todayRow['orders'] + $todayRow['cancelled']; ?></dd></div>
            <div><dt>Delivered</dt><dd><?php echo max(0, $deliveredToday); ?></dd></div>
            <div><dt>Meals made</dt><dd><?php echo $todayRow['meals']; ?></dd></div>
            <div><dt>Cancelled</dt><dd class="<?php echo $todayRow['cancelled'] ? 'ka-bad' : ''; ?>"><?php echo $todayRow['cancelled']; ?></dd></div>
        </dl>
        <?php if ($today['cancelledToday']): ?>
            <ul class="ka-reasons">
                <?php foreach ($today['cancelledToday'] as $c): ?>
                    <li><?php echo date('g:i A', strtotime($c['order_date'])); ?> · <?php echo htmlspecialchars($c['cancel_reason'] ?: 'No reason given'); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <h3 class="ka-minihead">Selling today</h3>
        <?php if (!$sellingToday): ?>
            <p class="ka-empty">No meals sold yet today.</p>
        <?php else: ?>
            <ol class="ka-selling">
                <?php foreach ($sellingToday as $m): ?>
                    <li><span><?php echo htmlspecialchars($m['product_title']); ?></span><b><?php echo (int)$m['meals']; ?></b></li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>
</div>

<section class="cb-card ka-card" aria-labelledby="hours-title">
    <div class="ka-card-head">
        <div><h2 id="hours-title">Orders by hour today</h2><p class="ka-card-sub">The current hour is darker.</p></div>
    </div>
    <div class="ka-chart" id="hours-chart"></div>
</section>

<script src="<?php echo $ROOT_DIR; ?>/js/kitchen-charts.js?v=<?php echo filemtime(__DIR__ . '/../../js/kitchen-charts.js'); ?>"></script>
<script>
CBCharts.bars(document.getElementById('hours-chart'), <?php echo json_encode($hourRows, JSON_HEX_TAG | JSON_HEX_AMP); ?>,
    { value: 'orders', label: 'Orders by hour today', height: 190, maxBar: 26,
      detail: function (r) { return '<span>' + r.orders + ' order' + (r.orders === 1 ? '' : 's') + '</span>'; } });
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
