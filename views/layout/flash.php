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
