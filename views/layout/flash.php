<?php
/**
 * The "Start a new basket?" sheet (without JavaScript), and the holder for pop-up alerts that js/main.js shows
 * (a new order in the kitchen portal, a connection problem). One-time messages from actions
 * ($_SESSION['success'] / ['error']) are shown inside the page instead: views/layout/page_message.php.
 */
?>
<?php if (!empty($_SESSION['cart_conflict'])): ?>
    <?php $conflict = $_SESSION['cart_conflict']; unset($_SESSION['cart_conflict']); ?>
    <?php // A meal from another kitchen was added (without JavaScript; js/main.js draws the same sheet itself) ?>
    <div class="cb-sheet" data-sheet>
        <div class="cb-sheet-backdrop" data-sheet-close></div>
        <div class="cb-sheet-card" role="dialog" aria-modal="true" aria-labelledby="cb-sheet-title">
            <span class="cb-sheet-icon" aria-hidden="true"><i class="bi bi-shop"></i></span>
            <h2 id="cb-sheet-title">Start a new basket?</h2>
            <p>Your basket has meals from <strong><?php echo htmlspecialchars($conflict['bill_kitchen']); ?></strong>. Each order comes from one kitchen, so adding <?php echo htmlspecialchars($conflict['meal']); ?> from <?php echo htmlspecialchars($conflict['new_kitchen']); ?> empties it first.</p>
            <form action="<?php echo $ROOT_DIR; ?>/actions/add_to_cart_action.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="product_id" value="<?php echo (int)$conflict['product_id']; ?>">
                <input type="hidden" name="qty" value="<?php echo (int)$conflict['qty']; ?>">
                <input type="hidden" name="notes" value="<?php echo htmlspecialchars($conflict['notes']); ?>">
                <input type="hidden" name="replace" value="1">
                <button type="submit" class="cb-checkout">Start new basket</button>
            </form>
            <button type="button" class="cb-sheet-keep" data-sheet-close>Keep my <?php echo htmlspecialchars($conflict['bill_kitchen']); ?> basket</button>
        </div>
    </div>
<?php endif; ?>
<div class="cb-toasts"></div>
