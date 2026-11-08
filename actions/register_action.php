<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/register.php");
    exit;
}
require_csrf();

$name = trim($_POST['customer_name'] ?? '');
$email = trim($_POST['customer_email'] ?? '');
$pass = $_POST['customer_pass'] ?? '';
$contact = trim($_POST['customer_contact'] ?? '');
$hostel = trim($_POST['campus_hostel'] ?? '');

// Same rules as js/validate.js, repeated here because browser checks can be skipped
$error = null;
if (mb_strlen($name) < 2) {
    $error = 'Enter your full name.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Enter a valid email address.';
} elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $pass)) {
    $error = 'Use at least 8 characters with an uppercase letter, a lowercase letter, a number and a symbol.';
} elseif (!preg_match('/^[0-9+\-\s]{7,15}$/', $contact)) {
    $error = 'Enter a valid phone number, such as 0241234567.';
} elseif (!array_key_exists($hostel, CustomerController::HOSTELS)) {
    $error = 'Choose your delivery spot from the list.';
}

$result = null;
if ($error === null) {
    $custCtrl = new CustomerController();
    $result = $custCtrl->registerCustomer($name, $email, $pass, 'Ghana', 'Berekuso', $contact, $hostel);
    if (!$result['success']) {
        $error = $result['error'];
    }
}

if ($error !== null) {
    $_SESSION['error'] = $error;
    // Refill the form after the redirect, leaving the password out
    $_SESSION['register_form'] = [
        'customer_name' => $name,
        'customer_email' => $email,
        'customer_contact' => $contact,
        'campus_hostel' => $hostel,
    ];
    header("Location: ../views/register.php");
    exit;
}

log_in_customer($result['user']);
$_SESSION['success'] = "Welcome, " . $result['user']['customer_name'] . "! Your account is ready.";
header("Location: ../index.php");
exit;
?>
