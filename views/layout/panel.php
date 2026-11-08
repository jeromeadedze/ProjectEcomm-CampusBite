<?php
/**
 * Right-hand order panel, translated from the theme's cb-panel snippet.
 * header.php decides whether it shows ($showPanel) and has already loaded the cart ($panelItems, $panelCount).
 * The theme's store credit and coupon blocks are left out because the database has no data behind them.
 */
if (empty($showPanel)) {
    return;
}

require_once __DIR__ . '/../../controllers/CustomerController.php';

$panelSubtotal = $headerCart->getCartTotal(get_cart_token(), get_user_id());
$panelDelivery = $headerCart->calculateDeliveryFee($panelItems);

// Group the tray by kitchen, keeping each kitchen's zone for its badge
$panelTray = [];
foreach ($panelItems as $panelItem) {
    $panelTray[$panelItem['brand_name']]['zone'] = $panelItem['zone'];
    $panelTray[$panelItem['brand_name']]['items'][] = $panelItem;
}

$panelCustomer = is_logged_in() ? (new CustomerController())->getCustomerById(get_user_id()) : null;
$panelHostel = $panelCustomer['campus_hostel'] ?? '';
?>
<aside class="cb-panel" id="cb-panel" aria-label="Your order">
    <div class="cb-panel-head">
        <h2>Your order</h2>
        <button type="button" class="cb-panel-close" id="cb-panel-close" aria-label="Close order panel" title="Close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
    </div>

    <div class="cb-addr">
        <div class="cb-addr-head"><span>Deliver to</span></div>
        <strong><?php echo htmlspecialchars($panelHostel !== '' ? $panelHostel : 'Ashesi main campus'); ?></strong>
        <?php if (!is_logged_in()): ?>
            <p>Sign in to save your hall and room for faster delivery.</p>
        <?php endif; ?>
    </div>

    <div class="cb-menu">
        <?php foreach ($panelTray as $kitchenName => $kitchenTray): ?>
            <div class="cb-tray-vendor">
                <span><?php echo htmlspecialchars($kitchenName); ?></span>
                <em class="cb-zone <?php echo $kitchenTray['zone'] === 'Zone B' ? 'cb-zone-b' : 'cb-zone-a'; ?>"><?php echo htmlspecialchars($kitchenTray['zone']); ?></em>
            </div>
            <ul>
                <?php foreach ($kitchenTray['items'] as $trayItem): ?>
                    <?php $trayImage = 'images/products/' . ($trayItem['product_image'] ?? ''); ?>
                    <li>
                        <span class="cb-menu-img">
                            <?php if (!empty($trayItem['product_image']) && file_exists(__DIR__ . '/../../' . $trayImage)): ?>
                                <img src="<?php echo $ROOT_DIR . '/' . htmlspecialchars($trayImage); ?>" alt="">
                            <?php else: ?>
                                <i class="bi bi-egg-fried" aria-hidden="true"></i>
                            <?php endif; ?>
                        </span>
                        <div>
                            <b><?php echo htmlspecialchars($trayItem['product_title']); ?></b>
                            <small>x<?php echo (int)$trayItem['qty']; ?></small>
                        </div>
                        <span class="cb-menu-price">GH₵ <?php echo number_format((float)$trayItem['product_price'] * (int)$trayItem['qty'], 2); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </div>

    <div class="cb-totals">
        <div><span>Items</span><span><?php echo $panelCount; ?></span></div>
        <div><span>Kitchens</span><span><?php echo count($panelTray); ?></span></div>
        <div><span>Delivery</span><span>GH₵ <?php echo number_format($panelDelivery, 2); ?></span></div>
        <div class="total"><span>Subtotal</span><span>GH₵ <?php echo number_format($panelSubtotal, 2); ?></span></div>
    </div>

    <?php if (!is_logged_in()): ?>
        <a class="cb-checkout cb-checkout-signin" href="<?php echo $ROOT_DIR; ?>/views/login.php?redirect=checkout">Sign in to check out</a>
        <p class="cb-signin-note">Browse and fill your tray freely. You'll need an account to place the order.</p>
    <?php else: ?>
        <a class="cb-checkout cb-checkout-link" href="<?php echo $ROOT_DIR; ?>/views/checkout.php">Checkout</a>
    <?php endif; ?>
    <a class="cb-panel-bills" href="<?php echo $ROOT_DIR; ?>/views/cart.php">View or edit bill</a>
</aside>
