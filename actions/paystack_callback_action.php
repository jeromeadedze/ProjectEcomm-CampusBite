<?php
/**
 * Paystack sends the shopper here after paying (or cancelling), with ?reference=...
 * The payment is checked with Paystack's verify API before anything is saved; the browser is never trusted
 * for the amount or the meals. Only a verified, matching payment becomes an order, and only then is the bill emptied.
 * If the student never comes back here, Paystack's charge.success webhook creates the same order instead.
 */
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/PaystackOrders.php';

// Signed out while on Paystack (the session ran out): remember the payment, and check it right after sign-in
if (!is_logged_in()) {
    $ref = trim($_GET['reference'] ?? $_GET['trxref'] ?? '');
    if (preg_match('/^CB-[A-F0-9]{16}$/', $ref)) {
        $_SESSION['pending_payment_ref'] = $ref;
        $_SESSION['error'] = 'Sign in again to finish your order. Your payment is safe.';
    }
}
require_login();

function payment_failed($reason, $reference = '') {
    header('Location: ../views/payment_failed.php?reason=' . urlencode($reason) . ($reference !== '' ? '&ref=' . urlencode($reference) : ''));
    exit;
}

$reference = trim($_GET['reference'] ?? $_GET['trxref'] ?? '');

// Checks the payment with Paystack and creates the order, unless Paystack's webhook already did
// (controllers/PaystackOrders.php, shared with actions/paystack_webhook.php)
$result = (new PaystackOrders())->finish($reference, get_user_id());
if ($result['error'] === 'busy') {
    // The webhook is saving this order right now: it'll be there in a moment
    $result = (new PaystackOrders())->finish($reference, get_user_id());
}
if ($result['error']) {
    payment_failed($result['error'] === 'busy' ? 'pending' : $result['error'], preg_match(PaystackOrders::REFERENCE_RULE, $reference) ? $reference : '');
}

header('Location: ../views/payment_success.php?order_id=' . $result['order_id']);
exit;
