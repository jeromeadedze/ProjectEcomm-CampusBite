<?php
// Forgot password: asks for the account's email and sends a reset link to it
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../core/mailer.php';

$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
if (is_logged_in()) {
    header("Location: " . $ROOT_DIR . "/views/settings.php");
    exit;
}

// Set by forgot_password_action.php once a request was handled
$sent = $_SESSION['reset_sent'] ?? null;
unset($_SESSION['reset_sent']);

require_once __DIR__ . '/layout/header.php';
?>

<section class="cb-auth">
    <?php if ($sent): ?>
        <h2>Check your email</h2>
        <p class="cb-auth-lead">
            If <b><?php echo htmlspecialchars($sent['email']); ?></b> belongs to a CampusBite account, we've sent it a link to choose a new password.
            The link works once, for the next hour. Check your spam folder if it doesn't arrive in a few minutes.
        </p>
        <?php if (!empty($sent['link'])): ?>
            <div class="cb-dev-note">
                <b>Test mode:</b> emails are saved to <code>storage/mail/</code> instead of being sent, so here is the link.
                <a class="cb-link" href="<?php echo htmlspecialchars($sent['link']); ?>">Open the reset link ›</a>
            </div>
        <?php endif; ?>
        <a class="cb-checkout" href="<?php echo $ROOT_DIR; ?>/views/login.php">Back to sign in</a>
        <p class="cb-auth-switch">Wrong address? <a href="forgot_password.php">Try another email</a></p>
    <?php else: ?>
        <h2>Forgot your password?</h2>
        <p class="cb-auth-lead">Enter the email you signed up with and we'll send you a link to choose a new password.</p>

        <form action="<?php echo $ROOT_DIR; ?>/actions/forgot_password_action.php" method="POST">
            <?php echo csrf_field(); ?>
            <div class="cb-field">
                <label for="customer_email">Email address</label>
                <input type="email" id="customer_email" name="customer_email" placeholder="name@ashesi.edu.gh" autocomplete="email" maxlength="100" required>
            </div>
            <button type="submit" class="cb-checkout">Send reset link</button>
        </form>

        <p class="cb-auth-switch">Remembered it? <a href="<?php echo $ROOT_DIR; ?>/views/login.php">Sign in</a></p>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
