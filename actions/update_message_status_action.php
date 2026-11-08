<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/SupportController.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/admin/messages.php");
    exit;
}
require_csrf();

$supportCtrl = new SupportController();
$id = (int)($_POST['message_id'] ?? 0);
$status = $_POST['status'] ?? '';

if (!$supportCtrl->getMessageById($id)) {
    $_SESSION['error'] = "That message no longer exists.";
} elseif (!$supportCtrl->setStatus($id, $status)) {
    $_SESSION['error'] = "Choose a status from the list.";
} else {
    $_SESSION['success'] = "Message marked " . strtolower($status) . ".";
}

header("Location: ../views/admin/messages.php");
exit;
?>
