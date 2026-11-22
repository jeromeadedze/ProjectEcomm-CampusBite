<?php
// Resets a kitchen login's forgotten password to a new starting password, shown once to whoever reset it.
// The kitchen admin can reset their own staff (Staff page); the CampusBite admin can reset any kitchen login (Users).
// The person must choose their own password the next time they sign in.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

$byAdmin = is_admin();
if (!$byAdmin) {
    require_kitchen_admin();
}
$back = $byAdmin ? '../views/admin/users.php' : '../views/kitchen/kitchen_staff.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $back");
    exit;
}
require_csrf();

$id = (int)($_POST['customer_id'] ?? 0);
if (!$byAdmin && $id === (int)get_user_id()) {
    $_SESSION['error'] = 'To change your own password, use Settings.';
    header("Location: $back");
    exit;
}

$result = (new CustomerController())->resetKitchenPassword($id, $byAdmin ? null : get_kitchen_id());
if ($result['error']) {
    $_SESSION['error'] = $result['error'];
    header("Location: $back");
    exit;
}

$_SESSION['new_login'] = ['name' => $result['person']['customer_name'], 'email' => $result['person']['customer_email'],
                          'password' => $result['password'], 'reset' => true];
$_SESSION['success'] = "{$result['person']['customer_name']}'s password was reset.";
header("Location: $back#new-login-done");
exit;
