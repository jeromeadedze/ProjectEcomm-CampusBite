<?php
require_once __DIR__ . "/../classes/OrderClass.php";

class OrderController
{
    // The only statuses an order can have, in pipeline order, with their timeline labels
    const STATUSES = [
        'Placed' => 'Order Received by Kitchen',
        'Preparing' => 'Kitchen Preparing Meal',
        'Ready' => 'Ready for Courier Pickup',
        'In Transit' => 'Student Courier on the Way',
        'Delivered' => 'Delivered to Drop Point',
    ];

    private $orderModel;

    public function __construct()
    {
        $this->orderModel = new OrderClass();
    }

    public function createOrder($customerId, $invoiceNo, $deliveryZone, $deliveryFee, $dropLocation, $notes = '')
    {
        return $this->orderModel->createOrder($customerId, $invoiceNo, $deliveryZone, $deliveryFee, $dropLocation, $notes);
    }

    public function addOrderDetail($orderId, $productId, $qty, $notes = null)
    {
        return $this->orderModel->addOrderDetail($orderId, $productId, $qty, $notes);
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

    public function recordPayment($amt, $customerId, $orderId, $paymentMode, $ref)
    {
        return $this->orderModel->recordPayment($amt, $customerId, $orderId, $paymentMode, $ref);
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
     * Assigns a courier (a role-3 account) to an order, or clears it when $courierId is null,
     * and notes the change on the order's timeline.
     */
    public function assignCourier($orderId, $courierId, $courierName = null)
    {
        if (!$this->orderModel->assignCourier($orderId, $courierId)) {
            return false;
        }
        $order = $this->orderModel->getOrderById($orderId);
        $message = $courierId ? "Courier {$courierName} assigned to your delivery." : "Courier unassigned.";
        $this->orderModel->addTrackingUpdate($orderId, $order['order_status'], $message);
        return true;
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
