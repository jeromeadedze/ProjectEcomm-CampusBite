<?php
require_once __DIR__ . "/OrderController.php";
require_once __DIR__ . "/../classes/CustomerClass.php";
require_once __DIR__ . "/../core/mailer.php";

/**
 * Emails about orders: the customer's receipt, a new-order alert for the kitchen, the customer's status
 * updates and cancellation notices. Each one is sent after the change is saved, and a failed email never
 * undoes or blocks the change (see send_email()).
 */
class OrderEmails
{
    // Statuses the customer hears about. "Ready" is skipped: it's the kitchen's own step before delivery.
    const CUSTOMER_STATUSES = ['Preparing', 'In Transit', 'Delivered'];

    private $orderCtrl;

    public function __construct()
    {
        $this->orderCtrl = new OrderController();
    }

    private function money($amount)
    {
        return 'GH₵ ' . number_format((float)$amount, 2);
    }

    private function firstName($name)
    {
        return explode(' ', trim((string)$name))[0];
    }

    private function trackingLink($order)
    {
        return app_url('views/order_tracking.php?order_id=' . (int)$order['order_id']);
    }

    // Meal lines, delivery and total, as email table rows
    private function billRows($order)
    {
        $rows = [];
        $meals = 0.0;
        foreach ($this->orderCtrl->getOrderDetails($order['order_id']) as $line) {
            $lineTotal = (float)$line['unit_price'] * (int)$line['qty'];
            $meals += $lineTotal;
            $rows[] = [(int)$line['qty'] . '× ' . $line['product_title'], $this->money($lineTotal)];
        }
        $rows[] = ['Delivery' . (!empty($order['drop_point']) ? ' to ' . $order['drop_point'] : ''), $this->money($order['delivery_fee'])];
        $rows[] = ['Total paid', $this->money($order['amt'] ?? ($meals + (float)$order['delivery_fee'])), true];
        return $rows;
    }

    // The kitchen's staff logins, to tell them about new and cancelled orders
    private function kitchenStaff($brandId)
    {
        return array_filter((new CustomerClass())->getKitchenAccounts(), function ($a) use ($brandId) {
            return (int)$a['brand_id'] === (int)$brandId;
        });
    }

    private function send($to, $name, $subject, $heading, $lines, $button, $link, $foot = null, $rows = [])
    {
        return send_email($to, $name, $subject,
            email_html($heading, $lines, $button, $link, $foot, $rows),
            email_text($heading, $lines, $button, $link, $foot, $rows));
    }

    /** A new paid order: the customer's receipt, and an alert to each of the kitchen's staff logins. */
    public function placed($orderId)
    {
        $order = $this->orderCtrl->getOrderById($orderId);
        if (!$order) {
            return;
        }
        $rows = $this->billRows($order);
        $kitchen = $order['brand_name'] ?: 'The kitchen';

        $lines = ['Hi ' . $this->firstName($order['customer_name']) . ',',
                  "Thanks for your order. $kitchen has it and will start on it shortly. We'll email you when it's being prepared, when it's on the way and when it's delivered.",
                  'Delivering to: ' . $order['drop_location'] . (!empty($order['special_instructions']) ? "\nYour note: " . $order['special_instructions'] : '')];
        $paidWith = trim(($order['payment_mode'] ?? '') . ' · ref ' . ($order['transaction_reference'] ?? ''), ' ·');
        $this->send($order['customer_email'], $order['customer_name'], "Your CampusBite receipt, order #{$order['invoice_no']}",
            "Order #{$order['invoice_no']} is placed", $lines, 'Track your order', $this->trackingLink($order),
            'Paid ' . date('M j, Y, g:i A', strtotime($order['order_date'])) . ($paidWith ? " with $paidWith" : '') . '. Keep this email as your receipt.', $rows);

        foreach ($this->kitchenStaff($order['brand_id']) as $staff) {
            $lines = ['Hi ' . $this->firstName($staff['customer_name']) . ',',
                      "{$order['customer_name']} just paid for order #{$order['invoice_no']}.",
                      'Deliver to: ' . $order['drop_location'] . ' · ' . ($order['customer_contact'] ?? '') . (!empty($order['special_instructions']) ? "\nNote: " . $order['special_instructions'] : '')];
            $this->send($staff['customer_email'], $staff['customer_name'], "New order #{$order['invoice_no']} for $kitchen",
                "New order #{$order['invoice_no']}", $lines, 'Open your orders', app_url('views/kitchen/kitchen_orders.php'), null, $rows);
        }
    }

