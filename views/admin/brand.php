<?php
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../controllers/CustomerController.php';

require_admin();

$kitchenAccounts = (new CustomerController())->getKitchenAccounts();
$loginForm = $_SESSION['kitchen_login_form'] ?? ['brand_id' => 0, 'customer_name' => '', 'customer_email' => '', 'customer_contact' => ''];
unset($_SESSION['kitchen_login_form']);

$controller = new ProductController();
$brands = $controller->getBrandsForAdmin();

$editBrand = isset($_GET['edit_id']) ? $controller->getBrandById((int)$_GET['edit_id']) : null;

// A rejected submission comes back with what was typed; otherwise show the saved kitchen or the defaults
$form = $_SESSION['brand_form'] ?? null;
unset($_SESSION['brand_form']);

if (!$form) {
    $form = $editBrand
        ? [
            'brand_name' => $editBrand['brand_name'],
            'zone' => $editBrand['zone'],
            'location_desc' => $editBrand['location_desc'],
            'commission_percent' => round($editBrand['commission_rate'] * 100),
            'is_active' => (int)$editBrand['is_active'],
        ]
        : ['brand_name' => '', 'zone' => 'Zone A', 'location_desc' => '', 'commission_percent' => 10, 'is_active' => 1];
}

require_once __DIR__ . '/../layout/header.php';
?>

