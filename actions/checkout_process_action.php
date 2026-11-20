<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';
require_once __DIR__ . '/../controllers/CustomerController.php';
require_once __DIR__ . '/../core/paystack.php';

require_login();

if (!can_order()) {
    $_SESSION['error'] = "Admin and kitchen accounts can't place orders.";
    header("Location: .." . staff_orders_path());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/cart.php");
    exit;
}
require_csrf();

$userId = get_user_id();
$cartIp = get_cart_token();

$cartCtrl = new CartController();

$cartItems = $cartCtrl->getCartItems($cartIp, $userId);

if (empty($cartItems)) {
    $_SESSION['error'] = "Your bag is empty. Please add items to checkout.";
    header("Location: ../views/cart.php");
    exit;
}

// Zone and fee are worked out here from the cart, never taken from the form
$user = (new CustomerController())->getCustomerById($userId);
$deliveryZone = $cartCtrl->deliveryZoneFor($cartItems);
$deliveryFee = $cartCtrl->calculateDeliveryFee($cartItems, has_active_pass($user));

$dropLocation = trim($_POST['drop_location'] ?? '');
$notes = trim($_POST['notes'] ?? '');

$formError = null;
if ($dropLocation === '' || mb_strlen($dropLocation) > 150) {
    $formError = "Enter a drop point of up to 150 characters.";
} elseif (mb_strlen($notes) > 255) {
    $formError = "Keep delivery instructions to 255 characters or fewer.";
}
if ($formError) {
    $_SESSION['error'] = $formError;
    header("Location: ../views/checkout.php");
    exit;
}

// One kitchen per order (bills from before this rule may still mix kitchens)
$kitchenIds = $cartCtrl->kitchenIds($cartItems);
if (count($kitchenIds) !== 1) {
    $_SESSION['error'] = "Each order can only come from one kitchen. Remove the meals from the other kitchens to check out.";
    header("Location: ../views/cart.php");
    exit;
}

$unavailable = $cartCtrl->unavailableItems($cartItems);
if ($unavailable) {
    $names = implode(', ', array_column($unavailable, 'product_title'));
    $_SESSION['error'] = "Some meals are no longer available: $names. Remove them to check out.";
    header("Location: ../views/cart.php");
    exit;
}

$subtotal = $cartCtrl->getCartTotal($cartIp, $userId);
$grandTotal = $subtotal + $deliveryFee;

if (!paystack_configured()) {
    error_log('Checkout: core/paystack_config.php is missing or has no secret key');
    $_SESSION['error'] = "Payments aren't set up on this site yet. Please try again later.";
    header("Location: ../views/checkout.php");
    exit;
}

// What is being bought travels with the payment as Paystack metadata. After paying, the callback reads it back
// from Paystack (not from the browser) and creates the order from it, so a cart changed in another tab can't
// alter an order that's already paid for.
$reference = 'CB-' . strtoupper(bin2hex(random_bytes(8)));
$metadata = [
    'customer_id' => $userId,
    'brand_id' => (int)$kitchenIds[0],
    'delivery_zone' => $deliveryZone,
    'delivery_fee' => (float)$deliveryFee,
    'drop_location' => $dropLocation,
    'notes' => $notes,
    'items' => array_map(function ($item) {
        return ['p_id' => (int)$item['p_id'], 'qty' => (int)$item['qty'], 'price' => (float)$item['product_price'], 'notes' => $item['notes']];
    }, $cartItems),
    // Shown on the transaction in the Paystack dashboard
    'custom_fields' => [
        ['display_name' => 'Kitchen', 'variable_name' => 'kitchen', 'value' => $cartItems[0]['brand_name']],
        ['display_name' => 'Drop point', 'variable_name' => 'drop_point', 'value' => $dropLocation],
    ],
];

$init = paystack_request('POST', '/transaction/initialize', [
    'email' => $user['customer_email'],
    'amount' => to_pesewas($grandTotal),
    'currency' => 'GHS',
    'reference' => $reference,
    'callback_url' => site_url('actions/paystack_callback_action.php'),
    'metadata' => $metadata,
]);

if (empty($init['status']) || empty($init['data']['authorization_url'])) {
    error_log('Paystack initialize failed: ' . json_encode($init['message'] ?? $init));
    $_SESSION['error'] = "We couldn't open the payment page. Your bag is unchanged, so please try again.";
    header("Location: ../views/checkout.php");
    exit;
}

header("Location: " . $init['data']['authorization_url']);
exit;
