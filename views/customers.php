<?php
// Admin: every account, with a switch between customer and courier
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

require_admin();

$customers = (new CustomerController())->getAllCustomers();
$courierCount = count(array_filter($customers, function ($c) {
    return (int)$c['user_role'] === 3;
}));
?>

<?php require __DIR__ . '/layout/admin_nav.php'; ?>

<p class="cb-sub"><?php echo count($customers); ?> accounts · <?php echo $courierCount; ?> courier<?php echo $courierCount === 1 ? '' : 's'; ?>. Make an account a courier to assign it orders.</p>

<section class="cb-card">
    <div class="cb-table-wrap">
        <table class="cb-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Drop point</th>
                    <?php if (PASS_ENABLED): ?><th>Pass</th><?php endif; ?>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="5" class="cb-table-empty">No accounts yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td class="cb-table-name">
                            <?php echo htmlspecialchars($c['customer_name']); ?>
                            <span class="cb-table-sub"><?php echo htmlspecialchars($c['customer_email']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($c['customer_contact']); ?></td>
                        <td><?php echo htmlspecialchars($c['campus_hostel'] ?? 'Not set'); ?></td>
                        <?php if (PASS_ENABLED): ?>
                            <td><?php echo has_active_pass($c) ? 'Active' : '–'; ?></td>
                        <?php endif; ?>
                        <td>
                            <?php if ((int)$c['user_role'] === 1): ?>
                                <span class="cb-zone cb-zone-b">Admin</span>
                            <?php else: ?>
                                <form class="cb-inline-form" action="<?php echo $ROOT_DIR; ?>/actions/update_role_action.php" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="customer_id" value="<?php echo (int)$c['customer_id']; ?>">
                                    <label class="visually-hidden" for="role-<?php echo (int)$c['customer_id']; ?>">Role for <?php echo htmlspecialchars($c['customer_name']); ?></label>
                                    <select id="role-<?php echo (int)$c['customer_id']; ?>" name="user_role">
                                        <?php foreach (CustomerController::ASSIGNABLE_ROLES as $roleValue => $roleLabel): ?>
                                            <option value="<?php echo $roleValue; ?>" <?php echo (int)$c['user_role'] === $roleValue ? 'selected' : ''; ?>><?php echo htmlspecialchars($roleLabel); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="cb-btn cb-btn-quiet">Save</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
