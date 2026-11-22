<?php
/**
 * Phones only (CSS hides it above 860px, where the order panel shows instead): a bar at the bottom of the screen
 * with what's in the bill, which opens the bill. While it shows it takes the place of the floating tab menu.
 * js/main.js keeps the count and total up to date as meals are added. header.php sets $showBasket.
 */
if (empty($showBasket)) {
    return;
}
$basketTotal = $panelCount > 0 ? (float)$headerCart->getCartTotal(get_cart_token(), get_user_id()) : 0;
?>
<a class="cb-basket" href="<?php echo $ROOT_DIR; ?>/views/cart.php" data-basket <?php echo $panelCount > 0 ? '' : 'hidden'; ?>>
    <span class="cb-basket-count" data-basket-count><?php echo $panelCount; ?></span>
    <span class="cb-basket-label">View basket</span>
    <span class="cb-basket-total" data-basket-total>GH₵ <?php echo number_format($basketTotal, 2); ?></span>
</a>
