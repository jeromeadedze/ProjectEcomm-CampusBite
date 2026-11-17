<?php
/**
 * One-time success and error messages set by the action files.
 */
$flashMessages = [
    'error' => ['role' => 'alert', 'icon' => 'bi-exclamation-circle-fill'],
    'success' => ['role' => 'status', 'icon' => 'bi-check-circle-fill'],
];
?>
<div class="cb-toasts">
    <?php if (!empty($_SESSION['cart_conflict'])): ?>
        <?php $conflict = $_SESSION['cart_conflict']; unset($_SESSION['cart_conflict']); ?>
        <div class="cb-toast cb-toast-error cb-toast-choice" role="alertdialog" aria-labelledby="cb-conflict-text">
            <i class="bi bi-shop" aria-hidden="true"></i>
            <div>
                <p id="cb-conflict-text">Your bill has meals from <strong><?php echo htmlspecialchars($conflict['bill_kitchen']); ?></strong>. Each order comes from one kitchen. Start a new bill with <?php echo htmlspecialchars($conflict['meal']); ?> from <?php echo htmlspecialchars($conflict['new_kitchen']); ?>?</p>
                <div class="cb-toast-actions">
                    <form action="<?php echo $ROOT_DIR; ?>/actions/add_to_cart_action.php" method="POST">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="product_id" value="<?php echo (int)$conflict['product_id']; ?>">
                        <input type="hidden" name="qty" value="<?php echo (int)$conflict['qty']; ?>">
                        <input type="hidden" name="notes" value="<?php echo htmlspecialchars($conflict['notes']); ?>">
                        <input type="hidden" name="replace" value="1">
                        <button type="submit" class="cb-btn">Start new bill</button>
                    </form>
                    <button type="button" class="cb-btn cb-btn-quiet" onclick="this.closest('.cb-toast').remove();">Keep current bill</button>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php foreach ($flashMessages as $flashType => $flashStyle): ?>
        <?php if (!empty($_SESSION[$flashType])): ?>
            <div class="cb-toast cb-toast-<?php echo $flashType; ?>" role="<?php echo $flashStyle['role']; ?>">
                <i class="bi <?php echo $flashStyle['icon']; ?>" aria-hidden="true"></i>
                <p><?php echo htmlspecialchars($_SESSION[$flashType]); ?></p>
                <button type="button" aria-label="Dismiss message" onclick="this.closest('.cb-toast').remove();"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </div>
            <?php unset($_SESSION[$flashType]); ?>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
