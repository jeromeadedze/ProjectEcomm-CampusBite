<?php
// Kitchen admin removes one of their own staff logins: the login is deleted (it was made for the kitchen)
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

require_kitchen_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/kitchen/kitchen_staff.php");
    exit;
}
require_csrf();

$custCtrl = new CustomerController();
$person = $custCtrl->getCustomerById((int)($_POST['customer_id'] ?? 0));

$removed = $person ? $custCtrl->removeKitchenLogin($person['customer_id'], get_kitchen_id()) : null;
if ($removed === 'deleted') {
    $_SESSION['success'] = "{$person['customer_name']}'s login was deleted. They can't sign in any more.";
} elseif ($removed === 'access_removed') {
    $_SESSION['success'] = "{$person['customer_name']} no longer has access to the kitchen. Their account has past orders, so it was kept as a customer account.";
} else {
    $_SESSION['error'] = "That person isn't on your staff.";
}

header("Location: ../views/kitchen/kitchen_staff.php");
exit;
?>
