<?php
/**
 * Right-hand order panel, translated from the theme's cb-panel snippet.
 * The theme's store credit and coupon blocks are left out because the database has no data behind them.
 */
require_once __DIR__ . '/../../controllers/CartController.php';
require_once __DIR__ . '/../../controllers/CustomerController.php';

$panelCart = new CartController();
$panelItems = $panelCart->getCartItems(get_ip(), get_user_id());
$panelCount = $panelCart->getCartCount(get_ip(), get_user_id());
$panelSubtotal = $panelCart->getCartTotal(get_ip(), get_user_id());
$panelDelivery = $panelCart->calculateDeliveryFee($panelItems);

// Group the tray by kitchen, keeping each kitchen's zone for its badge
$panelTray = [];
foreach ($panelItems as $panelItem) {
    $panelTray[$panelItem['brand_name']]['zone'] = $panelItem['zone'];
    $panelTray[$panelItem['brand_name']]['items'][] = $panelItem;
}

$panelCustomer = null;
if (is_logged_in()) {
    $panelCustomer = (new CustomerController())->getCustomerById(get_user_id());
}
$panelInitial = strtoupper(substr($_SESSION['customer_name'] ?? 'C', 0, 1));
$panelHostel = $panelCustomer['campus_hostel'] ?? '';
?>
<aside class="cb-panel" aria-label="Your order">
    <div class="cb-panel-top">
        <div class="cb-icons">
            <a href="<?php echo $ROOT_DIR; ?>/views/my_orders.php" aria-label="Order updates">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 16V11a6 6 0 0 1 12 0v5l2 2H4z"/><path d="M10 21h4"/></svg>
            </a>
            <a href="<?php echo $ROOT_DIR; ?>/views/cart.php" aria-label="Cart">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 4h2l2 12h11l2-8H6"/><circle cx="9" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/></svg>
                <?php if ($panelCount > 0): ?><span class="cb-dot"><?php echo $panelCount; ?></span><?php endif; ?>
            </a>
        </div>
        <?php if (is_logged_in()): ?>
            <a class="cb-avatar" href="<?php echo $ROOT_DIR; ?>/logout.php" aria-label="Log out" title="Log out"><?php echo htmlspecialchars($panelInitial); ?></a>
        <?php else: ?>
            <a class="cb-signin" href="<?php echo $ROOT_DIR; ?>/views/login.php">Sign in</a>
        <?php endif; ?>
    </div>

    <div class="cb-addr">
        <div class="cb-addr-head"><span>Deliver to</span></div>
        <strong><?php echo htmlspecialchars($panelHostel !== '' ? $panelHostel : 'Ashesi main campus'); ?></strong>
        <?php if (!is_logged_in()): ?>
            <p>Sign in to save your hall and room for faster delivery.</p>
        <?php endif; ?>
    </div>

    <div class="cb-menu">
        <h2>Order Menu</h2>
        <?php if (empty($panelTray)): ?>
            <p class="cb-empty" style="margin-top:14px">Your tray is empty. Add a dish to get started.</p>
        <?php else: ?>
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
        <?php endif; ?>
    </div>

    <div class="cb-totals">
        <div><span>Items</span><span><?php echo $panelCount; ?></span></div>
        <?php if ($panelCount > 0): ?>
            <div><span>Kitchens</span><span><?php echo count($panelTray); ?></span></div>
            <div><span>Delivery</span><span>GH₵ <?php echo number_format($panelDelivery, 2); ?></span></div>
        <?php endif; ?>
        <div class="total"><span>Subtotal</span><span>GH₵ <?php echo number_format($panelSubtotal, 2); ?></span></div>
    </div>

    <?php if (!is_logged_in()): ?>
        <a class="cb-checkout cb-checkout-signin" href="<?php echo $ROOT_DIR; ?>/views/login.php?redirect=checkout">Sign in to check out</a>
        <p class="cb-signin-note">Browse and fill your tray freely. You'll need an account to place the order.</p>
    <?php elseif ($panelCount > 0): ?>
        <a class="cb-checkout cb-checkout-link" href="<?php echo $ROOT_DIR; ?>/views/checkout.php">Checkout</a>
    <?php else: ?>
        <button type="button" class="cb-checkout" disabled>Checkout</button>
    <?php endif; ?>
</aside>
