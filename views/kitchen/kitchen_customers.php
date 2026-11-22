<?php
// Kitchen portal: the customers who order from this kitchen. Search by name, email or phone, 25 per page;
// ?id= opens one customer with their orders from this kitchen.
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../controllers/OrderController.php';

require_kitchen_admin();

$kitchenId = get_kitchen_id();
$kitchen = (new ProductController())->getBrandById($kitchenId);
$orderCtrl = new OrderController();

// One customer, only if they have ordered from this kitchen
$detail = null;
if (isset($_GET['id'])) {
    $detail = $orderCtrl->getKitchenCustomer($kitchenId, (int)$_GET['id']);
    if (!$detail) {
        $_SESSION['error'] = "That customer hasn't ordered from " . $kitchen['brand_name'] . '.';
        header('Location: kitchen_customers.php');
        exit;
    }
} else {
    $result = $orderCtrl->getKitchenCustomers($kitchenId, $_GET);
}

$pageHeading = $detail ? $detail['customer']['customer_name'] : $kitchen['brand_name'] . ' customers';
require_once __DIR__ . '/../layout/header.php';

$money = function ($amount) { return 'GH₵ ' . number_format((float)$amount, 2); };
$plural = function ($n, $word) { return (int)$n . ' ' . $word . ((int)$n === 1 ? '' : 's'); };
// Call and WhatsApp buttons for a customer's phone number
$contactButtons = function ($c) use ($kitchen) {
    if (empty($c['customer_contact'])) {
        return '';
    }
    $first = explode(' ', trim($c['customer_name']))[0];
    return '<a class="cb-btn cb-btn-quiet" href="' . htmlspecialchars(phone_tel_url($c['customer_contact'])) . '"><i class="bi bi-telephone" aria-hidden="true"></i> Call ' . htmlspecialchars($first) . '</a>'
         . '<a class="cb-btn cb-btn-quiet" href="' . htmlspecialchars(phone_wa_url($c['customer_contact'], 'Hi ' . $first . ', this is ' . $kitchen['brand_name'] . ' on CampusBite.')) . '" target="_blank" rel="noopener"><i class="bi bi-whatsapp" aria-hidden="true"></i> WhatsApp</a>';
};
?>

