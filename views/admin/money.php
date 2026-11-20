<?php
// Admin: money report. Meal sales per kitchen, CampusBite's commission, what each kitchen is owed,
// and a check of each order's total against the payment recorded for it.
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_admin();

require_once __DIR__ . '/../../controllers/OrderController.php';
require_once __DIR__ . '/../../controllers/ProductController.php';

// A valid Y-m-d date from the query string, or the fallback
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
    'Last month' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
    'Last 7 days' => [date('Y-m-d', strtotime('-6 days')), date('Y-m-d')],
    'All time' => ['2020-01-01', date('Y-m-d')],
];

$report = (new OrderController())->getMoneyReport($from, $to, (new ProductController())->getBrandsForAdmin());
$totals = $report['totals'];
$pay = $report['payments'];

// CSV download, sent before any page output
if (($_GET['export'] ?? '') === 'csv') {
    // Stops spreadsheet apps treating a kitchen name such as "=SUM(...)" as a formula
    $cell = function ($v) {
        $v = (string)$v;
        return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
    };
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="campusbite-money-' . $from . '-to-' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Kitchen', 'Commission %', 'Delivered orders', 'Meal sales', 'CampusBite commission', 'Delivery fees', 'Kitchen is owed', 'Orders in progress', 'In progress value'], ',', '"', '');
    foreach (array_merge($report['kitchens'], [$totals]) as $r) {
        fputcsv($out, [
            $cell($r['brand_name']),
            isset($r['commission_rate']) ? round($r['commission_rate'] * 100) : '',
            $r['orders'],
            number_format($r['sales'], 2, '.', ''),
            number_format($r['commission'], 2, '.', ''),
            number_format($r['delivery'], 2, '.', ''),
            number_format($r['owed'], 2, '.', ''),
            $r['pending_orders'],
            number_format($r['pending_value'], 2, '.', ''),
        ], ',', '"', '');
    }
    fclose($out);
    exit;
}

$pageHeading = 'Money';
require_once __DIR__ . '/../layout/header.php';

$money = function ($amount) { return 'GH₵ ' . number_format((float)$amount, 2); };
$range = function ($a, $b) { return 'from=' . urlencode($a) . '&to=' . urlencode($b); };
?>

<p class="cb-sub">
    Orders placed <?php echo date('M j, Y', strtotime($from)); ?> – <?php echo date('M j, Y', strtotime($to)); ?>.
    Only delivered orders count towards what a kitchen is owed.
</p>

<div class="cb-chips cb-money-presets" role="group" aria-label="Date range">
    <?php foreach ($presets as $label => [$a, $b]): ?>
        <a class="cb-chip <?php echo ($a === $from && $b === $to) ? 'is-on' : ''; ?>" href="money.php?<?php echo $range($a, $b); ?>"><?php echo $label; ?></a>
    <?php endforeach; ?>
</div>

<form class="cb-card cb-money-range" method="GET" action="money.php">
    <label>From <input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>" max="<?php echo date('Y-m-d'); ?>"></label>
    <label>To <input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>" max="<?php echo date('Y-m-d'); ?>"></label>
    <button type="submit" class="cb-btn">Show</button>
    <a class="cb-btn cb-btn-quiet" href="money.php?<?php echo $range($from, $to); ?>&amp;export=csv"><i class="bi bi-download" aria-hidden="true"></i> Download CSV</a>
</form>

<div class="cb-kpis">
    <section class="cb-card cb-kpi">
        <h2>Meal sales</h2>
        <p class="cb-kpi-num"><?php echo $money($totals['sales']); ?></p>
        <p class="cb-kpi-sub"><?php echo (int)$totals['orders']; ?> delivered order<?php echo (int)$totals['orders'] === 1 ? '' : 's'; ?></p>
    </section>
    <section class="cb-card cb-kpi">
        <h2>CampusBite earns</h2>
        <p class="cb-kpi-num"><?php echo $money($totals['commission']); ?></p>
        <p class="cb-kpi-sub">Commission on meal sales</p>
    </section>
    <section class="cb-card cb-kpi">
        <h2>Owed to kitchens</h2>
        <p class="cb-kpi-num"><?php echo $money($totals['owed']); ?></p>
        <p class="cb-kpi-sub">Includes <?php echo $money($totals['delivery']); ?> in delivery fees</p>
    </section>
    <section class="cb-card cb-kpi">
        <h2>Still in progress</h2>
        <p class="cb-kpi-num"><?php echo $money($totals['pending_value']); ?></p>
        <p class="cb-kpi-sub"><?php echo (int)$totals['pending_orders']; ?> order<?php echo (int)$totals['pending_orders'] === 1 ? '' : 's'; ?>, not owed until delivered</p>
    </section>
