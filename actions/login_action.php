<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['customer_email'] ?? '');
    $pass = $_POST['customer_pass'] ?? '';

    if (!empty($email) && !empty($pass)) {
        $custCtrl = new CustomerController();
        $user = $custCtrl->loginCustomer($email, $pass);

        if ($user) {
            $_SESSION['customer_id'] = $user['customer_id'];
            $_SESSION['customer_name'] = $user['customer_name'];
            $_SESSION['customer_email'] = $user['customer_email'];
            $_SESSION['user_role'] = $user['user_role'];
            $_SESSION['has_pass'] = $user['has_pass'];

            $_SESSION['success'] = "Welcome back, " . htmlspecialchars($user['customer_name']) . "!";
            header("Location: ../index.php");
            exit;
        } else {
            $_SESSION['error'] = "Invalid email or password combination.";
        }
    } else {
        $_SESSION['error'] = "Please fill in all login fields.";
    }
}

header("Location: ../views/login.php");
exit;
?>
