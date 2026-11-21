<?php
// Adds a reply to a support conversation, from the customer, the kitchen that handles it or an admin
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/SupportController.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit;
}
require_csrf();

$supportCtrl = new SupportController();
$id = (int)($_POST['message_id'] ?? 0);
$resolve = !empty($_POST['resolve']);
$error = $supportCtrl->reply($id, $_POST['body'] ?? '', $resolve);

if ($error) {
    $_SESSION['error'] = $error;
    // Keep what was typed, so it isn't lost
    $_SESSION['reply_draft'] = ['message_id' => $id, 'body' => (string)($_POST['body'] ?? '')];
} else {
    $_SESSION['success'] = is_admin() || is_kitchen()
        ? ($resolve ? 'Reply sent and the message marked resolved.' : 'Reply sent. The customer gets an email.')
        : 'Reply sent.';
}

$page = is_admin() ? '../views/admin/messages.php' : (is_kitchen() ? '../views/kitchen/kitchen_messages.php' : '../views/contact.php');
header("Location: " . $page . "#message-" . $id);
exit;
