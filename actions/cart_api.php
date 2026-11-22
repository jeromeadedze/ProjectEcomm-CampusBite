<?php
/**
 * Changes the bill without reloading the page (js/main.js: meal-card counters, the order panel's − / + buttons).
 * POST: csrf_token, product_id, qty (the quantity wanted: 0 removes the meal), replace (1 = empty a bill
 * from another kitchen first), root (the page's ROOT_DIR, so the order panel's links are rendered for that page).
 * Answers JSON: the new state of the bill, the order panel's HTML, or a kitchen conflict to ask about.
 * Without JavaScript the same buttons post to add_to_cart_action.php / update_qty_action.php instead.
 */
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';
require_once __DIR__ . '/../controllers/ProductController.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function cart_api_reply($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cart_api_reply(405, ['ok' => false, 'error' => 'Use POST.']);
}
$sent = $_POST['csrf_token'] ?? '';
if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
    cart_api_reply(403, ['ok' => false, 'error' => 'Your session expired. Reload the page and try again.']);
}
if (!can_order()) {
    cart_api_reply(403, ['ok' => false, 'error' => "Admin and kitchen accounts can't place orders."]);
}

// The bill as it is now, with the order panel drawn by the same template as on page load
function cart_api_state($cartCtrl, $token, $userId, $root) {
    $panelItems = $cartCtrl->getCartItems($token, $userId);
    $qtyById = [];
    foreach ($panelItems as $item) {
        $qtyById[(int)$item['p_id']] = (int)$item['qty'];
    }
    $panelCount = array_sum($qtyById);
    $subtotal = (float)$cartCtrl->getCartTotal($token, $userId);
    $panelHtml = '';
    if ($panelCount > 0) {
        $ROOT_DIR = $root;
        $headerCart = $cartCtrl;
        $showPanel = true;
        ob_start();
        require __DIR__ . '/../views/layout/panel.php';
        $panelHtml = ob_get_clean();
    }
    return [
        'items' => (object)$qtyById,
        'count' => $panelCount,
        'subtotal' => round($subtotal, 2),
        'subtotal_text' => 'GH₵ ' . number_format($subtotal, 2),
        'panel' => $panelHtml,
    ];
}

$productId = (int)($_POST['product_id'] ?? 0);
$want = (int)($_POST['qty'] ?? 0);
$replace = ($_POST['replace'] ?? '') === '1';
// Only the three page depths the site uses, so nothing else can end up in the links
$root = in_array($_POST['root'] ?? '', ['.', '..', '../..'], true) ? $_POST['root'] : '..';

$cartCtrl = new CartController();
$productCtrl = new ProductController();
$token = get_cart_token();
$userId = get_user_id();

if ($want < 0 || $want > CartClass::MAX_QTY) {
    cart_api_reply(422, ['ok' => false, 'error' => 'Choose a quantity from 1 to ' . CartClass::MAX_QTY . '.']);
}

$items = $cartCtrl->getCartItems($token, $userId);
$current = 0;
foreach ($items as $item) {
    if ((int)$item['p_id'] === $productId) {
        $current = (int)$item['qty'];
    }
}

if ($want > $current) {
    // Adding: the meal must still be orderable, and a bill holds one kitchen's meals
    $product = $productId > 0 ? $productCtrl->getProductById($productId) : null;
    if (!$productCtrl->isOrderable($product)) {
        cart_api_reply(200, ['ok' => false, 'error' => "Sorry, that meal isn't available right now."] + cart_api_state($cartCtrl, $token, $userId, $root));
    }
    $billKitchen = $items ? (int)$items[0]['product_brand'] : null;
    if ($billKitchen !== null && $billKitchen !== (int)$product['product_brand']) {
        if (!$replace) {
            cart_api_reply(200, ['ok' => false, 'conflict' => [
                'meal' => $product['product_title'],
                'new_kitchen' => $product['brand_name'],
                'bill_kitchen' => $items[0]['brand_name'],
            ]] + cart_api_state($cartCtrl, $token, $userId, $root));
        }
        $cartCtrl->clearCart($token, $userId);
        $current = 0;
    }
    if ($current === 0) {
        $cartCtrl->addToCart($productId, $token, $userId, $want);
    } else {
        $cartCtrl->updateCartQty($productId, $token, $want, $userId);
    }
} elseif ($want < $current) {
    $cartCtrl->updateCartQty($productId, $token, $want, $userId); // 0 removes it
}

cart_api_reply(200, ['ok' => true] + cart_api_state($cartCtrl, $token, $userId, $root));
