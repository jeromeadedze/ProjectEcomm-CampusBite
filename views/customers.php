<?php
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

require_admin();

$custCtrl = new CustomerController();
$customers = $custCtrl->getAllCustomers();
?>

<div class="my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold m-0"><i class="bi bi-people-fill text-warning me-2"></i>Campus Community Users</h3>
        <span class="badge bg-dark rounded-pill"><?php echo count($customers); ?> registered</span>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <table class="cb-table mb-0">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Ashesi Email</th>
                    <th>MoMo / Contact</th>
                    <th>Hostel / Drop Point</th>
                    <th>Role</th>
                    <th>Pass Holder</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td class="fw-bold">#<?php echo $c['customer_id']; ?></td>
                        <td><?php echo htmlspecialchars($c['customer_name']); ?></td>
                        <td class="small text-muted"><?php echo htmlspecialchars($c['customer_email']); ?></td>
                        <td class="small"><?php echo htmlspecialchars($c['customer_contact']); ?></td>
                        <td class="small"><?php echo htmlspecialchars($c['campus_hostel'] ?? 'Unspecified'); ?></td>
                        <td>
                            <?php if ($c['user_role'] == 1): ?>
                                <span class="badge bg-danger rounded-pill">Admin</span>
                            <?php elseif ($c['user_role'] == 3): ?>
                                <span class="badge bg-info text-dark rounded-pill">Courier</span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark border rounded-pill">Student / Staff</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($c['has_pass'])): ?>
                                <span class="badge bg-warning text-dark rounded-pill"><i class="bi bi-stars"></i> Pass Active</span>
                            <?php else: ?>
                                <span class="text-muted small">Standard</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
