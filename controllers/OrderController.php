<?php
require_once __DIR__ . "/../classes/OrderClass.php";

class OrderController
{
    // The only statuses an order can have, in pipeline order, with their timeline labels
    const STATUSES = [
        'Placed' => 'Order Received by Kitchen',
        'Preparing' => 'Kitchen Preparing Meal',
        'Ready' => 'Packed and Ready to Leave the Kitchen',
        'In Transit' => 'Kitchen on the Way to You',
        'Delivered' => 'Delivered to Drop Point',
    ];

    // The button a kitchen presses to move an order to its next stage. Kitchens deliver their own orders.
    const NEXT_STEP = [
        'Placed' => ['status' => 'Preparing', 'label' => 'Start preparing'],
        'Preparing' => ['status' => 'Ready', 'label' => 'Mark ready'],
        'Ready' => ['status' => 'In Transit', 'label' => 'Out for delivery'],
        'In Transit' => ['status' => 'Delivered', 'label' => 'Mark delivered'],
    ];

    // Who can cancel an order, and up to which stage. Customers only before the kitchen starts;
    // the kitchen can reject it until it's packed; the admin can cancel anything not yet delivered.
    const CANCELLABLE_FROM = [
        'customer' => ['Placed'],
        'kitchen' => ['Placed', 'Preparing'],
        'admin' => ['Placed', 'Preparing', 'Ready', 'In Transit'],
    ];

    // Reasons a kitchen can pick when it rejects an order
    const KITCHEN_REJECT_REASONS = [
        'A meal ran out',
        'Too busy to make it in time',
        'Kitchen is closing',
        "Can't deliver to that drop point",
    ];

    // How each refund status reads to people
    const REFUND_LABELS = [
        'pending' => 'Refund started',
        'processing' => 'Refund processing',
        'processed' => 'Refunded',
        'failed' => 'Refund failed',
        'needs-attention' => 'Refund needs attention',
        'not_needed' => 'No refund needed',
    ];

    private $orderModel;

    public function __construct()
    {
        $this->orderModel = new OrderClass();
    }

    public function createOrder($customerId, $brandId, $invoiceNo, $deliveryZone, $deliveryFee, $dropLocation, $notes = '')
    {
        return $this->orderModel->createOrder($customerId, $brandId, $invoiceNo, $deliveryZone, $deliveryFee, $dropLocation, $notes);
    }

    public function addOrderDetail($orderId, $productId, $qty, $price, $notes = null)
    {
        return $this->orderModel->addOrderDetail($orderId, $productId, $qty, $price, $notes);
    }

    public function getKitchenOrders($brandId, $scope = 'active')
    {
        return $this->orderModel->getKitchenOrders($brandId, $scope);
    }

    /**
     * Sales figures for a kitchen's dashboard: today, the last 7 days and all time, each with
     * CampusBite's commission and what the kitchen keeps (meal sales minus commission, plus delivery fees).
     * $commissionRate is unused: each order's saved rate is used instead. It stays for older callers.
     */
    public function getKitchenSummary($brandId, $commissionRate)
    {
        $periods = [
            'today' => date('Y-m-d 00:00:00'),
            'week' => date('Y-m-d 00:00:00', strtotime('-6 days')),
            'all' => null,
        ];
        $summary = [];
        foreach ($periods as $key => $since) {
            $row = $this->orderModel->getKitchenSales($brandId, $since);
            $sales = (float)$row['sales'];
            $commission = round((float)$row['commission'], 2);
            $summary[$key] = [
                'orders' => (int)$row['orders'],
                'meals' => (int)$row['meals'],
                'sales' => $sales,
                'commission' => $commission,
                'delivery' => (float)$row['delivery'],
                // The kitchen delivers its own orders, so it keeps the delivery fee too
                'payout' => $sales - $commission + (float)$row['delivery'],
            ];
        }
        return $summary;
    }

