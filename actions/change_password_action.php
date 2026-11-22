<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/settings.php");
    exit;
}
require_csrf();

$error = (new CustomerController())->changePassword(
    get_user_id(),
    $_POST['current_pass'] ?? '',
    $_POST['new_pass'] ?? '',
    $_POST['confirm_pass'] ?? ''
);

if ($error) {
    set_form_message('password', $error);
} else {
    // A fresh session id, and this device's session follows the new password; the account's other sessions
    // (other browsers and phones) no longer match it and are signed out on their next page (core.php)
    session_regenerate_id(true);
    $me = (new CustomerController())->getCustomerById(get_user_id());
    $_SESSION['pass_stamp'] = password_stamp($me['customer_pass']);
    set_form_message('password', "Your password was changed.", 'success');
}

// On a mistake, reopen the password form so it can be tried again straight away
header("Location: ../views/settings.php" . ($error ? '?edit=password' : ''));
exit;
?>
