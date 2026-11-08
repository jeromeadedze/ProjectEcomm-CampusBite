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

    public function addOrderDetail($orderId, $productId, $qty, $notes = null)
    {
        $sql = "INSERT INTO orderdetails (order_id, product_id, qty, notes) VALUES (?, ?, ?, ?)";
        return $this->execute($sql, [$orderId, $productId, $qty, $notes]);
    }

    public function recordPayment($amt, $customerId, $orderId, $paymentMode, $ref)
    {
        $sql = "INSERT INTO payment (amt, customer_id, order_id, currency, payment_mode, transaction_reference)
                VALUES (?, ?, ?, 'GHS', ?, ?)";
        return $this->execute($sql, [$amt, $customerId, $orderId, $paymentMode, $ref]);
    }

    public function getOrderById($orderId)
    {
        $sql = "SELECT o.*, c.customer_name, c.customer_email, c.customer_contact,
                       k.customer_name AS courier_name, k.customer_contact AS courier_contact
                FROM orders o
                JOIN customer c ON o.customer_id = c.customer_id
                LEFT JOIN customer k ON o.courier_id = k.customer_id
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

    // Each order with its meal count and total paid (payment amount, or meals + delivery if there is no payment row)
    public function getOrdersByCustomer($customerId)
    {
        $sql = "SELECT o.*,
                       (SELECT COALESCE(SUM(od.qty), 0) FROM orderdetails od WHERE od.order_id = o.order_id) AS item_count,
                       COALESCE((SELECT SUM(p.amt) FROM payment p WHERE p.order_id = o.order_id),
                                (SELECT COALESCE(SUM(od.qty * pr.product_price), 0)
                                 FROM orderdetails od JOIN products pr ON od.product_id = pr.product_id
                                 WHERE od.order_id = o.order_id) + o.delivery_fee) AS order_total
                FROM orders o
                WHERE o.customer_id = ?
                ORDER BY o.order_id DESC";
        return $this->fetchAll($sql, [$customerId]);
    }

    public function getAllOrders()
    {
        $sql = "SELECT o.*, c.customer_name, c.customer_contact, p.payment_mode, p.amt, k.customer_name AS courier_name,
                       (SELECT COALESCE(SUM(od.qty * pr.product_price), 0)
                        FROM orderdetails od JOIN products pr ON od.product_id = pr.product_id
                        WHERE od.order_id = o.order_id) AS items_total
                FROM orders o
                JOIN customer c ON o.customer_id = c.customer_id
                LEFT JOIN customer k ON o.courier_id = k.customer_id
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

    public function assignCourier($orderId, $courierId)
    {
        return $this->execute("UPDATE orders SET courier_id = ? WHERE order_id = ?", [$courierId, $orderId]);
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

    // Latest orders for the home page, each with its first meal and a total
    public function getRecentOrdersWithSummary($customerId, $limit = 3)
    {
        $sql = "SELECT o.order_id, o.invoice_no, o.order_date, o.order_status, o.delivery_fee,
                       (SELECT COALESCE(SUM(od.qty * p.product_price), 0)
                        FROM orderdetails od JOIN products p ON od.product_id = p.product_id
                        WHERE od.order_id = o.order_id) AS items_total,
                       (SELECT COUNT(*) FROM orderdetails od WHERE od.order_id = o.order_id) AS item_count,
                       (SELECT p.product_title FROM orderdetails od JOIN products p ON od.product_id = p.product_id
                        WHERE od.order_id = o.order_id ORDER BY p.product_id LIMIT 1) AS first_title,
                       (SELECT p.product_image FROM orderdetails od JOIN products p ON od.product_id = p.product_id
                        WHERE od.order_id = o.order_id ORDER BY p.product_id LIMIT 1) AS first_image,
                       (SELECT b.brand_name FROM orderdetails od JOIN products p ON od.product_id = p.product_id
                        JOIN brands b ON p.product_brand = b.brand_id
                        WHERE od.order_id = o.order_id ORDER BY p.product_id LIMIT 1) AS first_kitchen
                FROM orders o
                WHERE o.customer_id = ?
                ORDER BY o.order_id DESC
                LIMIT " . intval($limit);
        return $this->fetchAll($sql, [$customerId]);
    }
}
?>