    /**
     * Platform figures for the admin dashboard: today, last 7 days and all time.
     * CampusBite earns its commission on meals. Delivery fees go to the kitchens, which deliver their own orders.
     */
    public function getPlatformSummary()
    {
        $periods = [
            'today' => date('Y-m-d 00:00:00'),
            'week' => date('Y-m-d 00:00:00', strtotime('-6 days')),
            'all' => null,
        ];
        $summary = [];
        foreach ($periods as $key => $since) {
            $row = $this->orderModel->getPlatformSales($since);
            $fees = $this->orderModel->getDeliveryFees($since);
            $commission = round((float)$row['commission'], 2);
            $summary[$key] = [
                'orders' => (int)$row['orders'],
                'sales' => (float)$row['sales'],
                'commission' => $commission,
                'delivery' => $fees,
                'earned' => $commission,
            ];
        }
        return $summary;
    }

    public function countOrdersByStatus()
    {
        return $this->orderModel->countOrdersByStatus();
    }

    public function getKitchenOverview($since)
    {
        return $this->orderModel->getKitchenOverview($since);
    }

    public function getWaitingOrders($minutes)
    {
        return $this->orderModel->getWaitingOrders($minutes);
    }

    public function countKitchenOrdersByStatus($brandId)
    {
        return $this->orderModel->countKitchenOrdersByStatus($brandId);
    }

    public function getKitchenBestSellers($brandId, $limit = 5)
    {
        return $this->orderModel->getKitchenBestSellers($brandId, $limit);
    }

    public function beginTransaction()
    {
        return $this->orderModel->beginTransaction();
    }

    public function commit()
    {
        return $this->orderModel->commit();
    }

    public function rollBack()
    {
        return $this->orderModel->rollBack();
    }

    public function recordPayment($amt, $customerId, $orderId, $paymentMode, $ref, $verified = false)
    {
        return $this->orderModel->recordPayment($amt, $customerId, $orderId, $paymentMode, $ref, $verified);
    }

    public function getOrderIdByReference($ref)
    {
        return $this->orderModel->getOrderIdByReference($ref);
    }

    public function getOrderById($orderId)
    {
        return $this->orderModel->getOrderById($orderId);
    }

    public function getOrderDetails($orderId)
    {
        return $this->orderModel->getOrderDetails($orderId);
    }

    public function getOrdersByCustomer($customerId)
    {
        return $this->orderModel->getOrdersByCustomer($customerId);
    }

    public function getAllOrders()
    {
        return $this->orderModel->getAllOrders();
    }

    /** True when $by (customer, kitchen or admin) may still cancel this order */
    public function canCancel($order, $by)
    {
        return in_array($order['order_status'] ?? '', self::CANCELLABLE_FROM[$by] ?? [], true);
    }