    /** The kitchen moved an order on: tells the customer, for the stages they care about. */
    public function statusChanged($orderId, $status)
    {
        if (!in_array($status, self::CUSTOMER_STATUSES, true)) {
            return;
        }
        $order = $this->orderCtrl->getOrderById($orderId);
        if (!$order) {
            return;
        }
        $kitchen = $order['brand_name'] ?: 'The kitchen';
        $hi = 'Hi ' . $this->firstName($order['customer_name']) . ',';
        $copy = [
            'Preparing' => ["$kitchen is preparing your order", "$kitchen has started on order #{$order['invoice_no']}. We'll email you again when it's on the way."],
            'In Transit' => ['Your order is on the way', "$kitchen is bringing order #{$order['invoice_no']} to {$order['drop_location']}. Keep your phone close: they'll call " . ($order['customer_contact'] ?: 'you') . " if they can't find you."],
            'Delivered' => ['Your order was delivered', "Order #{$order['invoice_no']} was delivered to {$order['drop_location']}. Enjoy your meal! If anything was wrong, open the order and tap Get help."],
        ][$status];
        $this->send($order['customer_email'], $order['customer_name'], $copy[0] . " (order #{$order['invoice_no']})",
            $copy[0], [$hi, $copy[1]], $status === 'Delivered' ? 'View your order' : 'Track your order', $this->trackingLink($order));
    }

    /**
     * An order was cancelled: tells the customer (with the refund), and tells the kitchen when it wasn't the
     * one that cancelled, so it stops cooking.
     */
    public function cancelled($orderId)
    {
        $order = $this->orderCtrl->getOrderById($orderId);
        if (!$order || $order['order_status'] !== 'Cancelled') {
            return;
        }
        $by = $order['cancelled_by'];
        $kitchen = $order['brand_name'] ?: 'The kitchen';
        $reason = trim((string)$order['cancel_reason']);
        $who = ['customer' => 'You cancelled', 'kitchen' => "$kitchen couldn't take", 'admin' => 'CampusBite cancelled'][$by] ?? 'We cancelled';
        $lines = ['Hi ' . $this->firstName($order['customer_name']) . ',',
                  "$who order #{$order['invoice_no']}" . ($reason !== '' ? ". Reason: $reason" : '') . '.'];
        $refund = (float)($order['refund_amount'] ?? 0);
        if ($refund > 0) {
            $lines[] = $this->money($refund) . ' is being refunded to the ' . ($order['payment_mode'] ?: 'account') . ' you paid with. Mobile Money refunds usually arrive within a day; card refunds can take a few days.';
        } else {
            $lines[] = 'Nothing was charged for this order, so there is nothing to refund.';
        }
        $this->send($order['customer_email'], $order['customer_name'], "Order #{$order['invoice_no']} was cancelled",
            "Order #{$order['invoice_no']} was cancelled", $lines, 'View the order', $this->trackingLink($order));

        if ($by !== 'kitchen') {
            foreach ($this->kitchenStaff($order['brand_id']) as $staff) {
                $lines = ['Hi ' . $this->firstName($staff['customer_name']) . ',',
                          ($by === 'customer' ? $order['customer_name'] : 'CampusBite') . " cancelled order #{$order['invoice_no']}" . ($reason !== '' ? ". Reason: $reason" : '') . '. Please stop preparing it. The customer is refunded in full.'];
                $this->send($staff['customer_email'], $staff['customer_name'], "Order #{$order['invoice_no']} was cancelled",
                    "Stop: order #{$order['invoice_no']} was cancelled", $lines, 'Open your orders', app_url('views/kitchen/kitchen_orders.php'));
            }
        }
    }
}
?>
