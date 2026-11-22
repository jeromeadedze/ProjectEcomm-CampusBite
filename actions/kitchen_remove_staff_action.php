<?php
// Kitchen admin removes one of their own staff logins (the account stays, as a regular customer)
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

if ($person && $custCtrl->removeKitchenStaff($person['customer_id'], get_kitchen_id())) {
    $_SESSION['success'] = "{$person['customer_name']} no longer has access to the kitchen.";
} else {
    $_SESSION['error'] = "That person isn't on your staff.";
}

header("Location: ../views/kitchen/kitchen_staff.php");
exit;
?>
