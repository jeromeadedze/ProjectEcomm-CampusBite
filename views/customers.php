<?php
// Admin: every account and its role. Kitchen logins are created on the Kitchens tab.
require_once __DIR__ . '/../core/core.php';
require_admin();

require_once __DIR__ . '/../controllers/CustomerController.php';
$customers = (new CustomerController())->getAllCustomers();
require_once __DIR__ . '/layout/header.php';
?>


<p class="cb-sub"><?php echo count($customers); ?> accounts. Kitchen logins are managed on the Kitchens tab.</p>

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
                            <?php elseif ((int)$c['user_role'] === 4): ?>
                                <span class="cb-zone cb-zone-a">Kitchen · <?php echo htmlspecialchars($c['brand_name'] ?? 'none'); ?></span>
                            <?php else: ?>
                                Student / Staff
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
