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
    $_SESSION['error'] = $error;
} else {
    // A fresh session id, so anyone holding the old one is signed out
    session_regenerate_id(true);
    $_SESSION['success'] = "Your password was changed.";
}

header("Location: ../views/settings.php");
exit;
?>
