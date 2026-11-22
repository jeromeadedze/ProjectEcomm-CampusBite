<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/settings.php");
    exit;
}
require_csrf();

$result = (new CustomerController())->updateProfile(get_user_id(), $_POST);

if ($result['error']) {
    set_form_message('profile', $result['error']);
    $_SESSION['settings_form'] = $result['values'];
} else {
    // The greeting and avatar read the name from the session
    $_SESSION['customer_name'] = $result['values']['customer_name'];
    $_SESSION['customer_email'] = $result['values']['customer_email'];
    set_form_message('profile', $result['emailChanged']
        ? "Saved. Sign in with {$result['values']['customer_email']} from now on."
        : "Your details were saved.", 'success');
}

header("Location: ../views/settings.php");
exit;
?>
