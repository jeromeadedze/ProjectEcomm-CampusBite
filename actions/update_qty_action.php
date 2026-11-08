<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $p_id = (int)($_POST['p_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 1);

    if ($qty < 0 || $qty > CartClass::MAX_QTY) {
        $_SESSION['error'] = "Choose a quantity from 1 to " . CartClass::MAX_QTY . ".";
    } elseif ($p_id > 0) {
        // A quantity of 0 removes the meal
        (new CartController())->updateCartQty($p_id, get_cart_token(), $qty, get_user_id());
    }
}

header("Location: ../views/cart.php");
exit;
?>
