<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p_id = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 1);

    if ($p_id > 0) {
        $cartCtrl = new CartController();
        $ip = get_ip();
        $c_id = get_user_id();

        $cartCtrl->addToCart($p_id, $ip, $c_id, $qty);
        $_SESSION['success'] = "Meal added to your order!";
    }
}

// Redirect back
$referer = $_SERVER['HTTP_REFERER'] ?? '../views/cart.php';
header("Location: " . $referer);
exit;
?>