</div>

<div class="cb-row"><h2>By kitchen</h2></div>
<section class="cb-card">
    <div class="cb-table-wrap">
        <table class="cb-table">
            <thead>
                <tr>
                    <th>Kitchen</th>
                    <th class="num">Delivered</th>
                    <th class="num">Meal sales</th>
                    <th class="num">Commission</th>
                    <th class="num">Delivery fees</th>
                    <th class="num">Kitchen is owed</th>
                    <th class="num">In progress</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($report['kitchens'])): ?>
                    <tr><td colspan="7" class="cb-table-empty">No kitchens yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($report['kitchens'] as $k): ?>
                    <tr>
                        <td class="cb-table-name">
                            <?php echo htmlspecialchars($k['brand_name']); ?>
                            <span class="cb-table-sub"><?php echo round($k['commission_rate'] * 100); ?>% commission now</span>
                        </td>
                        <td class="num"><?php echo (int)$k['orders']; ?></td>
                        <td class="num"><?php echo $money($k['sales']); ?></td>
                        <td class="num"><?php echo $money($k['commission']); ?></td>
                        <td class="num"><?php echo $money($k['delivery']); ?></td>
                        <td class="num"><b><?php echo $money($k['owed']); ?></b></td>
                        <td class="num">
                            <?php echo $money($k['pending_value']); ?>
                            <span class="cb-table-sub"><?php echo (int)$k['pending_orders']; ?> order<?php echo (int)$k['pending_orders'] === 1 ? '' : 's'; ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="cb-table-total">
                    <th scope="row">All kitchens</th>
                    <td class="num"><?php echo (int)$totals['orders']; ?></td>
                    <td class="num"><?php echo $money($totals['sales']); ?></td>
                    <td class="num"><?php echo $money($totals['commission']); ?></td>
                    <td class="num"><?php echo $money($totals['delivery']); ?></td>
                    <td class="num"><?php echo $money($totals['owed']); ?></td>
                    <td class="num"><?php echo $money($totals['pending_value']); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</section>
<p class="cb-field-hint">A kitchen is owed its meal sales minus CampusBite's commission, plus the delivery fees, because kitchens deliver their own orders. Commission uses the rate the kitchen had when each order was placed. Meals are counted under the kitchen that cooked them.</p>

<div class="cb-row"><h2>Payment check</h2></div>
<section class="cb-card">
    <dl class="cb-kpi-split cb-money-check">
        <div><dt>Orders placed in this range</dt><dd><?php echo (int)$pay['orders']; ?></dd></div>
        <div><dt>Customers should have paid</dt><dd><?php echo $money($pay['expected']); ?></dd></div>
        <div><dt>Payments recorded</dt><dd><?php echo $money($pay['paid']); ?></dd></div>
        <div class="is-total"><dt>Difference</dt><dd><?php echo $money($pay['paid'] - $pay['expected']); ?></dd></div>
    </dl>

    <?php if (empty($pay['problems'])): ?>
        <p class="cb-money-ok"><i class="bi bi-check-circle" aria-hidden="true"></i> Every order has exactly one payment that matches its total.</p>
    <?php else: ?>
        <div class="cb-table-wrap">
            <table class="cb-table">
                <thead><tr><th>Invoice</th><th>Kitchen</th><th>Customer</th><th class="num">Order total</th><th class="num">Paid</th><th>Problem</th></tr></thead>
                <tbody>
                    <?php foreach ($pay['problems'] as $p): ?>
                        <tr>
                            <td class="cb-table-name">
                                <a href="<?php echo $ROOT_DIR; ?>/views/order_tracking.php?order_id=<?php echo (int)$p['order_id']; ?>">#<?php echo htmlspecialchars($p['invoice_no']); ?></a>
                                <span class="cb-table-sub"><?php echo date('M d · h:i A', strtotime($p['order_date'])); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($p['brand_name'] ?? '–'); ?></td>
                            <td><?php echo htmlspecialchars($p['customer_name']); ?></td>
                            <td class="num"><?php echo $money($p['expected']); ?></td>
                            <td class="num">
                                <?php echo $p['paid'] === null ? '–' : $money($p['paid']); ?>
                                <?php if (!empty($p['refs'])): ?><span class="cb-table-sub"><?php echo htmlspecialchars($p['refs']); ?></span><?php endif; ?>
                            </td>
                            <td><span class="cb-status cb-status-placed"><?php echo htmlspecialchars($p['problem']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<p class="cb-field-hint">Payments are simulated for now, so this compares orders with the payments CampusBite recorded at checkout. Once Paystack is connected, each payment will be verified with Paystack and this check will use the amounts Paystack actually received.</p>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
