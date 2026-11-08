<?php
// The bill, in the theme's layout: meals grouped by kitchen, with the order summary beside them
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/CartController.php';

if (is_admin()) {
    header("Location: " . $ROOT_DIR . "/views/admin/orders.php");
    exit;
}

$cartCtrl = new CartController();
$cartItems = $cartCtrl->getCartItems(get_cart_token(), get_user_id());
$subtotal = $cartCtrl->getCartTotal(get_cart_token(), get_user_id());
$deliveryFee = $cartCtrl->calculateDeliveryFee($cartItems);
$deliveryZone = $cartCtrl->deliveryZoneFor($cartItems);
$grandTotal = $subtotal + $deliveryFee;
$hasUnavailable = !empty($cartCtrl->unavailableItems($cartItems));
$mealCount = array_sum(array_column($cartItems, 'qty'));

// Group the meals by kitchen, keeping each kitchen's zone for its badge
$billKitchens = [];
foreach ($cartItems as $item) {
    $billKitchens[$item['brand_name']]['zone'] = $item['zone'];
    $billKitchens[$item['brand_name']]['items'][] = $item;
}
?>

<?php if (empty($cartItems)): ?>
    <div class="cb-card cb-bill-empty">
        <span class="cb-bill-empty-icon" aria-hidden="true"><i class="bi bi-bag"></i></span>
        <h2>Your tray is empty</h2>
        <p>Pick a kitchen and add a few meals. They'll show up here.</p>
        <a class="cb-btn" href="<?php echo $ROOT_DIR; ?>/views/menu.php">Explore the menu</a>
    </div>
<?php else: ?>
    <div class="cb-bill">
        <div>
            <p class="cb-sub"><?php echo $mealCount; ?> meal<?php echo $mealCount === 1 ? '' : 's'; ?> from <?php echo count($billKitchens); ?> kitchen<?php echo count($billKitchens) === 1 ? '' : 's'; ?></p>

            <?php foreach ($billKitchens as $kitchenName => $kitchen): ?>
                <section class="cb-card cb-bill-kitchen" aria-label="<?php echo htmlspecialchars($kitchenName); ?>">
                    <div class="cb-bill-kitchen-head">
                        <h2><?php echo htmlspecialchars($kitchenName); ?></h2>
                        <em class="cb-zone <?php echo $kitchen['zone'] === 'Zone B' ? 'cb-zone-b' : 'cb-zone-a'; ?>"><?php echo htmlspecialchars($kitchen['zone']); ?></em>
                    </div>

                    <ul class="cb-bill-items">
                        <?php foreach ($kitchen['items'] as $item): ?>
                            <?php
                            $qty = (int)$item['qty'];
                            $itemImage = !empty($item['product_image']) && file_exists(__DIR__ . '/../images/products/' . $item['product_image'])
                                ? $ROOT_DIR . '/images/products/' . htmlspecialchars($item['product_image'])
                                : null;
                            $itemUnavailable = (int)$item['is_available'] !== 1 || (int)$item['brand_active'] !== 1;
                            ?>
                            <li class="cb-bill-item<?php echo $itemUnavailable ? ' is-unavailable' : ''; ?>">
                                <span class="cb-menu-img">
                                    <?php if ($itemImage): ?>
                                        <img src="<?php echo $itemImage; ?>" alt="">
                                    <?php else: ?>
                                        <i class="bi bi-egg-fried" aria-hidden="true"></i>
                                    <?php endif; ?>
                                </span>

                                <div class="cb-bill-item-main">
                                    <b><?php echo htmlspecialchars($item['product_title']); ?></b>
                                    <small>GH₵ <?php echo number_format($item['product_price'], 2); ?> each</small>
                                    <?php if ($itemUnavailable): ?>
                                        <small class="cb-bill-warn">No longer available. Remove it to check out.</small>
                                    <?php endif; ?>
                                    <?php if (!empty($item['notes'])): ?>
                                        <small class="cb-bill-note"><i class="bi bi-chat-left-text" aria-hidden="true"></i> <?php echo htmlspecialchars($item['notes']); ?></small>
                                    <?php endif; ?>
                                </div>

                                <form class="cb-stepper" action="<?php echo $ROOT_DIR; ?>/actions/update_qty_action.php" method="POST" aria-label="Quantity of <?php echo htmlspecialchars($item['product_title']); ?>">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="p_id" value="<?php echo (int)$item['p_id']; ?>">
                                    <button type="submit" name="qty" value="<?php echo $qty - 1; ?>" aria-label="One less" <?php echo $qty <= 1 ? 'disabled' : ''; ?>>−</button>
                                    <output aria-live="polite"><?php echo $qty; ?></output>
                                    <button type="submit" name="qty" value="<?php echo $qty + 1; ?>" aria-label="One more" <?php echo ($qty >= CartClass::MAX_QTY || $itemUnavailable) ? 'disabled' : ''; ?>>+</button>
                                </form>

                                <span class="cb-bill-line">GH₵ <?php echo number_format((float)$item['product_price'] * $qty, 2); ?></span>

                                <form action="<?php echo $ROOT_DIR; ?>/actions/remove_from_cart_action.php" method="POST" data-confirm="Remove <?php echo htmlspecialchars($item['product_title']); ?> from your bill?">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="p_id" value="<?php echo (int)$item['p_id']; ?>">
                                    <button type="submit" class="cb-bill-remove" aria-label="Remove <?php echo htmlspecialchars($item['product_title']); ?>" title="Remove"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>

            <div class="cb-bill-foot">
                <a class="cb-link" href="<?php echo $ROOT_DIR; ?>/views/menu.php">‹ Add more meals</a>
                <form action="<?php echo $ROOT_DIR; ?>/actions/clear_cart_action.php" method="POST" data-confirm="Clear every meal from your bill?">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="cb-btn cb-btn-quiet">Clear bill</button>
                </form>
            </div>
        </div>

        <aside class="cb-card cb-bill-summary" aria-label="Order summary">
            <h2>Order summary</h2>
            <div class="cb-totals">
                <div><span>Meals (<?php echo $mealCount; ?>)</span><span>GH₵ <?php echo number_format($subtotal, 2); ?></span></div>
                <div><span>Delivery (<?php echo htmlspecialchars($deliveryZone); ?>)</span><span>GH₵ <?php echo number_format($deliveryFee, 2); ?></span></div>
                <div class="total"><span>Total</span><span>GH₵ <?php echo number_format($grandTotal, 2); ?></span></div>
            </div>
            <p class="cb-field-hint">
                One delivery fee per order: GH₵ <?php echo number_format(ProductController::DELIVERY_FEES['Zone A'], 2); ?> for on-campus kitchens,
                GH₵ <?php echo number_format(ProductController::DELIVERY_FEES['Zone B'], 2); ?> if any meal comes from a Berekuso kitchen.
            </p>

            <?php if ($hasUnavailable): ?>
                <button type="button" class="cb-checkout" disabled>Remove unavailable meals to check out</button>
            <?php elseif (is_logged_in()): ?>
                <a class="cb-checkout cb-checkout-link" href="<?php echo $ROOT_DIR; ?>/views/checkout.php">Proceed to checkout</a>
            <?php else: ?>
                <a class="cb-checkout cb-checkout-signin" href="<?php echo $ROOT_DIR; ?>/views/login.php?redirect=checkout">Sign in to check out</a>
                <p class="cb-signin-note">New here? <a href="<?php echo $ROOT_DIR; ?>/views/register.php">Create an account</a></p>
            <?php endif; ?>
        </aside>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
