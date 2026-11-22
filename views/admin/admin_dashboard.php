<?php
// Admin home: how the whole platform is doing, every kitchen at a glance, and what needs attention
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/OrderController.php';
require_once __DIR__ . '/../../controllers/CustomerController.php';
require_once __DIR__ . '/../../controllers/SupportController.php';

require_admin();

$orderCtrl = new OrderController();
$summary = $orderCtrl->getPlatformSummary();
$kitchens = $orderCtrl->getKitchenOverview(date('Y-m-d 00:00:00', strtotime('-6 days')));

$accounts = (new CustomerController())->getAllCustomers();
$roleCount = array_count_values(array_map(function ($c) { return (int)$c['user_role']; }, $accounts));
$openMessages = count(array_filter((new SupportController())->getAllMessages(), function ($m) { return $m['status'] === 'Open'; }));

$pageHeading = 'CampusBite overview';
$liveRefresh = true; // orders and messages change on their own: the page updates itself every 5 s
require_once __DIR__ . '/../layout/header.php';

$money = function ($amount) { return 'GH₵ ' . number_format($amount, 2); };
$plural = function ($n, $word) { return $n . ' ' . $word . ($n === 1 ? '' : 's'); };
?>

<p class="cb-sub">
    <?php echo $plural(count($kitchens), 'kitchen'); ?> ·
    <?php echo $plural((int)($roleCount[4] ?? 0), 'kitchen login'); ?>
</p>

<div class="cb-kpis">
    <?php foreach (['today' => 'Today', 'week' => 'Last 7 days', 'all' => 'All time'] as $key => $label): ?>
        <section class="cb-card cb-kpi">
            <h2><?php echo $label; ?></h2>
            <p class="cb-kpi-num"><?php echo $money($summary[$key]['sales']); ?></p>
            <p class="cb-kpi-sub"><?php echo $plural($summary[$key]['orders'], 'order'); ?> · meal sales</p>
            <dl class="cb-kpi-split">
                <div class="is-total"><dt>CampusBite earns</dt><dd><?php echo $money($summary[$key]['earned']); ?></dd></div>
            </dl>
            <p class="cb-kpi-sub">Commission on meals. Kitchens also kept <?php echo $money($summary[$key]['delivery']); ?> in delivery fees.</p>
        </section>
    <?php endforeach; ?>
</div>

<?php
// Each issue: icon, headline, a muted detail line, and the place to fix it
$issues = [];
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
                        <td>
                            <span class="cb-dot-status <?php echo $k['is_active'] ? '' : 'off'; ?>"><?php echo $k['is_active'] ? 'Open' : 'Paused'; ?></span>
                            <?php if (empty($k['paystack_subaccount'])): ?><a class="cb-no-payout" href="money.php#payout-accounts"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> Can't take payments</a><?php endif; ?>
                        </td>
                        <td><?php echo (int)$k['logins'] > 0 ? $plural((int)$k['logins'], 'login') : '<a class="cb-link" href="users.php?kitchen=' . (int)$k['brand_id'] . '#new-login">Add login</a>'; ?></td>
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

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
