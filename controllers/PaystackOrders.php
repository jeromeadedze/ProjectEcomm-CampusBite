<?php
/**
 * Turns a successful Paystack payment into an order. Used by both:
 *  - actions/paystack_callback_action.php: the student's browser comes back from Paystack after paying;
 *  - actions/paystack_webhook.php (charge.success): Paystack tells our server directly, so the order is created
 *    even if the student closed the tab before being sent back.
 * Whichever arrives first creates the order; the other finds it. A MySQL lock per payment reference makes them take
 * turns, and cb_payment.transaction_reference is UNIQUE as a backstop, so one payment can never make two orders.
 *
 * The payment is always checked with Paystack's verify API; nothing from the browser or the webhook body is trusted
 * for the amount or the meals. The meals, drop point, fee and split come from the metadata we sent at checkout.
 */
require_once __DIR__ . '/../core/paystack.php';
require_once __DIR__ . '/OrderController.php';
require_once __DIR__ . '/CartController.php';

class PaystackOrders
{
    const REFERENCE_RULE = '/^CB-[A-F0-9]{16}$/';

    /**
     * Makes sure the payment $reference has its order. $customerId: the signed-in student (browser), who must be the
     * one who paid; null for the webhook, which takes the customer from the payment.
     * Returns ['order_id' => int|null, 'created' => bool, 'error' => null or one of
     * 'invalid', 'unreachable', 'cancelled', 'declined', 'pending', 'mismatch', 'not_saved', 'busy'].
     */
    public function finish($reference, $customerId = null)
    {
        if (!preg_match(self::REFERENCE_RULE, (string)$reference)) {
            return $this->result(null, 'invalid');
        }
        $orderCtrl = new OrderController();
        $db = new OrderClass();

        // Take turns with any other request finishing the same payment (the browser and the webhook at once)
        $lockName = 'cb_pay_' . $reference;
        $got = $db->fetchOne("SELECT GET_LOCK(?, 15) AS got", [$lockName]);
        if (empty($got['got'])) {
            return $this->result(null, 'busy');
        }
        try {
            return $this->finishLocked($orderCtrl, $reference, $customerId);
        } finally {
            $db->fetchOne("SELECT RELEASE_LOCK(?) AS released", [$lockName]);
        }
    }

    private function finishLocked($orderCtrl, $reference, $customerId)
    {
        // Already an order (the other path got here first, or the page was reloaded)
        $existing = $orderCtrl->getOrderIdByReference($reference);
        if ($existing) {
            if ($customerId !== null && (int)$orderCtrl->getOrderById($existing)['customer_id'] !== (int)$customerId) {
                return $this->result(null, 'invalid');
            }
            return $this->result((int)$existing, null);
        }

        if (!paystack_configured()) {
            return $this->result(null, 'unreachable');
        }
        $verified = paystack_request('GET', '/transaction/verify/' . rawurlencode($reference));
        if ($verified === null) {
            return $this->result(null, 'unreachable');
        }
        $data = $verified['data'] ?? [];
        if (empty($verified['status']) || empty($data)) {
            return $this->result(null, 'invalid');
        }
        $status = $data['status'] ?? '';
        if ($status !== 'success') {
            // abandoned = closed or cancelled on Paystack's page; failed = declined by the bank or network
            return $this->result(null, $status === 'abandoned' ? 'cancelled' : ($status === 'failed' ? 'declined' : 'pending'));
        }

        $meta = $data['metadata'] ?? [];
        if (is_string($meta)) {
            $meta = json_decode($meta, true) ?: [];
        }
        $payer = (int)($meta['customer_id'] ?? 0);
        if (!$payer || empty($meta['items']) || empty($meta['brand_id']) || ($customerId !== null && $payer !== (int)$customerId)) {
            error_log("Paystack $reference: metadata missing or doesn't belong to customer " . ($customerId ?? 'from webhook'));
            return $this->result(null, 'invalid');
        }

        // What the order should cost, from the meals and fee fixed when the payment started
        $expected = (float)$meta['delivery_fee'];
        foreach ($meta['items'] as $item) {
            $expected += (float)$item['price'] * (int)$item['qty'];
        }
        $paidPesewas = (int)($data['amount'] ?? 0);
        if (($data['currency'] ?? '') !== 'GHS' || $paidPesewas !== to_pesewas($expected)) {
            error_log("Paystack $reference: paid $paidPesewas pesewas " . ($data['currency'] ?? '?') . ", expected " . to_pesewas($expected) . " GHS");
            return $this->result(null, 'mismatch');
        }

        // Who got what: Paystack reports the subaccount it paid; the shares come from the split set at checkout
        $split = null;
        $subaccount = (string)($data['subaccount']['subaccount_code'] ?? $meta['subaccount'] ?? '');
        if ($subaccount !== '') {
            $recovery = round((float)($meta['refund_recovery'] ?? 0), 2);
            $split = [
                'subaccount' => $subaccount,
                'campusbite_share' => round((float)($meta['commission'] ?? 0), 2),
                'refund_recovery' => $recovery,
                'kitchen_share' => round((float)($meta['kitchen_share'] ?? ($paidPesewas / 100 - (float)($meta['commission'] ?? 0) - $recovery)), 2),
            ];
        }
        $commissionRate = isset($meta['commission_rate']) ? (float)$meta['commission_rate'] : null;

        try {
            $orderCtrl->beginTransaction();
            $orderId = $orderCtrl->createOrder($payer, (int)$meta['brand_id'], null, $meta['delivery_zone'],
                (float)$meta['delivery_fee'], $meta['drop_location'], $meta['notes'] ?? '', $commissionRate, $meta['drop_point'] ?? null);
            if (!$orderId) {
                throw new RuntimeException('Order row was not created');
            }
            foreach ($meta['items'] as $item) {
                $orderCtrl->addOrderDetail($orderId, (int)$item['p_id'], (int)$item['qty'], (float)$item['price'], $item['notes'] ?? null);
            }
            $orderCtrl->recordPayment($paidPesewas / 100, $payer, $orderId, paystack_channel_label($data['channel'] ?? ''), $reference, true, $split);
            // The student is signed in to pay, so their bill is kept against their account: empty it
            (new CartController())->clearCart('', $payer);
            $orderCtrl->commit();
        } catch (Exception $e) {
            $orderCtrl->rollBack();
            $existing = $orderCtrl->getOrderIdByReference($reference);
            if ($existing) {
                return $this->result((int)$existing, null);
            }
            error_log("Paystack $reference: paid but the order could not be saved: " . $e->getMessage());
            return $this->result(null, 'not_saved');
        }

        // Receipt for the customer and a new-order alert for the kitchen (a failed email doesn't affect the order)
        require_once __DIR__ . '/OrderEmails.php';
        (new OrderEmails())->placed($orderId);
        return $this->result((int)$orderId, null, true);
    }

    private function result($orderId, $error, $created = false)
    {
        return ['order_id' => $orderId, 'created' => $created, 'error' => $error];
    }
}