<section class="cb-card">
    <h2><?php echo $editBrand ? 'Edit ' . htmlspecialchars($editBrand['brand_name']) : 'Add a kitchen'; ?></h2>
    <form class="cb-admin-form" action="<?php echo $ROOT_DIR; ?>/actions/<?php echo $editBrand ? 'update_brand_action.php' : 'add_brand_action.php'; ?>" method="POST">
        <?php echo csrf_field(); ?>
        <?php if ($editBrand): ?>
            <input type="hidden" name="brand_id" value="<?php echo (int)$editBrand['brand_id']; ?>">
        <?php endif; ?>
        <div class="cb-field">
            <label for="brand_name">Kitchen name</label>
            <input type="text" id="brand_name" name="brand_name" maxlength="100" value="<?php echo htmlspecialchars($form['brand_name']); ?>" placeholder="Hallmark Cafeteria" required>
        </div>
        <div class="cb-field">
            <label for="location_desc">Location</label>
            <input type="text" id="location_desc" name="location_desc" maxlength="255" value="<?php echo htmlspecialchars($form['location_desc'] ?? ''); ?>" placeholder="Student Center Pavilion">
        </div>
        <div class="cb-field cb-field-full">
            <label for="zone">Delivery zone</label>
            <select id="zone" name="zone">
                <?php foreach (ProductController::ZONES as $zoneValue => $zoneLabel): ?>
                    <option value="<?php echo htmlspecialchars($zoneValue); ?>" <?php echo $form['zone'] === $zoneValue ? 'selected' : ''; ?>><?php echo htmlspecialchars($zoneLabel); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="cb-field">
            <label for="commission_percent">Commission (%)</label>
            <input type="number" id="commission_percent" name="commission_percent" min="0" max="100" step="1" value="<?php echo htmlspecialchars((string)$form['commission_percent']); ?>" required>
        </div>
        <div class="cb-field">
            <label for="is_active">Status</label>
            <select id="is_active" name="is_active" aria-describedby="status_hint">
                <option value="1" <?php echo (int)$form['is_active'] === 1 ? 'selected' : ''; ?>>Active</option>
                <option value="0" <?php echo (int)$form['is_active'] === 0 ? 'selected' : ''; ?>>Paused</option>
            </select>
            <p class="cb-field-hint" id="status_hint">Paused kitchens are hidden from shoppers.</p>
        </div>
        <div class="cb-admin-actions">
            <button type="submit" class="cb-btn"><?php echo $editBrand ? 'Save changes' : 'Add kitchen'; ?></button>
            <?php if ($editBrand): ?>
                <a class="cb-btn cb-btn-quiet" href="brand.php">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="cb-card">
    <h2>All kitchens</h2>
    <div class="cb-table-wrap">
        <table class="cb-table">
            <thead>
                <tr>
                    <th>Kitchen</th>
                    <th>Zone</th>
                    <th>Status</th>
                    <th class="num">Commission</th>
                    <th class="num">Menu items</th>
                    <th><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($brands)): ?>
                    <tr><td colspan="6" class="cb-table-empty">No kitchens yet. Add the first one above.</td></tr>
                <?php endif; ?>
                <?php foreach ($brands as $brand): ?>
                    <?php $isEditing = $editBrand && (int)$editBrand['brand_id'] === (int)$brand['brand_id']; ?>
                    <tr <?php echo $isEditing ? 'class="is-editing"' : ''; ?>>
                        <td class="cb-table-name">
                            <?php echo htmlspecialchars($brand['brand_name']); ?>
                            <?php if (!empty($brand['location_desc'])): ?>
                                <span class="cb-table-sub"><?php echo htmlspecialchars($brand['location_desc']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($brand['zone']); ?></td>
                        <td>
                            <span class="cb-dot-status <?php echo $brand['is_active'] ? '' : 'off'; ?>"><?php echo $brand['is_active'] ? 'Active' : 'Paused'; ?></span>
                        </td>
                        <td class="num"><?php echo round($brand['commission_rate'] * 100); ?>%</td>
                        <td class="num"><?php echo (int)$brand['product_count']; ?></td>
                        <td>
                            <div class="cb-table-actions">
                                <a href="brand.php?edit_id=<?php echo (int)$brand['brand_id']; ?>" aria-label="Edit <?php echo htmlspecialchars($brand['brand_name']); ?>">Edit</a>
                                <form action="<?php echo $ROOT_DIR; ?>/actions/delete_brand_action.php" method="POST" <?php if ((int)$brand['product_count'] === 0): ?>data-confirm="Delete <?php echo htmlspecialchars($brand['brand_name']); ?>? This can't be undone."<?php endif; ?>>
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="brand_id" value="<?php echo (int)$brand['brand_id']; ?>">
                                    <button type="submit" aria-label="Delete <?php echo htmlspecialchars($brand['brand_name']); ?>">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="cb-card" id="kitchen-logins">
    <h2>Kitchen logins</h2>
    <p class="cb-card-lead">Give a kitchen's staff their own sign-in. They get a kitchen portal to receive orders, assign couriers, update their menu and prices, and see their sales. Share the starting password with them privately; they can change it under My account.</p>
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
            <input type="text" id="login_name" name="customer_name" maxlength="100" value="<?php echo htmlspecialchars($loginForm['customer_name']); ?>" placeholder="Ama Mensah" required>
        </div>
        <div class="cb-field">
            <label for="login_email">Sign-in email</label>
            <input type="email" id="login_email" name="customer_email" maxlength="100" value="<?php echo htmlspecialchars($loginForm['customer_email']); ?>" placeholder="hallmark@campusbite.app" required>
        </div>
        <div class="cb-field">
            <label for="login_contact">Phone</label>
            <input type="tel" id="login_contact" name="customer_contact" maxlength="15" value="<?php echo htmlspecialchars($loginForm['customer_contact']); ?>" placeholder="0241234567" required>
        </div>
        <div class="cb-field cb-field-full">
            <label for="login_pass">Starting password</label>
            <input type="password" id="login_pass" name="customer_pass" minlength="8" autocomplete="new-password" aria-describedby="login_pass_hint" required>
            <p class="cb-field-hint" id="login_pass_hint">At least 8 characters with an uppercase letter, a lowercase letter, a number and a symbol.</p>
        </div>
        <div class="cb-admin-actions"><button type="submit" class="cb-btn">Create kitchen login</button></div>
    </form>

    <div class="cb-table-wrap" style="margin-top:18px">
        <table class="cb-table">
            <thead><tr><th>Kitchen</th><th>Staff</th><th>Sign-in email</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                <?php if (empty($kitchenAccounts)): ?>
                    <tr><td colspan="4" class="cb-table-empty">No kitchen logins yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($kitchenAccounts as $account): ?>
                    <tr>
                        <td class="cb-table-name"><?php echo htmlspecialchars($account['brand_name'] ?? 'Kitchen removed'); ?></td>
                        <td><?php echo htmlspecialchars($account['customer_name']); ?><span class="cb-table-sub"><?php echo htmlspecialchars($account['customer_contact']); ?></span></td>
                        <td><?php echo htmlspecialchars($account['customer_email']); ?></td>
                        <td>
                            <div class="cb-table-actions">
                                <form action="<?php echo $ROOT_DIR; ?>/actions/remove_kitchen_access_action.php" method="POST" data-confirm="Remove kitchen access for <?php echo htmlspecialchars($account['customer_name']); ?>? The account stays, but can no longer open the kitchen portal.">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="customer_id" value="<?php echo (int)$account['customer_id']; ?>">
                                    <button type="submit">Remove access</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
