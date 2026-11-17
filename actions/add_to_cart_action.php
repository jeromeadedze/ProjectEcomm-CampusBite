<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';
require_once __DIR__ . '/../controllers/ProductController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $p_id = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 1);
    $notes = trim($_POST['notes'] ?? '');

    $productCtrl = new ProductController();
    $product = $p_id > 0 ? $productCtrl->getProductById($p_id) : null;

    if (!can_order()) {
        $_SESSION['error'] = "Admin and kitchen accounts can't place orders. Sign in with a customer account to order.";
    } elseif (!$productCtrl->isOrderable($product)) {
        $_SESSION['error'] = "Sorry, that meal isn't available right now.";
    } elseif ($qty < 1 || $qty > CartClass::MAX_QTY) {
        $_SESSION['error'] = "Choose a quantity from 1 to " . CartClass::MAX_QTY . ".";
    } elseif (mb_strlen($notes) > 255) {
        $_SESSION['error'] = "Keep your special request to 255 characters or fewer.";
    } else {
        $cartCtrl = new CartController();
        $billItems = $cartCtrl->getCartItems(get_cart_token(), get_user_id());
        $billKitchen = $billItems ? (int)$billItems[0]['product_brand'] : null;

        if ($billKitchen !== null && $billKitchen !== (int)$product['product_brand'] && ($_POST['replace'] ?? '') !== '1') {
            // One kitchen per bill: ask before throwing away the current one (shown by views/layout/flash.php)
            $_SESSION['cart_conflict'] = [
                'product_id' => $p_id,
                'qty' => $qty,
                'notes' => $notes,
                'meal' => $product['product_title'],
                'new_kitchen' => $product['brand_name'],
                'bill_kitchen' => $billItems[0]['brand_name'],
            ];
        } else {
            if ($billKitchen !== null && $billKitchen !== (int)$product['product_brand']) {
                $cartCtrl->clearCart(get_cart_token(), get_user_id());
            }
            $cartCtrl->addToCart($p_id, get_cart_token(), get_user_id(), $qty, $notes);
            $_SESSION['success'] = "{$product['product_title']} added to your order!";
        }
    }
}

redirect_back('../views/cart.php');
?>
