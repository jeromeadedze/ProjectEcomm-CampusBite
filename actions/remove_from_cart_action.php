<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';

$p_id = (int)($_GET['p_id'] ?? 0);

if ($p_id > 0) {
    $cartCtrl = new CartController();
    $ip = get_ip();
    $c_id = get_user_id();

    $cartCtrl->removeFromCart($p_id, $ip, $c_id);
    $_SESSION['success'] = "Item removed from cart.";
}

header("Location: ../views/cart.php");
exit;
?>
