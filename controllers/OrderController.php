<?php
require_once __DIR__ . "/../classes/OrderClass.php";

class OrderController
{
    private $orderModel;

    public function __construct()
    {
        $this->orderModel = new OrderClass();
    }

    public function createOrder($customerId, $invoiceNo, $deliveryZone, $deliveryFee, $dropLocation, $notes = '')
    {
        return $this->orderModel->createOrder($customerId, $invoiceNo, $deliveryZone, $deliveryFee, $dropLocation, $notes);
    }

    public function addOrderDetail($orderId, $productId, $qty)
    {
        return $this->orderModel->addOrderDetail($orderId, $productId, $qty);
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
        return $this->orderModel->updateOrderStatus($orderId, $status);
    }

    public function getTrackingHistory($orderId)
    {
        return $this->orderModel->getTrackingHistory($orderId);
    }
}
?>
