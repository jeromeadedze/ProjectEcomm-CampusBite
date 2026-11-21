<?php
// Admin: money report. Meal sales per kitchen, CampusBite's commission, what each kitchen is owed,
// and a check of each order's total against the payment recorded for it.
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_admin();

require_once __DIR__ . '/../../controllers/OrderController.php';
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../controllers/PayoutController.php';

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

$kitchens = (new ProductController())->getBrandsForAdmin();
$report = (new OrderController())->getMoneyReport($from, $to, $kitchens);
$totals = $report['totals'];
$pay = $report['payments'];

// Payouts: balances over all time, whatever range is chosen above
$payoutCtrl = new PayoutController();
$balances = $payoutCtrl->getBalances($kitchens);
uasort($balances, function ($a, $b) { return [$b['balance'], $a['brand_name']] <=> [$a['balance'], $b['brand_name']]; });
$recentPayouts = $payoutCtrl->getPayouts(null, 10);
$payKitchen = $balances[(int)($_GET['pay'] ?? 0)] ?? null;
$payForm = ($_SESSION['payout_form'] ?? []) + ['amount' => $payKitchen ? number_format(max(0, $payKitchen['balance']), 2, '.', '') : '',
                                               'method' => 'Mobile Money', 'reference' => '', 'note' => '', 'paid_on' => date('Y-m-d')];
unset($_SESSION['payout_form']);

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

