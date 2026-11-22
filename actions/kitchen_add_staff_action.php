<?php
// Kitchen admin adds a staff login for their own kitchen.
// The email (kitchen.firstname@campusbite.com) and a starting password are made for them;
// the new staff member sets their own password the first time they sign in.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_kitchen_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/kitchen/kitchen_staff.php");
    exit;
}
require_csrf();

$custCtrl = new CustomerController();
$kitchen = (new ProductController())->getBrandById(get_kitchen_id());
$name = trim($_POST['customer_name'] ?? '');
$password = CustomerController::startingPassword($kitchen['brand_name'], $name);

// The kitchen always comes from the admin's own account, never from the form
$input = [
    'brand_id' => get_kitchen_id(),
    'customer_name' => $name,
    'customer_contact' => $_POST['customer_contact'] ?? '',
    'customer_email' => trim($_POST['customer_email'] ?? '') ?: $custCtrl->suggestKitchenEmail($kitchen['brand_name'], $name),
    'customer_pass' => $password,
];
// Their own email: where their sign-in details are sent (the sign-in email itself is made for them)
$personalEmail = trim($_POST['personal_email'] ?? '');
if (!filter_var($personalEmail, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Enter their own email address, so we can send them their sign-in details.";
    $_SESSION['staff_form'] = ['brand_id' => $input['brand_id'], 'customer_name' => $name, 'customer_contact' => $input['customer_contact'],
        'customer_email' => trim($_POST['customer_email'] ?? ''), 'personal_email' => $personalEmail];
    header("Location: ../views/kitchen/kitchen_staff.php");
    exit;
}

$result = $custCtrl->createKitchenAccount($input, function ($brandId) { return $brandId === get_kitchen_id(); }, 'staff', true);

if ($result['error']) {
    $_SESSION['error'] = $result['error'];
    $_SESSION['staff_form'] = $result['values'];
    $_SESSION['staff_form']['personal_email'] = $personalEmail;
    if (trim($_POST['customer_email'] ?? '') === '') {
        $_SESSION['staff_form']['customer_email'] = ''; // keep it automatic next time
    }
} else {
    // Shown once on the Staff page so the admin can pass the details on
    $sent = $custCtrl->sendKitchenWelcome($personalEmail, $name, $kitchen['brand_name'], $result['values']['customer_email'], $password, 'staff');
    $_SESSION['new_login'] = ['name' => $name, 'email' => $result['values']['customer_email'], 'password' => $password, 'sent_to' => $sent ? $personalEmail : null];
    $_SESSION['success'] = "$name has been added to your staff.";
}

header("Location: ../views/kitchen/kitchen_staff.php");
exit;
?>
