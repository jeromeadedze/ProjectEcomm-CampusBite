<?php
// Saves a new password from a reset link, then sends the person to sign in
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/forgot_password.php");
    exit;
}
require_csrf();

$token = (string)($_POST['token'] ?? '');
$error = (new CustomerController())->resetPassword($token, (string)($_POST['new_pass'] ?? ''), (string)($_POST['confirm_pass'] ?? ''));

if ($error) {
    $validToken = preg_match('/^[a-f0-9]{64}$/', $token);
    set_form_message($validToken ? 'reset' : 'forgot', $error);
    $back = $validToken ? 'reset_password.php?token=' . $token : 'forgot_password.php';
    header("Location: ../views/" . $back);
    exit;
}

// Whoever was signed in on this browser is signed out, so the new password is needed next time
$_SESSION = [];
session_regenerate_id(true);
set_form_message('login', 'Your password was changed. Sign in with your new password.', 'success');
header("Location: ../views/login.php");
exit;