    /**
     * Cancels an order and refunds the customer in full through Paystack.
     * The order is claimed first (so it can't be cancelled twice or moved on meanwhile), then the refund is started.
     * If Paystack won't start the refund, the order goes back to how it was and nothing changes.
     * Payments from before Paystack was connected were simulated, so they're cancelled with no refund to send.
     * Returns ['ok' => bool, 'message' => what to tell the person].
     */
    public function cancelOrder($order, $by, $reason)
    {
        $orderId = (int)$order['order_id'];
        $previous = $order['order_status'];
        $reason = mb_substr(trim($reason), 0, 255);

        if (!$this->canCancel($order, $by)) {
            return ['ok' => false, 'message' => "Order #{$order['invoice_no']} can't be cancelled now: it's {$previous}."];
        }
        if (!$this->orderModel->claimCancellation($orderId, self::CANCELLABLE_FROM[$by], $by, $reason ?: null)) {
            return ['ok' => false, 'message' => "Order #{$order['invoice_no']} changed while you were looking at it. Check it and try again."];
        }

        $amount = round((float)($order['amt'] ?? 0), 2);
        $paidThroughPaystack = !empty($order['verified_at']) && !empty($order['transaction_reference']) && $amount > 0;
        $refundStatus = 'not_needed';
        $paystackId = null;

        if ($paidThroughPaystack) {
            require_once __DIR__ . '/../core/paystack.php';
            $refund = paystack_configured()
                ? paystack_refund($order['transaction_reference'], to_pesewas($amount), "CampusBite order #{$order['invoice_no']} cancelled")
                : ['ok' => false, 'message' => 'Paystack is not set up on this site.'];
            if (!$refund['ok']) {
                $this->orderModel->undoCancellation($orderId, $previous);
                $why = $refund['message'] !== '' ? ' Paystack said: ' . $refund['message'] : '';
                return ['ok' => false, 'message' => "The refund couldn't be started, so order #{$order['invoice_no']} has not been cancelled.{$why}"];
            }
            $refundStatus = $refund['status'] ?: 'pending';
            $paystackId = $refund['id'];
        }

        $who = ['customer' => 'the customer', 'kitchen' => $order['brand_name'] ?? 'the kitchen', 'admin' => 'CampusBite'][$by];
        $note = 'Cancelled by ' . $who . ($reason !== '' ? ': ' . $reason : '') . '.';
        $note .= $paidThroughPaystack ? ' GH₵ ' . number_format($amount, 2) . ' is being refunded through Paystack.' : '';
        try {
            $this->orderModel->recordRefund($orderId, $order['pay_id'] ?? null, $paidThroughPaystack ? $amount : 0, $refundStatus, $paystackId);
            $this->orderModel->addTrackingUpdate($orderId, 'Cancelled', mb_substr($note, 0, 255));
        } catch (PDOException $e) {
            // The order is cancelled and Paystack has the refund; only our record of it failed
            error_log("Order $orderId cancelled but its refund record failed: " . $e->getMessage() . " (Paystack refund $paystackId)");
        }

        $message = "Order #{$order['invoice_no']} is cancelled.";
        if ($paidThroughPaystack) {
            $message .= ' GH₵ ' . number_format($amount, 2) . ' is on its way back to ' . ($by === 'customer' ? 'you' : 'the customer') . ' through Paystack. Mobile Money refunds usually arrive within a day; cards can take a few days.';
        }
        return ['ok' => true, 'message' => $message];
    }

    /** Asks Paystack for the latest status of a refund that's still in progress, and saves it. Returns the status. */
    public function refreshRefund($order)
    {
        $status = $order['refund_status'] ?? null;
        if (!in_array($status, ['pending', 'processing'], true) || empty($order['paystack_refund_id'])) {
            return $status;
        }
        // At most once a minute per order
        if (!empty($order['refund_updated_at']) && strtotime($order['refund_updated_at']) > time() - 60) {
            return $status;
        }
        require_once __DIR__ . '/../core/paystack.php';
        $latest = paystack_configured() ? paystack_refund_status($order['paystack_refund_id']) : null;
        if ($latest !== null && $latest !== '') {
            $this->orderModel->updateRefundStatus((int)$order['refund_id'], mb_substr($latest, 0, 20));
            return $latest;
        }
        return $status;
    }

    const ORDERS_PER_PAGE = 25;

