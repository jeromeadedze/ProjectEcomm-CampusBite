<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

// Only this known destination is accepted, so the link can't send people off-site
$toCheckout = ($_POST['redirect'] ?? '') === 'checkout';
$loginPage = '../views/login.php' . ($toCheckout ? '?redirect=checkout' : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email = trim($_POST['customer_email'] ?? '');
    $pass = $_POST['customer_pass'] ?? '';

    if (!empty($email) && !empty($pass)) {
        $custCtrl = new CustomerController();
        $user = $custCtrl->loginCustomer($email, $pass);

        if ($user) {
            log_in_customer($user);
            $_SESSION['success'] = "Welcome back, " . $user['customer_name'] . "!";
            header("Location: " . ($toCheckout ? '../views/checkout.php' : '../index.php'));
            exit;
        } else {
            $_SESSION['error'] = "That email and password don't match. Check them and try again.";
        }
    } else {
        $_SESSION['error'] = "Enter your email and password to sign in.";
    }
}

header("Location: " . $loginPage);
exit;
?>
