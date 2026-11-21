<?php
// Admin: every account. Search by name, email or phone, filter by group, 25 per page; open one for its history.
// Kitchen logins are created on the Kitchens tab.
require_once __DIR__ . '/../core/core.php';
require_admin();

require_once __DIR__ . '/../controllers/CustomerController.php';
$result = (new CustomerController())->searchAccountsForAdmin($_GET);
$f = $result['filters'];
$accounts = $result['accounts'];
$counts = $result['counts'];

// The current search as a query string, with some values changed (group chips and paging keep the rest)
$listUrl = function ($changes) use ($f) {
    $query = array_merge(['q' => $f['q'], 'group' => $f['group']], $changes);
    if (isset($query['page']) && (int)$query['page'] <= 1) {
        unset($query['page']);
    }
    $query = array_filter($query, function ($v) { return (string)$v !== ''; });
    return 'customers.php' . ($query ? '?' . htmlspecialchars(http_build_query($query)) : '');
};
$money = function ($amount) { return 'GH₵ ' . number_format((float)$amount, 2); };

require_once __DIR__ . '/layout/header.php';
?>

<p class="cb-sub">
    <?php if ($f['q'] !== '' || $f['group'] !== ''): ?>
        <?php echo $result['total']; ?> matching account<?php echo $result['total'] === 1 ? '' : 's'; ?>.
    <?php else: ?>
        <?php echo $counts['all_accounts']; ?> accounts.
    <?php endif; ?>
    Open a customer to see their orders and messages, or to suspend them. Kitchen logins are managed on the Kitchens tab.
</p>

<form class="cb-card cb-money-range cb-order-filters" method="GET" action="customers.php" role="search">
    <label class="cb-order-search">Search
        <input type="search" name="q" value="<?php echo htmlspecialchars($f['q']); ?>" maxlength="100" placeholder="Name, email or phone">
    </label>
    <?php if ($f['group'] !== ''): ?><input type="hidden" name="group" value="<?php echo htmlspecialchars($f['group']); ?>"><?php endif; ?>
    <button type="submit" class="cb-btn">Search</button>
    <?php if ($f['q'] !== '' || $f['group'] !== ''): ?>
        <a class="cb-btn cb-btn-quiet" href="customers.php">Clear</a>
    <?php endif; ?>
</form>

<div class="cb-chips" role="group" aria-label="Filter accounts">
    <a class="cb-chip <?php echo $f['group'] === '' ? 'is-on' : ''; ?>" href="<?php echo $listUrl(['group' => '', 'page' => '']); ?>">All · <?php echo $counts['all_accounts']; ?></a>
    <?php foreach (CustomerController::ACCOUNT_GROUPS as $key => $label): ?>
        <a class="cb-chip <?php echo $f['group'] === $key ? 'is-on' : ''; ?>" href="<?php echo $listUrl(['group' => $key, 'page' => '']); ?>" <?php echo $f['group'] === $key ? 'aria-current="page"' : ''; ?>><?php echo $label; ?> · <?php echo $counts[$key]; ?></a>
    <?php endforeach; ?>
</div>

<section class="cb-card">
    <div class="cb-table-wrap">
        <table class="cb-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th class="num">Orders</th>
                    <th class="num">Spent</th>
                    <th>Last order</th>
                    <th>Account</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($accounts)): ?>
                    <tr><td colspan="6" class="cb-table-empty"><?php echo ($f['q'] !== '' || $f['group'] !== '') ? 'No accounts match.' : 'No accounts yet.'; ?></td></tr>
                <?php endif; ?>
                <?php foreach ($accounts as $c): ?>
                    <tr>
                        <td class="cb-table-name">
                            <a href="<?php echo $ROOT_DIR; ?>/views/admin/customer.php?id=<?php echo (int)$c['customer_id']; ?>"><?php echo htmlspecialchars($c['customer_name']); ?></a>
                            <span class="cb-table-sub"><?php echo htmlspecialchars($c['customer_email']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($c['customer_contact']); ?></td>
                        <td class="num"><?php echo (int)$c['orders']; ?></td>
                        <td class="num"><?php echo $money($c['spent']); ?></td>
                        <td><?php echo $c['last_order'] ? date('M j, Y', strtotime($c['last_order'])) : '–'; ?></td>
                        <td>
                            <?php if ((int)$c['user_role'] === 1): ?>
                                <span class="cb-zone cb-zone-b">Admin</span>
                            <?php elseif ((int)$c['user_role'] === 4): ?>
                                <span class="cb-zone cb-zone-a">Kitchen · <?php echo htmlspecialchars($c['brand_name'] ?? 'none'); ?></span>
                            <?php elseif (!empty($c['is_suspended'])): ?>
                                <span class="cb-status cb-status-cancelled">Suspended</span>
                            <?php else: ?>
                                Customer<?php echo PASS_ENABLED && has_active_pass($c) ? ' · Pass' : ''; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($result['pages'] > 1): ?>
    <nav class="cb-pager" aria-label="Pages">
        <?php if ($result['page'] > 1): ?><a href="<?php echo $listUrl(['page' => $result['page'] - 1]); ?>">‹ Newer</a><?php endif; ?>
        <span>Page <?php echo $result['page']; ?> of <?php echo $result['pages']; ?></span>
        <?php if ($result['page'] < $result['pages']): ?><a href="<?php echo $listUrl(['page' => $result['page'] + 1]); ?>">Older ›</a><?php endif; ?>
    </nav>
<?php endif; ?>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
