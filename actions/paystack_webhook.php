<?php
/**
 * Paystack webhook: Paystack calls this when something happens to a payment, so refund status stays current
 * without anyone opening the order (before, views/order_tracking.php asked Paystack at most once a minute).
 *
 * Set it in the Paystack dashboard: Settings > API Keys & Webhooks > Webhook URL =
 *   https://<the site>/actions/paystack_webhook.php
 * Only requests signed with our secret key are believed (X-Paystack-Signature: HMAC-SHA512 of the body).
 * Handled: refund.pending / refund.processing / refund.processed / refund.failed. Anything else is acknowledged
 * and ignored, so Paystack doesn't keep retrying it.
 */
require_once __DIR__ . '/../core/paystack.php';
require_once __DIR__ . '/../classes/OrderClass.php';

function webhook_reply($status, $text) {
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $text;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    webhook_reply(405, 'POST only');
}
if (!paystack_configured()) {
    webhook_reply(503, 'Paystack is not set up');
}

$body = file_get_contents('php://input');
$sent = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
if ($body === '' || $body === false || !is_string($sent) || !hash_equals(hash_hmac('sha512', $body, PAYSTACK_SECRET_KEY), $sent)) {
    error_log('Paystack webhook: bad or missing signature');
    webhook_reply(401, 'Bad signature');
}

$event = json_decode($body, true);
$name = (string)($event['event'] ?? '');
$data = is_array($event['data'] ?? null) ? $event['data'] : [];

$refundStatuses = [
    'refund.pending' => 'pending',
    'refund.processing' => 'processing',
    'refund.processed' => 'processed',
    'refund.failed' => 'failed',
];
if (isset($refundStatuses[$name])) {
    $reference = (string)($data['transaction_reference'] ?? ($data['transaction']['reference'] ?? ''));
    $refundId = (string)($data['id'] ?? ($data['refund_id'] ?? ''));
    $updated = (new OrderClass())->updateRefundFromPaystack($refundId, $reference, $refundStatuses[$name]);
    if (!$updated) {
        error_log("Paystack webhook: $name for $reference (refund $refundId) matched no refund here");
    }
}

webhook_reply(200, 'ok');
