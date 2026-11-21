<?php
// Admin: turns a kitchen login back into a regular customer account
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/admin/users.php");
    exit;
}
require_csrf();

$custCtrl = new CustomerController();
$account = $custCtrl->getCustomerById((int)($_POST['customer_id'] ?? 0));

if (!$account || (int)$account['user_role'] !== 4) {
    $_SESSION['error'] = "That kitchen login no longer exists.";
} else {
    $custCtrl->removeKitchenAccess($account['customer_id']);
    $_SESSION['success'] = "{$account['customer_name']} no longer has kitchen access.";
}

header("Location: ../views/admin/users.php");
exit;
?>
