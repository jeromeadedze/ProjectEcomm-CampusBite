<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p_id = (int)($_POST['p_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 1);

    if ($p_id > 0) {
        $cartCtrl = new CartController();
        $ip = get_ip();
        $c_id = get_user_id();

        $cartCtrl->updateCartQty($p_id, $ip, $qty, $c_id);
    }
}

header("Location: ../views/cart.php");
exit;
?>
