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
$hasPass = has_active_pass($user);
// The drop point decides the fee. Start from what was picked last time (an error sends the form back),
// then the customer's saved drop point from Settings.
$checkoutForm = $_SESSION['checkout_form'] ?? [];
unset($_SESSION['checkout_form']);
$checkoutMsg = take_form_message('checkout');
$savedPoint = DeliveryController::isDropPoint($user['campus_hostel'] ?? null) ? $user['campus_hostel'] : '';
$pickedPoint = $checkoutForm['drop_point'] ?? $savedPoint;
$pickedPoint = DeliveryController::isDropPoint($pickedPoint) ? $pickedPoint : '';
// This bill's fee to every drop point, for the picker and the live total
$feeByPoint = [];
foreach (array_keys(DeliveryController::DROP_POINTS) as $point) {
    $feeByPoint[$point] = (float)$cartCtrl->calculateDeliveryFee($cartItems, $hasPass, $point);
}
$deliveryFee = $pickedPoint !== '' ? $feeByPoint[$pickedPoint] : null;
$grandTotal = $subtotal + (float)$deliveryFee;
$kitchenName = $cartItems[0]['brand_name'] ?? '';
// Paystack pays the kitchen directly, so a kitchen without a payout account can't be paid yet
$kitchenIds = $cartCtrl->kitchenIds($cartItems);
$billKitchen = count($kitchenIds) === 1 ? (new ProductController())->getBrandById((int)$kitchenIds[0]) : null;
$canPay = $billKitchen && !empty($billKitchen['paystack_subaccount']);


require_once __DIR__ . '/layout/header.php';
?>

<form class="cb-bill" action="<?php echo $ROOT_DIR; ?>/actions/checkout_process_action.php" method="POST">
    <?php echo csrf_field(); ?>
    <div>
        <section class="cb-card">
            <h2><span class="cb-step-num">1</span> Where should <?php echo htmlspecialchars($kitchenName ?: 'the kitchen'); ?> deliver?</h2>
            <div class="cb-field">
                <label for="drop-point">Drop point</label>
                <select id="drop-point" name="drop_point" required data-delivery-picker>
                    <option value="">Choose your hall or hostel</option>
                    <?php foreach (DeliveryController::grouped() as $area => $points): ?>
                        <optgroup label="<?php echo htmlspecialchars(DeliveryController::AREAS[$area]); ?>">
                            <?php foreach ($points as $point): ?>
                                <option value="<?php echo htmlspecialchars($point); ?>" data-fee="<?php echo number_format($feeByPoint[$point], 2, '.', ''); ?>" <?php echo $point === $pickedPoint ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($point); ?> · GH₵ <?php echo number_format($feeByPoint[$point], 0); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <p class="cb-field-hint">
                    <?php echo htmlspecialchars($kitchenName ?: 'This kitchen'); ?> delivers to <?php echo htmlspecialchars(DeliveryController::nearLabel($billKitchen ?: 'Zone A')); ?>
                    for GH₵ <?php echo DeliveryController::FEE_NEAR; ?>; everywhere else is GH₵ <?php echo DeliveryController::FEE_FAR; ?>.
                </p>
            </div>
            <div class="cb-field">
                <label for="drop-detail">Room, block or landmark <span class="cb-muted">(optional)</span></label>
                <input type="text" id="drop-detail" name="drop_detail" maxlength="80" placeholder="e.g. Room 208, or the main gate" value="<?php echo htmlspecialchars($checkoutForm['drop_detail'] ?? ''); ?>">
            </div>
            <?php if ($pickedPoint !== $savedPoint || $savedPoint === ''): ?>
                <label class="cb-check">
                    <input type="checkbox" name="save_drop_point" value="1" <?php echo $savedPoint === '' ? 'checked' : ''; ?>>
                    <span>Make this my usual drop point</span>
                </label>
            <?php endif; ?>
            <div class="cb-field">
                <label for="drop-notes">Instructions for the kitchen <span class="cb-muted">(optional)</span></label>
                <input type="text" id="drop-notes" name="notes" maxlength="255" placeholder="e.g. Call when you're outside the courtyard" value="<?php echo htmlspecialchars($checkoutForm['notes'] ?? ''); ?>">
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
            <div><span data-delivery-label>Delivery<?php echo $pickedPoint !== '' ? ' to ' . htmlspecialchars($pickedPoint) : ''; ?></span><span data-delivery-fee><?php echo $deliveryFee !== null ? 'GH₵ ' . number_format($deliveryFee, 2) : 'Pick a drop point'; ?></span></div>
            <div class="total"><span>Total</span><span data-delivery-total data-subtotal="<?php echo number_format($subtotal, 2, '.', ''); ?>"><?php echo $deliveryFee !== null ? 'GH₵ ' . number_format($grandTotal, 2) : 'GH₵ ' . number_format($subtotal, 2) . ' + delivery'; ?></span></div>
        </div>
        <?php echo form_message_html($checkoutMsg); ?>
        <?php if ($canPay): ?>
            <button type="submit" class="cb-checkout"><i class="bi bi-lock-fill" aria-hidden="true"></i> <span data-delivery-pay><?php echo $deliveryFee !== null ? 'Pay GH₵ ' . number_format($grandTotal, 2) . ' with Paystack' : 'Pay with Paystack'; ?></span></button>
        <?php else: ?>
            <p class="cb-warn-note"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> <?php echo htmlspecialchars($kitchenName ?: 'This kitchen'); ?> can't take online payments yet. Try again later or order from another kitchen.</p>
            <button type="button" class="cb-checkout" disabled>Payment unavailable</button>
        <?php endif; ?>
        <a class="cb-panel-bills" href="<?php echo $ROOT_DIR; ?>/views/cart.php">‹ Back to bill</a>
    </aside>
</form>

<script>
// Shows the delivery fee and total for the drop point picked (the server works the fee out again when paying)
(function () {
    var picker = document.querySelector('[data-delivery-picker]');
    if (!picker) return;
    var label = document.querySelector('[data-delivery-label]'), fee = document.querySelector('[data-delivery-fee]'),
        total = document.querySelector('[data-delivery-total]'), pay = document.querySelector('[data-delivery-pay]');
    var subtotal = parseFloat(total.getAttribute('data-subtotal'));
    var money = function (n) { return 'GH\u20B5 ' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); };
    picker.addEventListener('change', function () {
        var opt = picker.options[picker.selectedIndex];
        if (!picker.value) {
            label.textContent = 'Delivery'; fee.textContent = 'Pick a drop point';
            total.textContent = money(subtotal) + ' + delivery';
            if (pay) pay.textContent = 'Pay with Paystack';
            return;
        }
        var f = parseFloat(opt.getAttribute('data-fee'));
        label.textContent = 'Delivery to ' + picker.value;
        fee.textContent = money(f);
        total.textContent = money(subtotal + f);
        if (pay) pay.textContent = 'Pay ' + money(subtotal + f) + ' with Paystack';
    });
})();
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