<?php if ($detail): ?>
    <?php $c = $detail['customer']; $orders = $detail['orders']; ?>
    <p class="cb-sub"><a class="cb-link" href="kitchen_customers.php">‹ All customers</a></p>
    <p class="cb-sub">
        <?php echo htmlspecialchars($c['customer_contact']); ?> ·
        <a class="cb-link" href="mailto:<?php echo htmlspecialchars($c['customer_email']); ?>"><?php echo htmlspecialchars($c['customer_email']); ?></a>
        <?php if ($c['usual_drop']): ?> · Usually delivers to <?php echo htmlspecialchars($c['usual_drop']); ?><?php endif; ?>
    </p>
    <div class="cb-customer-contact"><?php echo $contactButtons($c); ?></div>

    <div class="cb-kpis">
        <section class="cb-card cb-kpi">
            <h2>Orders</h2>
            <p class="cb-kpi-num"><?php echo (int)$c['orders']; ?></p>
            <p class="cb-kpi-sub"><?php echo (int)$c['cancelled'] ? $plural($c['cancelled'], 'cancelled order') . ' not counted' : 'None cancelled'; ?></p>
        </section>
        <section class="cb-card cb-kpi">
            <h2>Spent with you</h2>
            <p class="cb-kpi-num"><?php echo $money($c['spent']); ?></p>
            <p class="cb-kpi-sub">Meals and delivery, cancelled orders left out</p>
        </section>
        <section class="cb-card cb-kpi">
            <h2>Customer since</h2>
            <p class="cb-kpi-num"><?php echo date('M j, Y', strtotime($c['first_order'])); ?></p>
            <p class="cb-kpi-sub">Last order <?php echo date('M j, Y', strtotime($c['last_order'])); ?></p>
        </section>
    </div>

    <div class="cb-row"><h2>Their orders from you</h2></div>
    <section class="cb-card">
        <div class="cb-table-wrap">
            <table class="cb-table">
                <thead><tr><th>Invoice</th><th>Drop point</th><th class="num">Total</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td class="cb-table-name">
                                <a href="<?php echo $ROOT_DIR; ?>/views/order_tracking.php?order_id=<?php echo (int)$o['order_id']; ?>">#<?php echo htmlspecialchars($o['invoice_no']); ?></a>
                                <span class="cb-table-sub"><?php echo date('M d, Y · h:i A', strtotime($o['order_date'])); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($o['drop_location']); ?></td>
                            <td class="num"><?php echo $money($o['amt'] ?? ((float)$o['items_total'] + (float)$o['delivery_fee'])); ?></td>
                            <td><span class="cb-status cb-status-<?php echo strtolower(str_replace(' ', '-', $o['order_status'])); ?>"><?php echo htmlspecialchars($o['order_status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ((int)$c['orders'] + (int)$c['cancelled'] > count($orders)): ?>
            <p class="cb-field-hint">Showing the latest <?php echo count($orders); ?> orders.</p>
        <?php endif; ?>
    </section>

<?php else: ?>
    <?php
    $s = $result['stats'];
    $listUrl = function ($changes) use ($result) {
        $query = array_merge(['q' => $result['q']], $changes);
        if (isset($query['page']) && (int)$query['page'] <= 1) {
            unset($query['page']);
        }
        $query = array_filter($query, function ($v) { return (string)$v !== ''; });
        return 'kitchen_customers.php' . ($query ? '?' . htmlspecialchars(http_build_query($query)) : '');
    };
    ?>
    <p class="cb-sub">Everyone who has ordered from <?php echo htmlspecialchars($kitchen['brand_name']); ?>, most recent first. Open a customer to see their orders or get in touch.</p>

    <div class="cb-kpis">
        <section class="cb-card cb-kpi">
            <h2>Customers</h2>
            <p class="cb-kpi-num"><?php echo $s['customers']; ?></p>
            <p class="cb-kpi-sub">Have ordered from you</p>
        </section>
        <section class="cb-card cb-kpi">
            <h2>Came back</h2>
            <p class="cb-kpi-num"><?php echo $s['returners']; ?></p>
            <p class="cb-kpi-sub"><?php echo $s['customers'] ? round($s['returners'] / $s['customers'] * 100) . '% ordered more than once' : 'Ordered more than once'; ?></p>
        </section>
        <section class="cb-card cb-kpi">
            <h2>New</h2>
            <p class="cb-kpi-num"><?php echo $s['new_recently']; ?></p>
            <p class="cb-kpi-sub">First order in the last <?php echo OrderController::NEW_CUSTOMER_DAYS; ?> days</p>
        </section>
    </div>

    <form class="cb-find" method="GET" action="kitchen_customers.php" role="search">
        <label class="cb-find-field">
            <span class="cb-sr-only">Search customers by name, email or phone</span>
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" name="q" value="<?php echo htmlspecialchars($result['q']); ?>" maxlength="100" placeholder="Search by name, email or phone">
        </label>
        <button type="submit" class="cb-btn">Search</button>
        <?php if ($result['q'] !== ''): ?>
            <p class="cb-find-note"><?php echo (int)$result['total']; ?> <?php echo (int)$result['total'] === 1 ? 'customer matches' : 'customers match'; ?> "<?php echo htmlspecialchars($result['q']); ?>" · <a class="cb-link" href="kitchen_customers.php">Clear</a></p>
        <?php endif; ?>
    </form>

    <section class="cb-card">
        <div class="cb-table-wrap">
            <table class="cb-table">
                <thead><tr><th>Customer</th><th>Phone</th><th class="num">Orders</th><th class="num">Spent</th><th>Last order</th><th>Usual drop point</th></tr></thead>
                <tbody>
                    <?php if (empty($result['customers'])): ?>
                        <tr><td colspan="6" class="cb-table-empty">
                            <?php if ($result['q'] !== ''): ?>
                                No customers match "<?php echo htmlspecialchars($result['q']); ?>". <a class="cb-link" href="kitchen_customers.php">Clear the search</a>
                            <?php else: ?>
                                No customers yet. Everyone who orders from you will show up here.
                            <?php endif; ?>
                        </td></tr>
                    <?php endif; ?>
                    <?php foreach ($result['customers'] as $c): ?>
                        <tr>
                            <td class="cb-table-name">
                                <a href="kitchen_customers.php?id=<?php echo (int)$c['customer_id']; ?>"><?php echo htmlspecialchars($c['customer_name']); ?></a>
                                <span class="cb-table-sub"><?php echo htmlspecialchars($c['customer_email']); ?></span>
                            </td>
                            <td><a class="cb-link-quiet" href="<?php echo htmlspecialchars(phone_tel_url($c['customer_contact'])); ?>"><?php echo htmlspecialchars($c['customer_contact']); ?></a></td>
                            <td class="num"><?php echo (int)$c['orders']; ?></td>
                            <td class="num"><?php echo $money($c['spent']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($c['last_order'])); ?></td>
                            <td><?php echo htmlspecialchars($c['usual_drop'] ?? '–'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php if ($result['pages'] > 1): ?>
        <nav class="cb-pager" aria-label="Pages">
            <?php if ($result['page'] > 1): ?><a href="<?php echo $listUrl(['page' => $result['page'] - 1]); ?>">‹ More recent</a><?php endif; ?>
            <span>Page <?php echo $result['page']; ?> of <?php echo $result['pages']; ?></span>
            <?php if ($result['page'] < $result['pages']): ?><a href="<?php echo $listUrl(['page' => $result['page'] + 1]); ?>">Earlier ›</a><?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