// Every payout ever recorded, as CSV
if (($_GET['export'] ?? '') === 'payouts') {
    $cell = function ($v) {
        $v = (string)$v;
        return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
    };
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="campusbite-payouts-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Paid on', 'Kitchen', 'Amount', 'Method', 'Reference', 'Note', 'Recorded by', 'Recorded at'], ',', '"', '');
    foreach ($payoutCtrl->getPayouts() as $p) {
        fputcsv($out, [$p['paid_on'], $cell($p['brand_name']), number_format((float)$p['amount'], 2, '.', ''), $p['method'],
                       $cell($p['reference']), $cell($p['note']), $cell($p['recorded_by_name']), $p['created_at']], ',', '"', '');
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
    Only delivered orders count towards what a kitchen is owed; cancelled orders don't count at all.
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

<div class="cb-row" id="payouts">
    <h2>Payouts</h2>
    <?php if (!empty($recentPayouts)): ?><a class="cb-viewall" href="money.php?export=payouts"><i class="bi bi-download" aria-hidden="true"></i> All payouts CSV</a><?php endif; ?>
</div>
<section class="cb-card">
    <p class="cb-card-lead">What each kitchen has earned on delivered orders since it joined, what CampusBite has paid it, and what's still owed. These don't change with the dates above.</p>
    <div class="cb-table-wrap">
        <table class="cb-table">
            <thead><tr><th>Kitchen</th><th class="num">Earned to date</th><th class="num">Paid out</th><th class="num">Balance owed</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($balances as $b): ?>
                    <tr>
                        <td class="cb-table-name">
                            <a href="kitchen.php?id=<?php echo (int)$b['brand_id']; ?>"><?php echo htmlspecialchars($b['brand_name']); ?></a>
                            <span class="cb-table-sub"><?php echo $b['last_paid_on'] ? 'Last paid ' . date('M j, Y', strtotime($b['last_paid_on'])) : 'Never paid out'; ?></span>
                        </td>
                        <td class="num"><?php echo $money($b['earned']); ?></td>
                        <td class="num"><?php echo $money($b['paid']); ?></td>
                        <td class="num"><b><?php echo $money($b['balance']); ?></b></td>
                        <td class="num">
                            <?php if ($b['balance'] > 0): ?>
                                <a class="cb-btn cb-btn-quiet" href="money.php?<?php echo $range($from, $to); ?>&amp;pay=<?php echo (int)$b['brand_id']; ?>#record-payout">Record payout</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($payKitchen): ?>
    <section class="cb-card" id="record-payout">
        <h2>Record a payout to <?php echo htmlspecialchars($payKitchen['brand_name']); ?></h2>
        <p class="cb-card-lead">Pay the kitchen first (Mobile Money, bank transfer or cash), then record it here. <?php echo htmlspecialchars($payKitchen['brand_name']); ?> is owed <b><?php echo $money($payKitchen['balance']); ?></b>. A payout can't be changed once it's recorded.</p>
        <form class="cb-admin-form" action="<?php echo $ROOT_DIR; ?>/actions/record_payout_action.php" method="POST" data-confirm="Record this payout to <?php echo htmlspecialchars($payKitchen['brand_name']); ?>? It can't be changed afterwards.">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="brand_id" value="<?php echo (int)$payKitchen['brand_id']; ?>">
            <div class="cb-field">
                <label for="payout-amount">Amount paid (GH₵)</label>
                <input type="text" inputmode="decimal" id="payout-amount" name="amount" required pattern="\d+(\.\d{1,2})?" value="<?php echo htmlspecialchars($payForm['amount']); ?>">
            </div>
            <div class="cb-field">
                <label for="payout-method">Paid by</label>
                <select id="payout-method" name="method">
                    <?php foreach (PayoutController::METHODS as $method): ?>
                        <option value="<?php echo htmlspecialchars($method); ?>" <?php echo $payForm['method'] === $method ? 'selected' : ''; ?>><?php echo htmlspecialchars($method); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="cb-field">
                <label for="payout-ref">Reference <span class="cb-muted">(not needed for cash)</span></label>
                <input type="text" id="payout-ref" name="reference" maxlength="100" placeholder="e.g. MoMo transaction ID" value="<?php echo htmlspecialchars($payForm['reference']); ?>">
            </div>
            <div class="cb-field">
                <label for="payout-date">Paid on</label>
                <input type="date" id="payout-date" name="paid_on" required max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($payForm['paid_on']); ?>">
            </div>
            <div class="cb-field cb-field-full">
                <label for="payout-note">Note <span class="cb-muted">(optional)</span></label>
                <input type="text" id="payout-note" name="note" maxlength="255" placeholder="e.g. Payout for the first half of November" value="<?php echo htmlspecialchars($payForm['note']); ?>">
            </div>
            <div class="cb-admin-actions">
                <button type="submit" class="cb-btn">Record payout</button>
                <a class="cb-btn cb-btn-quiet" href="money.php?<?php echo $range($from, $to); ?>#payouts">Cancel</a>
            </div>
        </form>
    </section>
<?php endif; ?>

<?php if (!empty($recentPayouts)): ?>
    <section class="cb-card">
        <h2>Recent payouts</h2>
        <div class="cb-table-wrap">
            <table class="cb-table">
                <thead><tr><th>Paid on</th><th>Kitchen</th><th class="num">Amount</th><th>How</th><th>Recorded by</th></tr></thead>
                <tbody>
                    <?php foreach ($recentPayouts as $p): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($p['paid_on'])); ?></td>
                            <td class="cb-table-name"><?php echo htmlspecialchars($p['brand_name']); ?><?php if ($p['note']): ?><span class="cb-table-sub"><?php echo htmlspecialchars($p['note']); ?></span><?php endif; ?></td>
                            <td class="num"><?php echo $money($p['amount']); ?></td>
                            <td><?php echo htmlspecialchars($p['method']); ?><?php if ($p['reference']): ?><span class="cb-table-sub"><?php echo htmlspecialchars($p['reference']); ?></span><?php endif; ?></td>
                            <td><?php echo htmlspecialchars($p['recorded_by_name'] ?? 'Removed account'); ?><span class="cb-table-sub"><?php echo date('M j · h:i A', strtotime($p['created_at'])); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<div class="cb-row"><h2>Payment check</h2></div>
<section class="cb-card">
    <dl class="cb-kpi-split cb-money-check">
        <div><dt>Orders placed in this range</dt><dd><?php echo (int)$pay['orders']; ?></dd></div>
        <div><dt>Customers should have paid (cancelled orders left out)</dt><dd><?php echo $money($pay['expected']); ?></dd></div>
        <div><dt>Payments recorded</dt><dd><?php echo $money($pay['paid']); ?></dd></div>
        <div><dt>Confirmed by Paystack</dt><dd><?php echo $money($pay['verified']); ?></dd></div>
        <div><dt>Refunded for <?php echo (int)$pay['cancelled']; ?> cancelled order<?php echo (int)$pay['cancelled'] === 1 ? '' : 's'; ?></dt><dd>− <?php echo $money($pay['refunded']); ?></dd></div>
        <div class="is-total"><dt>Difference</dt><dd><?php echo $money($pay['paid'] - $pay['refunded'] - $pay['expected']); ?></dd></div>
    </dl>

    <?php if (empty($pay['problems'])): ?>
        <p class="cb-money-ok"><i class="bi bi-check-circle" aria-hidden="true"></i> Every order has exactly one Paystack-confirmed payment that matches its total.</p>
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
                                <?php if (!empty($p['refunded']) && (float)$p['refunded'] > 0): ?><span class="cb-table-sub">Refund <?php echo $money($p['refunded']); ?> · <?php echo htmlspecialchars($p['refund_status']); ?></span><?php endif; ?>
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
<p class="cb-field-hint">Each payment is checked with Paystack's verify API before its order is created, and the amount stored is the amount Paystack reports it received. Orders from before Paystack was connected show as "Not confirmed by Paystack" because their payments were simulated.</p>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
