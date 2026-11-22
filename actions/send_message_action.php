<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/SupportController.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/contact.php");
    exit;
}
require_csrf();

$result = (new SupportController())->submitMessage(get_user_id(), $_POST);

if ($result['error']) {
    set_form_message('contact', $result['error']);
    $_SESSION['contact_form'] = $result['values'];
} else {
    set_form_message('contact', "Thanks, your message was sent. We'll get back to you soon.", 'success');
}

header("Location: ../views/contact.php");
exit;
?>
