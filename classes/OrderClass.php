<?php
require_once __DIR__ . "/../core/db_class.php";

class OrderClass extends Database
{
    public function createOrder($customerId, $invoiceNo, $deliveryZone, $deliveryFee, $dropLocation, $notes = '')
    {
        $sql = "INSERT INTO orders (customer_id, invoice_no, order_status, delivery_zone, delivery_fee, drop_location, special_instructions)
                VALUES (?, ?, 'Placed', ?, ?, ?, ?)";
        $success = $this->execute($sql, [$customerId, $invoiceNo, $deliveryZone, $deliveryFee, $dropLocation, $notes]);
        if ($success) {
            $orderId = $this->getLastInsertId();
            // Create initial tracking entry
            $this->addTrackingUpdate($orderId, 'Placed', 'Order placed successfully and received by kitchen.');
            return $orderId;
        }
        return false;
    }

    public function addOrderDetail($orderId, $productId, $qty)
    {
        $sql = "INSERT INTO orderdetails (order_id, product_id, qty) VALUES (?, ?, ?)";
        return $this->execute($sql, [$orderId, $productId, $qty]);
    }

    public function recordPayment($amt, $customerId, $orderId, $paymentMode, $ref)
    {
        $sql = "INSERT INTO payment (amt, customer_id, order_id, currency, payment_mode, transaction_reference)
                VALUES (?, ?, ?, 'GHS', ?, ?)";
        return $this->execute($sql, [$amt, $customerId, $orderId, $paymentMode, $ref]);
    }

    public function getOrderById($orderId)
    {
        $sql = "SELECT o.*, c.customer_name, c.customer_email, c.customer_contact 
                FROM orders o
                JOIN customer c ON o.customer_id = c.customer_id
                WHERE o.order_id = ?";
        return $this->fetchOne($sql, [$orderId]);
    }

    public function getOrderDetails($orderId)
    {
        $sql = "SELECT od.*, p.product_title, p.product_price, p.product_image, b.brand_name
                FROM orderdetails od
                JOIN products p ON od.product_id = p.product_id
                JOIN brands b ON p.product_brand = b.brand_id
                WHERE od.order_id = ?";
        return $this->fetchAll($sql, [$orderId]);
    }

    public function getOrdersByCustomer($customerId)
    {
        $sql = "SELECT * FROM orders WHERE customer_id = ? ORDER BY order_id DESC";
        return $this->fetchAll($sql, [$customerId]);
    }

    public function getAllOrders()
    {
        $sql = "SELECT o.*, c.customer_name, c.customer_contact, p.payment_mode, p.amt 
                FROM orders o
                JOIN customer c ON o.customer_id = c.customer_id
                LEFT JOIN payment p ON o.order_id = p.order_id
                ORDER BY o.order_id DESC";
        return $this->fetchAll($sql);
    }

    public function updateOrderStatus($orderId, $status)
    {
        $sql = "UPDATE orders SET order_status = ? WHERE order_id = ?";
        $res = $this->execute($sql, [$status, $orderId]);
        if ($res) {
            $msg = "Order status updated to: " . $status;
            $this->addTrackingUpdate($orderId, $status, $msg);
        }
        return $res;
    }

    public function addTrackingUpdate($orderId, $status, $message)
    {
        $sql = "INSERT INTO order_tracking (order_id, status, status_message) VALUES (?, ?, ?)";
        return $this->execute($sql, [$orderId, $status, $message]);
    }

    public function getTrackingHistory($orderId)
    {
        $sql = "SELECT * FROM order_tracking WHERE order_id = ? ORDER BY tracking_id ASC";
        return $this->fetchAll($sql, [$orderId]);
    }
}
?>
