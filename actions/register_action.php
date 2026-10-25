<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['customer_name'] ?? '');
    $email = trim($_POST['customer_email'] ?? '');
    $pass = $_POST['customer_pass'] ?? '';
    $contact = trim($_POST['customer_contact'] ?? '');
    $hostel = trim($_POST['campus_hostel'] ?? 'Walter Sisulu Hall');

    if (!empty($name) && !empty($email) && !empty($pass) && !empty($contact)) {
        $custCtrl = new CustomerController();
        $ok = $custCtrl->registerCustomer($name, $email, $pass, 'Ghana', 'Berekuso', $contact, $hostel);

        if ($ok) {
            $_SESSION['success'] = "Account created successfully! Please sign in.";
            header("Location: ../views/login.php");
            exit;
        } else {
            $_SESSION['error'] = "Registration failed. Email might already be in use.";
        }
    } else {
        $_SESSION['error'] = "Please fill in all required fields.";
    }
}

header("Location: ../views/register.php");
exit;
?>
