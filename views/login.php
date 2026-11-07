<?php
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
require_once __DIR__ . '/layout/header.php';

if (is_logged_in()) {
    header("Location: " . $ROOT_DIR . "/index.php");
    exit;
}

$toCheckout = ($_GET['redirect'] ?? '') === 'checkout';
?>

<section class="cb-auth">
    <h2>Welcome back</h2>
    <p class="cb-auth-lead"><?php echo $toCheckout ? 'Sign in to finish your order.' : 'Sign in to order from every campus kitchen.'; ?></p>

    <form action="<?php echo $ROOT_DIR; ?>/actions/login_action.php" method="POST">
        <?php if ($toCheckout): ?>
            <input type="hidden" name="redirect" value="checkout">
        <?php endif; ?>
        <div class="cb-field">
            <label for="customer_email">Email address</label>
            <input type="email" id="customer_email" name="customer_email" placeholder="name@ashesi.edu.gh" autocomplete="email" required>
        </div>
        <div class="cb-field">
            <label for="customer_pass">Password</label>
            <input type="password" id="customer_pass" name="customer_pass" autocomplete="current-password" required>
        </div>
        <button type="submit" class="cb-checkout">Sign in</button>
    </form>

    <p class="cb-auth-switch">New to CampusBite? <a href="<?php echo $ROOT_DIR; ?>/views/register.php">Create an account</a></p>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
