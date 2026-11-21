<?php
/**
 * Paystack sends the shopper here after paying (or cancelling), with ?reference=...
 * The payment is checked with Paystack's verify API before anything is saved; the browser is never trusted
 * for the amount or the meals. Only a verified, matching payment becomes an order, and only then is the bill emptied.
 */
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../core/paystack.php';
require_once __DIR__ . '/../controllers/CartController.php';
require_once __DIR__ . '/../controllers/OrderController.php';

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
if (!preg_match('/^CB-[A-F0-9]{16}$/', $reference)) {
    payment_failed('invalid');
}

$userId = get_user_id();
$orderCtrl = new OrderController();

// Already turned into an order (the page was refreshed or opened twice)
$existing = $orderCtrl->getOrderIdByReference($reference);
if ($existing) {
    $order = $orderCtrl->getOrderById($existing);
    if ((int)$order['customer_id'] !== $userId) {
        payment_failed('invalid');
    }
    header('Location: ../views/payment_success.php?order_id=' . $existing);
    exit;
}

if (!paystack_configured()) {
    payment_failed('unreachable', $reference);
}
$result = paystack_request('GET', '/transaction/verify/' . rawurlencode($reference));
if ($result === null) {
    payment_failed('unreachable', $reference);
}
$data = $result['data'] ?? [];
if (empty($result['status']) || empty($data)) {
    payment_failed('invalid', $reference);
}

$status = $data['status'] ?? '';
if ($status !== 'success') {
    // abandoned = closed or cancelled on Paystack's page; failed = declined by the bank or network
    payment_failed($status === 'abandoned' ? 'cancelled' : ($status === 'failed' ? 'declined' : 'pending'), $reference);
}

$meta = $data['metadata'] ?? [];
if (is_string($meta)) {
    $meta = json_decode($meta, true) ?: [];
}
if ((int)($meta['customer_id'] ?? 0) !== $userId || empty($meta['items']) || empty($meta['brand_id'])) {
    error_log("Paystack $reference: metadata doesn't belong to customer $userId");
    payment_failed('invalid', $reference);
}

// What the order should cost, from the meals and fee fixed when the payment started
$expected = (float)$meta['delivery_fee'];
foreach ($meta['items'] as $item) {
    $expected += (float)$item['price'] * (int)$item['qty'];
}
$paidPesewas = (int)($data['amount'] ?? 0);
if (($data['currency'] ?? '') !== 'GHS' || $paidPesewas !== to_pesewas($expected)) {
    error_log("Paystack $reference: paid $paidPesewas pesewas " . ($data['currency'] ?? '?') . ", expected " . to_pesewas($expected) . " GHS");
    payment_failed('mismatch', $reference);
}

// Who got what: Paystack reports the subaccount it paid; the shares come from the split set at checkout
$split = null;
$subaccount = (string)($data['subaccount']['subaccount_code'] ?? $meta['subaccount'] ?? '');
if ($subaccount !== '') {
    $split = [
        'subaccount' => $subaccount,
        'campusbite_share' => round((float)($meta['commission'] ?? 0), 2),
        'kitchen_share' => round((float)($meta['kitchen_share'] ?? ($paidPesewas / 100 - (float)($meta['commission'] ?? 0))), 2),
    ];
}
$commissionRate = isset($meta['commission_rate']) ? (float)$meta['commission_rate'] : null;

$cartCtrl = new CartController();
try {
    $orderCtrl->beginTransaction();

    $orderId = $orderCtrl->createOrder($userId, (int)$meta['brand_id'], mt_rand(100000, 999999), $meta['delivery_zone'],
        (float)$meta['delivery_fee'], $meta['drop_location'], $meta['notes'] ?? '', $commissionRate);
    if (!$orderId) {
        throw new RuntimeException('Order row was not created');
    }
    foreach ($meta['items'] as $item) {
        $orderCtrl->addOrderDetail($orderId, (int)$item['p_id'], (int)$item['qty'], (float)$item['price'], $item['notes'] ?? null);
    }
    $orderCtrl->recordPayment($paidPesewas / 100, $userId, $orderId, paystack_channel_label($data['channel'] ?? ''), $reference, true, $split);
    $cartCtrl->clearCart(get_cart_token(), $userId);

    $orderCtrl->commit();
} catch (Exception $e) {
    $orderCtrl->rollBack();
    // Two tabs finishing at once: the other one already saved this payment's order
    $existing = $orderCtrl->getOrderIdByReference($reference);
    if ($existing) {
        header('Location: ../views/payment_success.php?order_id=' . $existing);
        exit;
    }
    error_log("Paystack $reference: paid but the order could not be saved: " . $e->getMessage());
    payment_failed('not_saved', $reference);
}

// Receipt for the customer and a new-order alert for the kitchen (a failed email doesn't affect the order)
require_once __DIR__ . '/../controllers/OrderEmails.php';
(new OrderEmails())->placed($orderId);

header('Location: ../views/payment_success.php?order_id=' . $orderId);
exit;
