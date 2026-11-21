<?php
// Admin: the people who run each kitchen. Kitchen staff logins, grouped by kitchen, and adding or removing them.
// Customers aren't managed here; each kitchen sees its own customers in the kitchen portal.
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../controllers/CustomerController.php';

require_admin();

$brands = (new ProductController())->getBrandsForAdmin();
$staff = (new CustomerController())->getKitchenAccounts();

// Staff per kitchen; a login whose kitchen was removed is listed on its own
$byKitchen = [];
$orphans = [];
$brandIds = array_map('intval', array_column($brands, 'brand_id'));
foreach ($staff as $account) {
    if (in_array((int)$account['brand_id'], $brandIds, true)) {
        $byKitchen[(int)$account['brand_id']][] = $account;
    } else {
        $orphans[] = $account;
    }
}
$kitchensWithoutStaff = count($brands) - count($byKitchen);

$loginForm = $_SESSION['kitchen_login_form'] ?? ['brand_id' => (int)($_GET['kitchen'] ?? 0), 'customer_name' => '', 'customer_email' => '', 'customer_contact' => ''];
unset($_SESSION['kitchen_login_form']);

$plural = function ($n, $word) { return $n . ' ' . $word . ($n === 1 ? '' : 's'); };

require_once __DIR__ . '/../layout/header.php';

// One table row per staff login, with its Remove access button
$staffRow = function ($account, $kitchenCell) use ($ROOT_DIR) { ?>
    <tr>
        <td class="cb-table-name"><?php echo $kitchenCell; ?></td>
        <td><?php echo htmlspecialchars($account['customer_name']); ?><span class="cb-table-sub"><?php echo htmlspecialchars($account['customer_contact']); ?></span></td>
        <td><?php echo htmlspecialchars($account['customer_email']); ?></td>
        <td>
            <div class="cb-table-actions">
                <form action="<?php echo $ROOT_DIR; ?>/actions/remove_kitchen_access_action.php" method="POST" data-confirm="Remove kitchen access for <?php echo htmlspecialchars($account['customer_name']); ?>? They can still sign in to order food, but can no longer open the kitchen portal.">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="customer_id" value="<?php echo (int)$account['customer_id']; ?>">
                    <button type="submit" aria-label="Remove kitchen access for <?php echo htmlspecialchars($account['customer_name']); ?>">Remove access</button>
                </form>
            </div>
        </td>
    </tr>
<?php };
?>

<p class="cb-sub">
    <?php echo $plural(count($staff), 'staff login'); ?> across <?php echo $plural(count($brands), 'kitchen'); ?>.
    Staff run their kitchen's orders, menu and messages from the kitchen portal.
</p>

<section class="cb-card" aria-labelledby="staff-title">
    <h2 id="staff-title">Staff by kitchen</h2>
    <?php if ($kitchensWithoutStaff > 0): ?>
        <p class="cb-card-lead"><?php echo $plural($kitchensWithoutStaff, 'kitchen'); ?> <?php echo $kitchensWithoutStaff === 1 ? 'has' : 'have'; ?> no login yet, so nobody there can receive orders.</p>
    <?php endif; ?>
    <div class="cb-table-wrap">
        <table class="cb-table">
            <thead><tr><th>Kitchen</th><th>Staff</th><th>Sign-in email</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                <?php if (empty($brands) && empty($orphans)): ?>
                    <tr><td colspan="4" class="cb-table-empty">No kitchens yet. <a class="cb-link" href="brand.php">Add a kitchen</a> first, then give its staff a login here.</td></tr>
                <?php endif; ?>
                <?php foreach ($brands as $brand): ?>
                    <?php $kitchenCell = '<a href="kitchen.php?id=' . (int)$brand['brand_id'] . '">' . htmlspecialchars($brand['brand_name']) . '</a>'; ?>
                    <?php if (empty($byKitchen[(int)$brand['brand_id']])): ?>
                        <tr>
                            <td class="cb-table-name"><?php echo $kitchenCell; ?></td>
                            <td colspan="2" class="cb-muted">No login yet</td>
                            <td><div class="cb-table-actions"><a href="users.php?kitchen=<?php echo (int)$brand['brand_id']; ?>#new-login" aria-label="Add a login for <?php echo htmlspecialchars($brand['brand_name']); ?>">Add login</a></div></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($byKitchen[(int)$brand['brand_id']] as $account): ?>
                            <?php $staffRow($account, $kitchenCell); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php foreach ($orphans as $account): ?>
                    <?php $staffRow($account, '<span class="cb-muted">Kitchen removed</span>'); ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($brands): ?>
<section class="cb-card" id="new-login" aria-labelledby="new-login-title">
    <h2 id="new-login-title">Add a kitchen login</h2>
    <p class="cb-card-lead">Staff get the kitchen portal: they receive and deliver orders, keep the menu and prices current and see their sales and customers. Share the starting password with them privately; they can change it under My account.</p>
    <form class="cb-admin-form" action="<?php echo $ROOT_DIR; ?>/actions/create_kitchen_account_action.php" method="POST" autocomplete="off">
        <?php echo csrf_field(); ?>
        <div class="cb-field">
            <label for="login_brand">Kitchen</label>
            <select id="login_brand" name="brand_id" required>
                <option value="" <?php echo (int)$loginForm['brand_id'] === 0 ? 'selected' : ''; ?> disabled>Choose a kitchen</option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?php echo (int)$brand['brand_id']; ?>" <?php echo (int)$loginForm['brand_id'] === (int)$brand['brand_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($brand['brand_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="cb-field">
            <label for="login_name">Staff name</label>
            <input type="text" id="login_name" name="customer_name" maxlength="100" autocomplete="off" value="<?php echo htmlspecialchars($loginForm['customer_name']); ?>" placeholder="Ama Mensah" required>
        </div>
        <div class="cb-field">
            <label for="login_email">Sign-in email</label>
            <input type="email" id="login_email" name="customer_email" maxlength="100" autocomplete="off" value="<?php echo htmlspecialchars($loginForm['customer_email']); ?>" placeholder="hallmark@campusbite.app" required>
        </div>
        <div class="cb-field">
            <label for="login_contact">Phone</label>
            <input type="tel" id="login_contact" name="customer_contact" maxlength="15" autocomplete="off" value="<?php echo htmlspecialchars($loginForm['customer_contact']); ?>" placeholder="0241234567" required>
        </div>
        <div class="cb-field cb-field-full">
            <label for="login_pass">Starting password</label>
            <input type="password" id="login_pass" name="customer_pass" minlength="8" autocomplete="new-password" aria-describedby="login_pass_hint" required>
            <p class="cb-field-hint" id="login_pass_hint">At least 8 characters with an uppercase letter, a lowercase letter, a number and a symbol.</p>
        </div>
        <div class="cb-admin-actions"><button type="submit" class="cb-btn">Create kitchen login</button></div>
    </form>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
