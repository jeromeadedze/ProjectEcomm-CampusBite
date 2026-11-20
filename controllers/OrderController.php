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
    public function getMoneyReport($from, $to, $kitchens)
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

        // Payment check: each order's expected total against what was recorded as paid
        $check = ['orders' => 0, 'expected' => 0.0, 'paid' => 0.0, 'verified' => 0.0, 'problems' => []];
        foreach ($this->orderModel->getPaymentCheck($start, $end) as $p) {
            $expected = round((float)$p['expected'], 2);
            $paid = $p['paid'] === null ? null : round((float)$p['paid'], 2);
            $check['orders']++;
            $check['expected'] += $expected;
            $check['paid'] += (float)$paid;
            if ((int)$p['verified'] > 0) {
                $check['verified'] += (float)$paid;
            }
            if ($paid === null) {
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
