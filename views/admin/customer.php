<?php
// Admin: one account at a glance. Contact details, ordering history, refunds, support messages,
// and suspending or reinstating a customer account.
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_admin();

require_once __DIR__ . '/../../controllers/CustomerController.php';
require_once __DIR__ . '/../../controllers/OrderController.php';
require_once __DIR__ . '/../../controllers/SupportController.php';

$customerId = (int)($_GET['id'] ?? 0);
$account = $customerId > 0 ? (new CustomerController())->getCustomerById($customerId) : null;
if (!$account) {
    $_SESSION['error'] = "That account doesn't exist.";
    header('Location: ../customers.php');
    exit;
}

$orderCtrl = new OrderController();
$stats = $orderCtrl->getCustomerOrderStats($customerId);
$recent = $orderCtrl->searchOrdersForAdmin(['customer' => $customerId]);
$messages = (new SupportController())->getMessagesByCustomer($customerId);
$role = (int)$account['user_role'];
$isCustomer = $role === 2;
$suspended = !empty($account['is_suspended']);

$pageHeading = $account['customer_name'];
require_once __DIR__ . '/../layout/header.php';

$money = function ($amount) { return 'GH₵ ' . number_format((float)$amount, 2); };
$plural = function ($n, $word) { return (int)$n . ' ' . $word . ((int)$n === 1 ? '' : 's'); };
$roleLabel = [1 => 'Admin', 2 => 'Customer', 4 => 'Kitchen staff'][$role] ?? 'Account';
?>

<p class="cb-sub"><a class="cb-link" href="../customers.php">‹ All customers</a></p>
<p class="cb-sub">
    <span class="cb-dot-status <?php echo $suspended ? 'off' : ''; ?>"><?php echo $suspended ? 'Suspended' : 'Active'; ?></span>
    · <?php echo $roleLabel; ?>
    · <a class="cb-link" href="mailto:<?php echo htmlspecialchars($account['customer_email']); ?>"><?php echo htmlspecialchars($account['customer_email']); ?></a>
    · <?php echo htmlspecialchars($account['customer_contact']); ?>
    <?php if ($isCustomer): ?>· Delivers to <?php echo htmlspecialchars($account['campus_hostel'] ?: 'no drop point set'); ?><?php endif; ?>
    · <?php echo !empty($account['created_at']) ? 'Joined ' . date('M j, Y', strtotime($account['created_at'])) : 'Joined before sign-up dates were recorded'; ?>
</p>

<?php if ($suspended): ?>
    <div class="cb-card cb-suspended-note">
        <b>Suspended <?php echo date('M j, Y', strtotime($account['suspended_at'])); ?>.</b>
        <?php echo htmlspecialchars($account['suspend_reason'] ?? ''); ?>
        They can't sign in or order until you reinstate them.
    </div>
<?php endif; ?>

<div class="cb-kpis">
    <section class="cb-card cb-kpi">
        <h2>Orders</h2>
        <p class="cb-kpi-num"><?php echo (int)$stats['orders']; ?></p>
        <p class="cb-kpi-sub"><?php echo (int)$stats['delivered']; ?> delivered · <?php echo (int)$stats['in_progress']; ?> in progress · <?php echo (int)$stats['cancelled']; ?> cancelled</p>
    </section>
    <section class="cb-card cb-kpi">
        <h2>Spent</h2>
        <p class="cb-kpi-num"><?php echo $money($stats['spent']); ?></p>
        <p class="cb-kpi-sub">Paid for orders that weren't cancelled</p>
    </section>
    <section class="cb-card cb-kpi">
        <h2>Refunded</h2>
        <p class="cb-kpi-num"><?php echo $money($stats['refunded']); ?></p>
        <p class="cb-kpi-sub"><?php echo $plural($stats['cancelled'], 'cancelled order'); ?></p>
    </section>
    <section class="cb-card cb-kpi">
        <h2>Orders most from</h2>
        <p class="cb-kpi-num cb-kpi-text"><?php echo $stats['top_kitchen'] ? htmlspecialchars($stats['top_kitchen']['brand_name']) : '–'; ?></p>
        <p class="cb-kpi-sub"><?php echo $stats['last_order'] ? 'Last order ' . date('M j, Y', strtotime($stats['last_order'])) : 'No orders yet'; ?></p>
    </section>
</div>

<div class="cb-row">
    <h2>Orders</h2>
    <?php if ($recent['total'] > 0): ?><a class="cb-viewall" href="orders.php?customer=<?php echo $customerId; ?>">All <?php echo $plural($recent['total'], 'order'); ?> ›</a><?php endif; ?>
