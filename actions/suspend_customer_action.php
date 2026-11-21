<?php
// Admin: suspends a customer account (with a reason) or reinstates it
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
$account = $custCtrl->getCustomerById($id);
$do = $_POST['do'] ?? '';

if (!$account) {
    $_SESSION['error'] = "That account doesn't exist.";
    header("Location: ../views/customers.php");
    exit;
}

if ($do === 'suspend') {
    $error = $custCtrl->suspendCustomer($id, $_POST['reason'] ?? '');
    $done = "{$account['customer_name']} is suspended and was signed out.";
} elseif ($do === 'reinstate') {
    $error = $custCtrl->reinstateCustomer($id);
    $done = "{$account['customer_name']} can sign in and order again.";
} else {
    $error = 'Choose suspend or reinstate.';
}

$_SESSION[$error ? 'error' : 'success'] = $error ?: $done;
header("Location: ../views/admin/customer.php?id=" . $id);
exit;
