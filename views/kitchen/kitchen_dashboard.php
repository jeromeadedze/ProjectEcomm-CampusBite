<?php
// Kitchen portal home: today's work, sales and what CampusBite keeps, best sellers
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../controllers/OrderController.php';

require_kitchen();

$productCtrl = new ProductController();
$orderCtrl = new OrderController();
$kitchenId = get_kitchen_id();
$kitchen = $productCtrl->getBrandById($kitchenId);

$summary = $orderCtrl->getKitchenSummary($kitchenId, $kitchen['commission_rate']);
$byStatus = $orderCtrl->countKitchenOrdersByStatus($kitchenId);
$activeOrders = array_slice($orderCtrl->getKitchenOrders($kitchenId, 'active'), 0, 5);
$bestSellers = $orderCtrl->getKitchenBestSellers($kitchenId, 5);
$meals = $productCtrl->getProductsForKitchen($kitchenId);
$soldOut = count(array_filter($meals, function ($meal) { return !(int)$meal['is_available']; }));
$commissionPercent = round($kitchen['commission_rate'] * 100);
$payoutLabel = ProductController::payoutLabel($kitchen);
$refunds = $orderCtrl->getKitchenRefundBalance($kitchenId);

$pageHeading = $kitchen['brand_name'];
require_once __DIR__ . '/../layout/header.php';

$money = function ($amount) { return 'GH₵ ' . number_format($amount, 2); };
$pipeline = ['Placed' => 'New', 'Preparing' => 'Preparing', 'Ready' => 'Ready to deliver', 'In Transit' => 'Out for delivery'];
?>

<p class="cb-sub">
    <span class="cb-dot-status <?php echo $kitchen['is_active'] ? '' : 'off'; ?>"><?php echo $kitchen['is_active'] ? 'Open to shoppers' : 'Paused: hidden from shoppers'; ?></span>
    · <?php echo htmlspecialchars($kitchen['zone']); ?> · <?php echo htmlspecialchars($kitchen['location_desc'] ?: 'No location set'); ?>
</p>

<div class="cb-kpis">
    <section class="cb-card cb-kpi">
        <h2>Today</h2>
        <p class="cb-kpi-num"><?php echo $money($summary['today']['sales']); ?></p>
        <p class="cb-kpi-sub"><?php echo $summary['today']['orders']; ?> order<?php echo $summary['today']['orders'] === 1 ? '' : 's'; ?> · <?php echo $summary['today']['meals']; ?> meal<?php echo $summary['today']['meals'] === 1 ? '' : 's'; ?></p>
    </section>
    <section class="cb-card cb-kpi">
        <h2>Last 7 days</h2>
        <p class="cb-kpi-num"><?php echo $money($summary['week']['sales']); ?></p>
        <p class="cb-kpi-sub"><?php echo $summary['week']['orders']; ?> order<?php echo $summary['week']['orders'] === 1 ? '' : 's'; ?> · you keep <?php echo $money($summary['week']['payout']); ?></p>
    </section>
    <section class="cb-card cb-kpi">
        <h2>All time</h2>
        <p class="cb-kpi-num"><?php echo $money($summary['all']['sales']); ?></p>
        <dl class="cb-kpi-split">
            <div><dt>CampusBite commission (<?php echo $commissionPercent; ?>%)</dt><dd><?php echo $money($summary['all']['commission']); ?></dd></div>
            <div><dt>You keep</dt><dd><?php echo $money($summary['all']['payout']); ?></dd></div>
        </dl>
    </section>
</div>
<p class="cb-field-hint">Sales count meal prices only. You deliver your own orders, so you keep each order's delivery fee on top of what your meals earn.</p>

<div class="cb-row"><h2>How you get paid</h2><a class="cb-viewall" href="kitchen_info.php#payout"><?php echo $payoutLabel ? 'Change ›' : 'Add account ›'; ?></a></div>
<section class="cb-card">
    <?php if ($payoutLabel): ?>
        <p class="cb-card-lead">Paystack pays your share of every order straight to <strong><?php echo htmlspecialchars($payoutLabel); ?></strong> when the customer pays, usually by the next working day. Your share is the meals minus CampusBite's <?php echo $commissionPercent; ?>% commission, plus the delivery fee.</p>
        <?php if ($refunds['owed'] > 0): ?>
            <p class="cb-warn-note"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> <?php echo $money($refunds['owed']); ?> still to take back for refunded orders. It comes out of your share of your next orders, so those payouts will be smaller until it's repaid.</p>
        <?php endif; ?>
        <p class="cb-field-hint">If an order is cancelled, the customer gets back everything they paid. Paystack takes the refund from CampusBite, so your share of that order is taken back from your next orders.</p>
    <?php else: ?>
        <p class="cb-warn-note"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> Students can't pay for your meals until you add a payout account.&nbsp;<a href="kitchen_info.php#payout">Add it under Kitchen info</a></p>
    <?php endif; ?>
</section>

<div class="cb-row"><h2>Order queue</h2><a class="cb-viewall" href="kitchen_orders.php">All orders ›</a></div>
<div class="cb-queue">
    <?php foreach ($pipeline as $status => $label): ?>
        <a class="cb-card cb-queue-step" href="kitchen_orders.php#status-<?php echo strtolower(str_replace(' ', '-', $status)); ?>">
            <span class="cb-queue-num"><?php echo (int)($byStatus[$status] ?? 0); ?></span>
            <span class="cb-status cb-status-<?php echo strtolower(str_replace(' ', '-', $status)); ?>"><?php echo $label; ?></span>
        </a>
    <?php endforeach; ?>
</div>

<div class="cb-kitchen-split">
    <section class="cb-card">
        <h2>Active orders</h2>
        <?php if (empty($activeOrders)): ?>
            <p class="cb-table-empty">No orders waiting. New orders show up here.</p>
        <?php else: ?>
            <div class="cb-table-wrap">
                <table class="cb-table">
                    <thead><tr><th>Invoice</th><th>Customer</th><th class="num">Meals</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($activeOrders as $o): ?>
                            <tr>
                                <td class="cb-table-name">#<?php echo htmlspecialchars($o['invoice_no']); ?><span class="cb-table-sub"><?php echo date('M d · h:i A', strtotime($o['order_date'])); ?></span></td>
                                <td><?php echo htmlspecialchars($o['customer_name']); ?><span class="cb-table-sub"><?php echo htmlspecialchars($o['drop_location']); ?></span></td>
                                <td class="num"><?php echo (int)$o['item_count']; ?></td>
                                <td><span class="cb-status cb-status-<?php echo strtolower(str_replace(' ', '-', $o['order_status'])); ?>"><?php echo htmlspecialchars($o['order_status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="cb-card">
        <h2>Best sellers</h2>
        <?php if (empty($bestSellers)): ?>
            <p class="cb-table-empty">No sales yet.</p>
        <?php else: ?>
            <ol class="cb-best">
                <?php foreach ($bestSellers as $best): ?>
                    <li>
                        <b><?php echo htmlspecialchars($best['product_title']); ?></b>
                        <span><?php echo (int)$best['meals']; ?> sold · <?php echo $money($best['sales']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
        <p class="cb-field-hint"><?php echo count($meals); ?> meals on your menu<?php echo $soldOut ? ", $soldOut sold out" : ''; ?>. <a class="cb-link" href="kitchen_menu.php">Manage menu ›</a></p>
    </section>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