    /**
     * A kitchen's order list (kitchen portal, All orders). Cleans the query string into filters, always limited to
     * $brandId, and returns one page of orders, the per-status counts for the chips, and paging details.
     */
    public function searchKitchenOrders($brandId, $input)
    {
        $readDate = function ($value) {
            $d = DateTime::createFromFormat('Y-m-d', (string)$value);
            return ($d && $d->format('Y-m-d') === $value) ? $value : '';
        };
        $f = [
            'q' => mb_substr(trim((string)($input['q'] ?? '')), 0, 100),
            'brand_id' => (int)$brandId,
            'from' => $readDate($input['from'] ?? ''),
            'to' => $readDate($input['to'] ?? ''),
            'status' => (array_key_exists($input['status'] ?? '', self::STATUSES) || ($input['status'] ?? '') === 'Cancelled') ? $input['status'] : '',
        ];
        if ($f['from'] !== '' && $f['to'] !== '' && $f['from'] > $f['to']) {
            [$f['from'], $f['to']] = [$f['to'], $f['from']];
        }

        $counts = $this->orderModel->countOrdersByStatusFor($f);
        $total = $f['status'] !== '' ? (int)($counts[$f['status']] ?? 0) : array_sum($counts);
        $pages = max(1, (int)ceil($total / self::ORDERS_PER_PAGE));
        $page = min(max(1, (int)($input['page'] ?? 1)), $pages);
        $orders = $this->orderModel->searchOrders($f, self::ORDERS_PER_PAGE, ($page - 1) * self::ORDERS_PER_PAGE);

        return ['filters' => $f, 'orders' => $orders, 'counts' => $counts, 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    const CUSTOMERS_PER_PAGE = 25;
    const NEW_CUSTOMER_DAYS = 30;

    /** A kitchen's customers: ?q= (name, email or phone) and ?page=, plus headline numbers. */
    public function getKitchenCustomers($brandId, $input)
    {
        $q = mb_substr(trim((string)($input['q'] ?? '')), 0, 100);
        $total = $this->orderModel->countKitchenCustomers($brandId, $q);
        $pages = max(1, (int)ceil($total / self::CUSTOMERS_PER_PAGE));
        $page = min(max(1, (int)($input['page'] ?? 1)), $pages);
        return [
            'q' => $q,
            'customers' => $this->orderModel->getKitchenCustomers($brandId, $q, self::CUSTOMERS_PER_PAGE, ($page - 1) * self::CUSTOMERS_PER_PAGE),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'stats' => $this->orderModel->getKitchenCustomerStats($brandId, date('Y-m-d 00:00:00', strtotime('-' . self::NEW_CUSTOMER_DAYS . ' days'))),
        ];
    }

    /** One customer as this kitchen knows them, with their orders from it; null if they've never ordered from it. */
    public function getKitchenCustomer($brandId, $customerId)
    {
        $rows = $this->orderModel->getKitchenCustomers($brandId, '', 1, 0, (int)$customerId);
        if (!$rows) {
            return null;
        }
        $orders = $this->orderModel->searchOrders(['brand_id' => (int)$brandId, 'customer_id' => (int)$customerId], self::ORDERS_PER_PAGE, 0);
        return ['customer' => $rows[0], 'orders' => $orders];
    }

    public function updateOrderStatus($orderId, $status)
    {
        if (!array_key_exists($status, self::STATUSES)) {
            return false;
        }
        return $this->orderModel->updateOrderStatus($orderId, $status);
    }

    /**
     * The admin money report for orders placed from $from up to and including $to (Y-m-d dates).
     * Per kitchen: delivered orders, meal sales, CampusBite's commission, delivery fees and what the kitchen
     * is owed (sales - commission + delivery fees), plus orders still in progress, which aren't owed yet.
     */
    public function getMoneyReport($from, $to, $kitchens, $withPaymentCheck = true)
    {
        $start = $from . ' 00:00:00';
        $end = date('Y-m-d 00:00:00', strtotime($to . ' +1 day'));

        $blank = ['orders' => 0, 'sales' => 0.0, 'commission' => 0.0, 'delivery' => 0.0, 'owed' => 0.0,
                  'pending_orders' => 0, 'pending_value' => 0.0];
        $rows = [];
        foreach ($kitchens as $k) {
            $rows[(int)$k['brand_id']] = ['brand_id' => (int)$k['brand_id'], 'brand_name' => $k['brand_name'],
                                          'commission_rate' => (float)$k['commission_rate']] + $blank;
        }
        $row = function ($id) use (&$rows, $blank) {
            if (!isset($rows[$id])) {
                $rows[$id] = ['brand_id' => $id, 'brand_name' => 'Unknown kitchen', 'commission_rate' => 0.0] + $blank;
            }
            return $id;
        };

        foreach ($this->orderModel->getMealMoneyByKitchen($start, $end) as $m) {
            $id = $row((int)$m['brand_id']);
            if ((int)$m['delivered']) {
                $rows[$id]['sales'] += (float)$m['sales'];
                $rows[$id]['commission'] += (float)$m['commission'];
            } else {
                $rows[$id]['pending_value'] += (float)$m['sales'];
            }
        }
        foreach ($this->orderModel->getOrderMoneyByKitchen($start, $end) as $o) {
            $id = $row((int)$o['brand_id']);
            if ((int)$o['delivered']) {
                $rows[$id]['orders'] += (int)$o['orders'];
                $rows[$id]['delivery'] += (float)$o['delivery'];
            } else {
                $rows[$id]['pending_orders'] += (int)$o['orders'];
                $rows[$id]['pending_value'] += (float)$o['delivery'];
            }
        }

        $totals = ['brand_name' => 'All kitchens'] + $blank;
        foreach ($rows as $id => $r) {
            $rows[$id]['commission'] = round($r['commission'], 2);
            $rows[$id]['owed'] = round($r['sales'] - $rows[$id]['commission'] + $r['delivery'], 2);
            foreach (array_keys($blank) as $field) {
                $totals[$field] += $rows[$id][$field];
            }
        }

        // Kitchens with activity first, biggest payout on top
        $rows = array_values($rows);
        usort($rows, function ($a, $b) {
            return [$b['owed'], $b['pending_value'], $a['brand_name']] <=> [$a['owed'], $a['pending_value'], $b['brand_name']];
        });

        // Payment check: each order's expected total against what was recorded as paid (skipped by pages that don't show it)
        $check = ['orders' => 0, 'expected' => 0.0, 'paid' => 0.0, 'verified' => 0.0, 'refunded' => 0.0, 'cancelled' => 0, 'problems' => []];
        foreach ($withPaymentCheck ? $this->orderModel->getPaymentCheck($start, $end) : [] as $p) {
            $expected = round((float)$p['expected'], 2);
            $paid = $p['paid'] === null ? null : round((float)$p['paid'], 2);
            $check['orders']++;
            $check['expected'] += $expected;
            $check['paid'] += (float)$paid;
            if ((int)$p['verified'] > 0) {
                $check['verified'] += (float)$paid;
            }
            $refunded = $p['refund_status'] !== null && !in_array($p['refund_status'], ['failed', 'needs-attention', 'not_needed'], true) ? round((float)$p['refunded'], 2) : 0.0;
            $check['refunded'] += $refunded;
            if ($p['order_status'] === 'Cancelled') {
                // A cancelled order should end with everything paid sent back
                $check['cancelled']++;
                $check['expected'] -= $expected;
                if ((int)$p['verified'] === 0) {
                    // A simulated payment on a cancelled order: no real money came in, so none needs to go back
                    $check['paid'] -= (float)$paid;
                }
                if (in_array($p['refund_status'], ['failed', 'needs-attention'], true)) {
                    $p['problem'] = 'Cancelled, but the Paystack refund failed';
                } elseif ((int)$p['verified'] > 0 && $p['refund_status'] === null) {
                    $p['problem'] = 'Cancelled, but no refund was recorded';
                } elseif ((int)$p['verified'] > 0 && abs($refunded - (float)$paid) >= 0.01) {
                    $p['problem'] = 'Cancelled, but the refund doesn\'t match the payment';
                } else {
                    continue;
                }
            } elseif ($paid === null) {
                $p['problem'] = 'No payment recorded';
            } elseif ((int)$p['payments'] > 1) {
                $p['problem'] = 'Paid more than once';
            } elseif (abs($paid - $expected) >= 0.01) {
                $p['problem'] = $paid < $expected ? 'Paid less than the order total' : 'Paid more than the order total';
            } elseif ((int)$p['verified'] === 0) {
                // Recorded before Paystack was connected (or by hand): no money was confirmed for it
                $p['problem'] = 'Not confirmed by Paystack';
            } else {
                continue;
            }
            $p['expected'] = $expected;
            $p['paid'] = $paid;
            $check['problems'][] = $p;
        }

        return ['kitchens' => $rows, 'totals' => $totals, 'payments' => $check];
    }

    public function getTrackingHistory($orderId)
    {
        return $this->orderModel->getTrackingHistory($orderId);
    }

    public function getRecentOrdersWithSummary($customerId, $limit = 3)
    {
        return $this->orderModel->getRecentOrdersWithSummary($customerId, $limit);
    }
}
?>
