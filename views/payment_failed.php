<?php
// When a Paystack payment didn't go through or couldn't be confirmed. The bill is left as it was.
require_once __DIR__ . '/../core/core.php';
require_login();
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';

$reasons = [
    'cancelled' => ['Payment cancelled', "You closed the payment page before paying, so nothing was charged. Your bill is still here when you're ready."],
    'declined' => ['Payment declined', "Your bank or mobile money provider declined the payment, so nothing was charged. Check your balance or try another method."],
    'pending' => ['Payment still processing', "Paystack hasn't confirmed this payment yet. If you approved it on your phone, wait a moment and check again."],
    'unreachable' => ["We couldn't confirm your payment", "We couldn't reach Paystack to check this payment. If money left your account, check again in a moment. Don't pay twice."],
    'mismatch' => ["Payment doesn't match your order", "The amount paid doesn't match your order, so we didn't place it. Contact support with the reference below and we'll sort out a refund."],
    'not_saved' => ["Payment received, order not saved", "Your payment went through, but we couldn't save the order. Contact support with the reference below. Don't pay again."],
    'invalid' => ["We couldn't find that payment", "This payment link isn't valid for your account. Your bill hasn't changed."],
];
$reason = array_key_exists($_GET['reason'] ?? '', $reasons) ? $_GET['reason'] : 'invalid';
[$title, $message] = $reasons[$reason];
$reference = preg_match('/^CB-[A-F0-9]{16}$/', $_GET['ref'] ?? '') ? $_GET['ref'] : '';
$canRetryCheck = $reference !== '' && in_array($reason, ['pending', 'unreachable'], true);
$needsSupport = in_array($reason, ['mismatch', 'not_saved'], true);

$hidePageTitle = true;
$pageHeading = $title;
require_once __DIR__ . '/layout/header.php';
?>

<section class="cb-card cb-result">
    <span class="cb-result-icon" aria-hidden="true"><i class="bi <?php echo $needsSupport ? 'bi-exclamation-lg' : 'bi-x-lg'; ?>"></i></span>
    <h1><?php echo htmlspecialchars($title); ?></h1>
    <p><?php echo htmlspecialchars($message); ?></p>
    <?php if ($reference !== ''): ?>
        <p class="cb-field-hint">Paystack reference <?php echo htmlspecialchars($reference); ?></p>
    <?php endif; ?>

    <div class="cb-result-actions">
        <?php if ($canRetryCheck): ?>
            <a class="cb-checkout cb-checkout-link" href="<?php echo $ROOT_DIR; ?>/actions/paystack_callback_action.php?reference=<?php echo urlencode($reference); ?>">Check again</a>
        <?php elseif ($needsSupport): ?>
            <a class="cb-checkout cb-checkout-link" href="<?php echo $ROOT_DIR; ?>/views/contact.php">Contact support</a>
        <?php else: ?>
            <a class="cb-checkout cb-checkout-link" href="<?php echo $ROOT_DIR; ?>/views/checkout.php">Try again</a>
        <?php endif; ?>
        <a class="cb-btn cb-btn-quiet" href="<?php echo $ROOT_DIR; ?>/views/cart.php">Back to my bill</a>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
