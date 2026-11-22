<?php
// CampusBite admin creates a kitchen's admin login (role 4, linked to one kitchen).
// The email (kitchen.firstname@campusbite.com) and a starting password are made for them;
// the new kitchen admin sets their own password the first time they sign in.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/admin/users.php");
    exit;
}
require_csrf();

$productCtrl = new ProductController();
$custCtrl = new CustomerController();
$brandId = (int)($_POST['brand_id'] ?? 0);
$kitchen = $brandId > 0 ? $productCtrl->getBrandById($brandId) : null;
$name = trim($_POST['customer_name'] ?? '');
$password = $kitchen ? CustomerController::startingPassword($kitchen['brand_name'], $name) : '';

$input = [
    'brand_id' => $brandId,
    'customer_name' => $name,
    'customer_contact' => $_POST['customer_contact'] ?? '',
    'customer_email' => trim($_POST['customer_email'] ?? '') ?: ($kitchen ? $custCtrl->suggestKitchenEmail($kitchen['brand_name'], $name) : ''),
    'customer_pass' => $password,
];
// Their own email: where their sign-in details are sent (the sign-in email itself is made for them)
$personalEmail = trim($_POST['personal_email'] ?? '');
if (!filter_var($personalEmail, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Enter their own email address, so we can send them their sign-in details.";
    $_SESSION['kitchen_login_form'] = ['brand_id' => $input['brand_id'], 'customer_name' => $name, 'customer_contact' => $input['customer_contact'],
        'customer_email' => trim($_POST['customer_email'] ?? ''), 'personal_email' => $personalEmail];
    header("Location: ../views/admin/users.php#new-login");
    exit;
}

$result = $custCtrl->createKitchenAccount($input, function ($id) use ($productCtrl) {
    return $id > 0 && (bool)$productCtrl->getBrandById($id);
}, 'admin', true);

if ($result['error']) {
    $_SESSION['error'] = $result['error'];
    $_SESSION['kitchen_login_form'] = $result['values'];
    $_SESSION['kitchen_login_form']['personal_email'] = $personalEmail;
    if (trim($_POST['customer_email'] ?? '') === '') {
        $_SESSION['kitchen_login_form']['customer_email'] = ''; // keep it automatic next time
    }
} else {
    // Shown once on the Users page so the admin can pass the details on
    $sent = $custCtrl->sendKitchenWelcome($personalEmail, $name, $kitchen['brand_name'], $result['values']['customer_email'], $password, 'admin');
    $_SESSION['new_login'] = ['name' => $name, 'email' => $result['values']['customer_email'], 'password' => $password, 'kitchen' => $kitchen['brand_name'], 'sent_to' => $sent ? $personalEmail : null];
    $_SESSION['success'] = "$name is now the kitchen admin for {$kitchen['brand_name']}.";
}

header("Location: ../views/admin/users.php" . ($result['error'] ? '#new-login' : '#new-login-done'));
exit;
?>
