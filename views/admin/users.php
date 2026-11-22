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

$loginForm = ($_SESSION['kitchen_login_form'] ?? []) + ['brand_id' => (int)($_GET['kitchen'] ?? 0), 'customer_name' => '', 'customer_email' => '', 'customer_contact' => '', 'personal_email' => ''];
unset($_SESSION['kitchen_login_form']);

$plural = function ($n, $word) { return $n . ' ' . $word . ($n === 1 ? '' : 's'); };

require_once __DIR__ . '/../layout/header.php';

// One table row per staff login, with its Remove access button
$staffRow = function ($account, $kitchenCell) use ($ROOT_DIR) { ?>
    <tr>
        <td class="cb-table-name"><?php echo $kitchenCell; ?></td>
        <td><?php echo htmlspecialchars($account['customer_name']); ?><span class="cb-table-sub"><?php echo htmlspecialchars($account['customer_contact']); ?></span></td>
        <td><span class="cb-status <?php echo $account['kitchen_role'] === 'staff' ? 'cb-status-cancelled' : 'cb-status-delivered'; ?>"><?php echo $account['kitchen_role'] === 'staff' ? 'Staff' : 'Kitchen admin'; ?></span></td>
        <td><?php echo htmlspecialchars($account['customer_email']); ?></td>
        <td>
            <div class="cb-table-actions">
                <?php // Kitchen logins can't reset by email (made-up sign-in emails): the admin gives them a new starting password ?>
                <form action="<?php echo $ROOT_DIR; ?>/actions/reset_kitchen_password_action.php" method="POST" data-confirm="Reset <?php echo htmlspecialchars($account['customer_name']); ?>'s password? Their current password stops working, and you'll get a new one to give them.">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="customer_id" value="<?php echo (int)$account['customer_id']; ?>">
                    <button type="submit" class="is-plain" aria-label="Reset <?php echo htmlspecialchars($account['customer_name']); ?>'s password">Reset password</button>
                </form>
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

<?php require __DIR__ . '/../layout/new_login_card.php'; ?>

<p class="cb-sub">
    <?php echo $plural(count($staff), 'kitchen login'); ?> across <?php echo $plural(count($brands), 'kitchen'); ?>.
    You create each kitchen's admin here. Kitchen admins then add their own staff from the kitchen portal.
</p>

<section class="cb-card" aria-labelledby="staff-title">
    <h2 id="staff-title">Logins by kitchen</h2>
    <?php if ($kitchensWithoutStaff > 0): ?>
        <p class="cb-card-lead"><?php echo $plural($kitchensWithoutStaff, 'kitchen'); ?> <?php echo $kitchensWithoutStaff === 1 ? 'has' : 'have'; ?> no login yet, so nobody there can receive orders.</p>
    <?php endif; ?>
    <div class="cb-table-wrap">
        <table class="cb-table">
            <thead><tr><th>Kitchen</th><th>Name</th><th>Role</th><th>Sign-in email</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                <?php if (empty($brands) && empty($orphans)): ?>
                    <tr><td colspan="5" class="cb-table-empty">No kitchens yet. <a class="cb-link" href="brand.php">Add a kitchen</a> first, then give its staff a login here.</td></tr>
                <?php endif; ?>
                <?php foreach ($brands as $brand): ?>
                    <?php $kitchenCell = '<a href="kitchen.php?id=' . (int)$brand['brand_id'] . '">' . htmlspecialchars($brand['brand_name']) . '</a>'; ?>
                    <?php if (empty($byKitchen[(int)$brand['brand_id']])): ?>
                        <tr>
                            <td class="cb-table-name"><?php echo $kitchenCell; ?></td>
                            <td colspan="3" class="cb-muted">No login yet</td>
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
    <h2 id="new-login-title">Add a kitchen admin</h2>
    <p class="cb-card-lead">The kitchen admin is the owner or manager. They see the kitchen's sales, set the menu and prices, manage the payout account and add their own staff, who handle the orders. Their email and starting password are made for you.</p>
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
            <label for="login_name">Admin's name</label>
            <input type="text" id="login_name" name="customer_name" maxlength="100" autocomplete="off" value="<?php echo htmlspecialchars($loginForm['customer_name']); ?>" placeholder="Ama Mensah" required>
        </div>

        <div class="cb-field">
            <label for="login_contact">Phone</label>
            <input type="tel" id="login_contact" name="customer_contact" maxlength="15" autocomplete="off" value="<?php echo htmlspecialchars($loginForm['customer_contact']); ?>" placeholder="0241234567" required>
        </div>
        <div class="cb-field cb-field-full">
            <label for="login_personal">Their own email</label>
            <input type="email" id="login_personal" name="personal_email" maxlength="100" autocomplete="off" value="<?php echo htmlspecialchars($loginForm['personal_email']); ?>" placeholder="ama.mensah@gmail.com" aria-describedby="login_personal_hint" required>
            <p class="cb-field-hint" id="login_personal_hint">We'll email their sign-in details here.</p>
        </div>
        <div class="cb-field-full lp" data-login-preview>
            <span class="lp-label">Sign-in details</span>
            <div class="lp-auto" data-auto>
                <div><span>Email</span><code data-email>kitchen.firstname@campusbite.com</code></div>
                <div><span>Starting password</span><code data-pass>Kitchen@Firstname + 2 digits</code></div>
            </div>
            <div class="cb-field" data-custom-email hidden>
                <label for="login_email">Sign-in email</label>
                <input type="email" id="login_email" name="customer_email" maxlength="100" autocomplete="off" value="<?php echo htmlspecialchars($loginForm['customer_email']); ?>" placeholder="name@example.com">
            </div>
            <p class="cb-field-hint">Made for you when you save. A number is added if the email is taken. The full password is shown once afterwards, and they set their own at first sign-in. <button type="button" class="mf-suggest" data-email-toggle>Use a different email</button></p>
        </div>
        <div class="cb-admin-actions"><button type="submit" class="cb-btn">Create kitchen admin</button></div>
    </form>
</section>
<?php endif; ?>

<script src="<?php echo $ROOT_DIR; ?>/js/login-preview.js?v=<?php echo filemtime(__DIR__ . '/../../js/login-preview.js'); ?>"></script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