</div>
<section class="cb-card">
    <?php if (empty($recent['orders'])): ?>
        <p class="cb-muted">No orders yet.</p>
    <?php else: ?>
        <div class="cb-table-wrap">
            <table class="cb-table">
                <thead><tr><th>Invoice</th><th>Kitchen</th><th class="num">Total</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach (array_slice($recent['orders'], 0, 8) as $o): ?>
                        <tr>
                            <td class="cb-table-name">
                                <a href="<?php echo $ROOT_DIR; ?>/views/order_tracking.php?order_id=<?php echo (int)$o['order_id']; ?>">#<?php echo htmlspecialchars($o['invoice_no']); ?></a>
                                <span class="cb-table-sub"><?php echo date('M d, Y · h:i A', strtotime($o['order_date'])); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($o['brand_name'] ?? '–'); ?></td>
                            <td class="num"><?php echo $money($o['amt'] ?? ((float)$o['items_total'] + (float)$o['delivery_fee'])); ?></td>
                            <td><span class="cb-status cb-status-<?php echo strtolower(str_replace(' ', '-', $o['order_status'])); ?>"><?php echo htmlspecialchars($o['order_status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<div class="cb-bill">
    <div>
        <div class="cb-row"><h2>Support messages</h2><?php if ($messages): ?><a class="cb-viewall" href="messages.php">Support inbox ›</a><?php endif; ?></div>
        <section class="cb-card">
            <?php if (empty($messages)): ?>
                <p class="cb-muted">No support messages.</p>
            <?php else: ?>
                <ul class="cb-kitchen-msgs">
                    <?php foreach (array_slice($messages, 0, 6) as $m): ?>
                        <li>
                            <span class="cb-dot-status <?php echo $m['status'] === 'Open' ? '' : 'off'; ?>"><?php echo htmlspecialchars($m['status']); ?></span>
                            <a class="cb-link" href="messages.php#message-<?php echo (int)$m['message_id']; ?>"><b><?php echo htmlspecialchars($m['topic']); ?></b></a>
                            to <?php echo htmlspecialchars($m['brand_name'] ?? 'CampusBite'); ?>
                            <?php if (!empty($m['invoice_no'])): ?> · #<?php echo htmlspecialchars($m['invoice_no']); ?><?php endif; ?>
                            <span class="cb-table-sub"><?php echo date('M d · h:i A', strtotime($m['created_at'])); ?> · <?php echo (int)$m['reply_count'] === 1 ? '1 reply' : (int)$m['reply_count'] . ' replies'; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <aside>
        <div class="cb-row"><h2>Account</h2></div>
        <section class="cb-card">
            <?php if (!$isCustomer): ?>
                <p class="cb-muted">
                    <?php echo $role === 4 ? 'This is a kitchen login. Remove its kitchen access from the Kitchens tab.' : "Admin accounts can't be suspended here."; ?>
                </p>
            <?php elseif ($suspended): ?>
                <p class="cb-card-lead">Reinstating lets <?php echo htmlspecialchars(explode(' ', trim($account['customer_name']))[0]); ?> sign in and order again.</p>
                <form action="<?php echo $ROOT_DIR; ?>/actions/suspend_customer_action.php" method="POST" data-confirm="Reinstate <?php echo htmlspecialchars($account['customer_name']); ?>?">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="customer_id" value="<?php echo $customerId; ?>">
                    <input type="hidden" name="do" value="reinstate">
                    <button type="submit" class="cb-btn">Reinstate account</button>
                </form>
            <?php else: ?>
                <p class="cb-card-lead">A suspended customer is signed out straight away and can't sign in or order. Their orders and messages stay.<?php echo (int)$stats['in_progress'] ? ' They have ' . $plural($stats['in_progress'], 'order') . ' in progress; cancel those from the order page if needed.' : ''; ?></p>
                <form action="<?php echo $ROOT_DIR; ?>/actions/suspend_customer_action.php" method="POST" data-confirm="Suspend <?php echo htmlspecialchars($account['customer_name']); ?>? They'll be signed out and can't sign in until you reinstate them.">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="customer_id" value="<?php echo $customerId; ?>">
                    <input type="hidden" name="do" value="suspend">
                    <div class="cb-field">
                        <label for="suspend-reason">Reason <span class="cb-muted">(they see it when they try to sign in)</span></label>
                        <input type="text" id="suspend-reason" name="reason" minlength="3" maxlength="255" required placeholder="e.g. Repeated fake orders">
                    </div>
                    <button type="submit" class="cb-btn cb-btn-danger">Suspend account</button>
                </form>
            <?php endif; ?>
        </section>
    </aside>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
