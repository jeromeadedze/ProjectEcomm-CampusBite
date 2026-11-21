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

        if ($user && !empty($user['is_suspended'])) {
            // Only said after the right password, so it doesn't reveal which emails are suspended
            $_SESSION['error'] = "This account is suspended" . (!empty($user['suspend_reason']) ? ': ' . $user['suspend_reason'] : '') . ". Contact CampusBite support if you think this is a mistake.";
        } elseif ($user) {
            log_in_customer($user);
            $_SESSION['success'] = "Welcome back, " . $user['customer_name'] . "!";
            // Came back from Paystack after the session ran out: finish checking that payment first
            $pendingRef = $_SESSION['pending_payment_ref'] ?? '';
            unset($_SESSION['pending_payment_ref']);
            if (can_order() && preg_match('/^CB-[A-F0-9]{16}$/', $pendingRef)) {
                header('Location: paystack_callback_action.php?reference=' . $pendingRef);
            } elseif (!can_order()) {
                header("Location: .." . home_path_for_role());
            } else {
                header("Location: " . ($toCheckout ? '../views/checkout.php' : '../index.php'));
            }
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
