<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/SupportController.php';

require_login();
if (!is_admin() && !is_kitchen()) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit;
}
require_csrf();

$supportCtrl = new SupportController();
$id = (int)($_POST['message_id'] ?? 0);
$status = $_POST['status'] ?? '';

$msg = $supportCtrl->getMessageById($id);
$role = $supportCtrl->roleFor($msg);

if (!$msg) {
    $_SESSION['error'] = "That message no longer exists.";
} elseif ($role !== 'admin' && $role !== 'kitchen') {
    $_SESSION['error'] = "You cannot update messages for another kitchen.";
} elseif (!$supportCtrl->setStatus($id, $status)) {
    $_SESSION['error'] = "Choose a status from the list.";
} else {
    $_SESSION['success'] = "Message marked " . strtolower($status) . ".";
}

redirect_back(is_kitchen() ? '../views/kitchen/kitchen_messages.php' : '../views/admin/messages.php');
?>
