<?php
// Checkout: where to deliver, how to pay, and the order summary beside it
require_once __DIR__ . '/../core/core.php';
require_login();

$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
if (!can_order()) {
    header("Location: " . $ROOT_DIR . staff_orders_path());
    exit;
}

require_once __DIR__ . '/../controllers/CartController.php';
require_once __DIR__ . '/../controllers/CustomerController.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$cartCtrl = new CartController();
$cartIp = get_cart_token();
$userId = get_user_id();
$cartItems = $cartCtrl->getCartItems($cartIp, $userId);

if (empty($cartItems)) {
    header("Location: " . $ROOT_DIR . "/views/cart.php");
    exit;
}

$user = (new CustomerController())->getCustomerById($userId);
$subtotal = $cartCtrl->getCartTotal($cartIp, $userId);
$deliveryFee = $cartCtrl->calculateDeliveryFee($cartItems, has_active_pass($user));
$grandTotal = $subtotal + $deliveryFee;
$zone = $cartCtrl->deliveryZoneFor($cartItems);
$kitchenName = $cartItems[0]['brand_name'] ?? '';
// Paystack pays the kitchen directly, so a kitchen without a payout account can't be paid yet
$kitchenIds = $cartCtrl->kitchenIds($cartItems);
$billKitchen = count($kitchenIds) === 1 ? (new ProductController())->getBrandById((int)$kitchenIds[0]) : null;
$canPay = $billKitchen && !empty($billKitchen['paystack_subaccount']);

$dropPoints = [
    'Walter Sisulu Hall (Hostel 1)', 'Wangari Maathai Hall (Hostel 2)', 'Charlotte Court (Off-Campus)', 'Masere Hostel (Berekuso)',
    'FabLab / Engineering Building Courtyard', 'Library Courtyard / Upper Quad', 'Faculty Office Lounge', 'Student Center Hive',
];

require_once __DIR__ . '/layout/header.php';
?>

<form class="cb-bill" action="<?php echo $ROOT_DIR; ?>/actions/checkout_process_action.php" method="POST">
    <?php echo csrf_field(); ?>
    <div>
        <section class="cb-card">
            <h2><span class="cb-step-num">1</span> Where should <?php echo htmlspecialchars($kitchenName ?: 'the kitchen'); ?> deliver?</h2>
            <div class="cb-field">
                <label for="drop-preset">Popular drop points</label>
                <select id="drop-preset" data-fill="drop-location">
                    <option value="">Choose a drop point, or type your own below</option>
                    <?php foreach ($dropPoints as $point): ?>
                        <option value="<?php echo htmlspecialchars($point); ?>"><?php echo htmlspecialchars($point); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="cb-field">
                <label for="drop-location">Drop point, room or building</label>
                <input type="text" id="drop-location" name="drop_location" maxlength="150" required placeholder="e.g. Sisulu Room 208, or Lab 2" value="<?php echo htmlspecialchars($user['campus_hostel'] ?? ''); ?>">
            </div>
            <div class="cb-field">
                <label for="drop-notes">Instructions for the kitchen <span class="cb-muted">(optional)</span></label>
                <input type="text" id="drop-notes" name="notes" maxlength="255" placeholder="e.g. Call when you're outside the courtyard">
            </div>
            <p class="cb-checkout-contact">
                <i class="bi bi-telephone" aria-hidden="true"></i>
                The kitchen will call <b><?php echo htmlspecialchars($user['customer_contact'] ?? ''); ?></b> if it can't find you.
                <a class="cb-link" href="<?php echo $ROOT_DIR; ?>/views/settings.php">Change number</a>
            </p>
        </section>

        <section class="cb-card">
            <h2><span class="cb-step-num">2</span> Pay securely with Paystack</h2>
            <div class="cb-pay-options cb-pay-info">
                <div class="cb-pay-option"><span class="cb-pay-icon" aria-hidden="true"><i class="bi bi-phone-fill"></i></span><span><b>Mobile Money</b><small>MTN, Telecel or AirtelTigo</small></span></div>
                <div class="cb-pay-option"><span class="cb-pay-icon" aria-hidden="true"><i class="bi bi-credit-card-2-front"></i></span><span><b>Visa / Mastercard</b><small>Debit or credit card</small></span></div>
            </div>
            <p class="cb-field-hint">You'll choose how to pay on Paystack's secure page. Your order goes to the kitchen as soon as Paystack confirms the payment. If you cancel, nothing is charged and your bill stays as it is.</p>
        </section>
    </div>

    <aside class="cb-card cb-bill-summary" aria-label="Order summary">
        <h2>My order</h2>
        <ul class="cb-checkout-items">
            <?php foreach ($cartItems as $item): ?>
                <li>
                    <span class="cb-ticket-qty"><?php echo (int)$item['qty']; ?>×</span>
                    <span><?php echo htmlspecialchars($item['product_title']); ?></span>
                    <span class="cb-tray-price">GH₵ <?php echo number_format((float)$item['product_price'] * (int)$item['qty'], 2); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="cb-totals">
            <div><span>Meals</span><span>GH₵ <?php echo number_format($subtotal, 2); ?></span></div>
            <div><span>Delivery (<?php echo htmlspecialchars($zone); ?>)</span><span>GH₵ <?php echo number_format($deliveryFee, 2); ?></span></div>
            <div class="total"><span>Total</span><span>GH₵ <?php echo number_format($grandTotal, 2); ?></span></div>
        </div>
        <?php if ($canPay): ?>
            <button type="submit" class="cb-checkout"><i class="bi bi-lock-fill" aria-hidden="true"></i> Pay GH₵ <?php echo number_format($grandTotal, 2); ?> with Paystack</button>
        <?php else: ?>
            <p class="cb-warn-note"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> <?php echo htmlspecialchars($kitchenName ?: 'This kitchen'); ?> can't take online payments yet. Try again later or order from another kitchen.</p>
            <button type="button" class="cb-checkout" disabled>Payment unavailable</button>
        <?php endif; ?>
        <a class="cb-panel-bills" href="<?php echo $ROOT_DIR; ?>/views/cart.php">‹ Back to bill</a>
    </aside>
</form>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
