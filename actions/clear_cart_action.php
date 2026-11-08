<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/cart.php");
    exit;
}
require_csrf();

(new CartController())->clearCart(get_cart_token(), get_user_id());
$_SESSION['success'] = "Your bill has been cleared.";

header("Location: ../views/cart.php");
exit;
?>
