<?php
/**
 * Phones only (CSS hides it above 860px, where the order panel shows instead): a bar at the bottom of the screen
 * with the shopping bag (and how many meals) and the total, which opens the bill. It sits just above the floating tab menu,
 * and scrolling down shrinks it to a round bag on the right (js/main.js adds .is-mini).
 * js/main.js keeps the count and total up to date as meals are added. header.php sets $showBasket.
 */
if (empty($showBasket)) {
    return;
}
$basketTotal = $panelCount > 0 ? (float)$headerCart->getCartTotal(get_cart_token(), get_user_id()) : 0;
?>
<a class="cb-basket" href="<?php echo $ROOT_DIR; ?>/views/cart.php" data-basket <?php echo $panelCount > 0 ? '' : 'hidden'; ?>
   aria-label="View basket, <?php echo $panelCount . ($panelCount === 1 ? ' meal' : ' meals'); ?>, GH₵ <?php echo number_format($basketTotal, 2); ?>">
    <span class="cb-basket-bag" aria-hidden="true">
        <?php echo isset($icon) ? $icon('bag') : ''; // the same bag as the top bar's Cart button (nav.php) ?>
        <span class="cb-basket-count" data-basket-count><?php echo $panelCount; ?></span>
    </span>
    <span class="cb-basket-total" data-basket-total aria-hidden="true">GH₵ <?php echo number_format($basketTotal, 2); ?></span>
</a>
