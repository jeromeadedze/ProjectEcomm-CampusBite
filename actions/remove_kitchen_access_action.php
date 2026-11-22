<?php
// Admin: removes a kitchen login. It's deleted (kitchen logins are made for the kitchen); one with past orders
// only loses its kitchen access and stays a customer account.
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
    $removed = $custCtrl->removeKitchenLogin($account['customer_id']);
    $_SESSION['success'] = $removed === 'deleted'
        ? "{$account['customer_name']}'s kitchen login was deleted. They can't sign in any more."
        : "{$account['customer_name']} no longer has kitchen access. Their account has past orders, so it was kept as a customer account.";
}

header("Location: ../views/admin/users.php");
exit;
?>
