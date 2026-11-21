<?php
// Sends a password reset link. The reply is the same whether or not the email has an account.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/forgot_password.php");
    exit;
}
require_csrf();

$email = trim((string)($_POST['customer_email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
    $_SESSION['error'] = 'Enter the email address you signed up with.';
    header("Location: ../views/forgot_password.php");
    exit;
}

$link = (new CustomerController())->requestPasswordReset($email, $_SERVER['REMOTE_ADDR'] ?? '');

$_SESSION['reset_sent'] = [
    'email' => $email,
    // Only shown when emails are saved instead of sent and the site is opened on this computer
    'link' => mail_shows_links() ? $link : null,
];
header("Location: ../views/forgot_password.php");
exit;
