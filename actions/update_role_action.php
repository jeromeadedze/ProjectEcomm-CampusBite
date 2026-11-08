<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/customers.php");
    exit;
}
require_csrf();

$custCtrl = new CustomerController();
$id = (int)($_POST['customer_id'] ?? 0);
$role = (int)($_POST['user_role'] ?? 0);
$account = $custCtrl->getCustomerById($id);

if (!$account) {
    $_SESSION['error'] = "That account no longer exists.";
} elseif ((int)$account['user_role'] === 1) {
    // Admins are only created or removed in the database
    $_SESSION['error'] = "Admin accounts can't be changed here.";
} elseif (!$custCtrl->setRole($id, $role)) {
    $_SESSION['error'] = "Choose a role from the list.";
} else {
    $_SESSION['success'] = "{$account['customer_name']} is now " . (CustomerController::ASSIGNABLE_ROLES[$role] === 'Courier' ? 'a courier' : 'a regular customer') . ".";
}

header("Location: ../views/customers.php");
exit;
?>
