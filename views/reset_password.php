<?php
// Reset password: opened from the link in a forgot-password email
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

// The token is in this page's address, so don't pass the address on to fonts or other sites
header('Referrer-Policy: no-referrer');

$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
$token = (string)($_GET['token'] ?? '');
$reset = (new CustomerController())->findPasswordReset($token);

require_once __DIR__ . '/layout/header.php';
?>

<section class="cb-auth">
    <?php if (!$reset): ?>
        <h2>This link doesn't work any more</h2>
        <p class="cb-auth-lead">Reset links work once, for an hour after they're sent. Asking for a new link also stops the older ones working. Ask for a new link and use the newest email.</p>
        <a class="cb-checkout" href="forgot_password.php">Send a new link</a>
        <p class="cb-auth-switch"><a href="<?php echo $ROOT_DIR; ?>/views/login.php">Back to sign in</a></p>
    <?php else: ?>
        <h2>Choose a new password</h2>
        <p class="cb-auth-lead">For <b><?php echo htmlspecialchars($reset['customer_email']); ?></b>. You'll sign in with it next.</p>

        <form action="<?php echo $ROOT_DIR; ?>/actions/reset_password_action.php" method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <?php echo form_message_html(take_form_message('reset')); ?>
            <div class="cb-field">
                <label for="new_pass">New password</label>
                <input type="password" id="new_pass" name="new_pass" autocomplete="new-password" minlength="8" aria-describedby="new_pass_hint" required>
                <p class="cb-field-hint" id="new_pass_hint">At least 8 characters with an uppercase letter, a lowercase letter, a number and a symbol.</p>
            </div>
            <div class="cb-field">
                <label for="confirm_pass">Type it again</label>
                <input type="password" id="confirm_pass" name="confirm_pass" autocomplete="new-password" minlength="8" required>
            </div>
            <button type="submit" class="cb-checkout">Save new password</button>
        </form>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
