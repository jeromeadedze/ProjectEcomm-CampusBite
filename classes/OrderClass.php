<?php
require_once __DIR__ . "/../core/db_class.php";

class OrderClass extends Database
{
    public function createOrder($customerId, $brandId, $invoiceNo, $deliveryZone, $deliveryFee, $dropLocation, $notes = '')
    {
        $sql = "INSERT INTO orders (customer_id, brand_id, invoice_no, order_status, delivery_zone, delivery_fee, drop_location, special_instructions)
                VALUES (?, ?, ?, 'Placed', ?, ?, ?, ?)";
        $success = $this->execute($sql, [$customerId, $brandId, $invoiceNo, $deliveryZone, $deliveryFee, $dropLocation, $notes]);
        if ($success) {
            $orderId = $this->getLastInsertId();
            // Create initial tracking entry
            $this->addTrackingUpdate($orderId, 'Placed', 'Order placed successfully and received by kitchen.');
            return $orderId;
        }
        return false;
    }

    // $price is the meal's price at checkout, kept so the order's value never changes afterwards
    public function addOrderDetail($orderId, $productId, $qty, $price, $notes = null)
    {
        $sql = "INSERT INTO orderdetails (order_id, product_id, qty, price, notes) VALUES (?, ?, ?, ?, ?)";
        return $this->execute($sql, [$orderId, $productId, $qty, $price, $notes]);
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
                       k.customer_name AS courier_name, k.customer_contact AS courier_contact,
                       b.brand_name, b.commission_rate
                FROM orders o
                JOIN customer c ON o.customer_id = c.customer_id
                LEFT JOIN customer k ON o.courier_id = k.customer_id
                LEFT JOIN brands b ON o.brand_id = b.brand_id
                WHERE o.order_id = ?";
        return $this->fetchOne($sql, [$orderId]);
    }

    public function getOrderDetails($orderId)
    {
        $sql = "SELECT od.*, COALESCE(od.price, p.product_price) AS unit_price, p.product_title, p.product_price, p.product_image, p.product_brand, b.brand_name
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
                                (SELECT COALESCE(SUM(od.qty * COALESCE(od.price, pr.product_price)), 0)
                                 FROM orderdetails od JOIN products pr ON od.product_id = pr.product_id
                                 WHERE od.order_id = o.order_id) + o.delivery_fee) AS order_total,
                       b.brand_name
                FROM orders o
                LEFT JOIN brands b ON o.brand_id = b.brand_id
                WHERE o.customer_id = ?
                ORDER BY o.order_id DESC";
        return $this->fetchAll($sql, [$customerId]);
    }

    public function getAllOrders()
    {
        $sql = "SELECT o.*, c.customer_name, c.customer_contact, p.payment_mode, p.amt, k.customer_name AS courier_name, b.brand_name,
                       (SELECT COALESCE(SUM(od.qty * COALESCE(od.price, pr.product_price)), 0)
                        FROM orderdetails od JOIN products pr ON od.product_id = pr.product_id
                        WHERE od.order_id = o.order_id) AS items_total
                FROM orders o
                JOIN customer c ON o.customer_id = c.customer_id
                LEFT JOIN customer k ON o.courier_id = k.customer_id
                LEFT JOIN brands b ON o.brand_id = b.brand_id
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

    // ---------- Admin dashboard (whole platform) ----------

    /**
     * Orders, meal sales and CampusBite's commission since a date (all time when $since is null).
     * Commission uses the rate of the kitchen each meal came from.
     */
    public function getPlatformSales($since = null)
    {
        $sql = "SELECT COUNT(DISTINCT o.order_id) AS orders,
                       COALESCE(SUM(od.qty * COALESCE(od.price, p.product_price)), 0) AS sales,
                       COALESCE(SUM(od.qty * COALESCE(od.price, p.product_price) * b.commission_rate), 0) AS commission
                FROM orders o
                JOIN orderdetails od ON od.order_id = o.order_id
                JOIN products p ON od.product_id = p.product_id
                JOIN brands b ON p.product_brand = b.brand_id" . ($since ? " WHERE o.order_date >= ?" : "");
        return $this->fetchOne($sql, $since ? [$since] : []);
    }

    public function getDeliveryFees($since = null)
    {
        $row = $this->fetchOne("SELECT COALESCE(SUM(delivery_fee), 0) AS fees FROM orders" . ($since ? " WHERE order_date >= ?" : ""), $since ? [$since] : []);
        return (float)$row['fees'];
    }

    public function countOrdersByStatus()
    {
        return array_column($this->fetchAll("SELECT order_status, COUNT(*) AS total FROM orders GROUP BY order_status"), 'total', 'order_status');
    }

    /**
     * Every kitchen with its sales and orders since a date, whether it has a kitchen login, and its menu size
     */
    public function getKitchenOverview($since)
    {
        $sql = "SELECT b.brand_id, b.brand_name, b.zone, b.is_active, b.commission_rate,
                       (SELECT COUNT(*) FROM customer c WHERE c.user_role = 4 AND c.brand_id = b.brand_id) AS logins,
                       (SELECT COUNT(*) FROM products p WHERE p.product_brand = b.brand_id) AS meals,
                       (SELECT COUNT(*) FROM products p WHERE p.product_brand = b.brand_id AND p.is_available = 0) AS sold_out,
                       (SELECT COUNT(*) FROM orders o WHERE o.brand_id = b.brand_id AND o.order_date >= ?) AS orders,
                       (SELECT COUNT(*) FROM orders o WHERE o.brand_id = b.brand_id AND o.order_status <> 'Delivered') AS active,
                       (SELECT COALESCE(SUM(od.qty * COALESCE(od.price, p.product_price)), 0)
                        FROM orderdetails od JOIN orders o ON od.order_id = o.order_id JOIN products p ON od.product_id = p.product_id
                        WHERE p.product_brand = b.brand_id AND o.order_date >= ?) AS sales
                FROM brands b
                ORDER BY sales DESC, b.brand_name ASC";
        return $this->fetchAll($sql, [$since, $since]);
    }

    // Orders still marked Placed after $minutes: no kitchen has started them
    public function getWaitingOrders($minutes)
    {
        $sql = "SELECT o.order_id, o.invoice_no, o.order_date, b.brand_name
                FROM orders o LEFT JOIN brands b ON o.brand_id = b.brand_id
                WHERE o.order_status = 'Placed' AND o.order_date < (NOW() - INTERVAL " . intval($minutes) . " MINUTE)
                ORDER BY o.order_date ASC";
        return $this->fetchAll($sql);
    }

    // ---------- Kitchen portal ----------

    /**
     * A kitchen's orders, newest first. $scope: 'active' (not delivered yet), 'done' (delivered) or 'all'.
     */
    public function getKitchenOrders($brandId, $scope = 'active')
    {
        $where = "o.brand_id = ?";
        if ($scope === 'active') {
            $where .= " AND o.order_status <> 'Delivered'";
        } elseif ($scope === 'done') {
            $where .= " AND o.order_status = 'Delivered'";
        }
        // Totals count only this kitchen's meals: orders from before the one-kitchen rule can mix kitchens
        $sql = "SELECT o.*, c.customer_name, c.customer_contact, k.customer_name AS courier_name,
                       (SELECT COALESCE(SUM(od.qty * COALESCE(od.price, pr.product_price)), 0)
                        FROM orderdetails od JOIN products pr ON od.product_id = pr.product_id
                        WHERE od.order_id = o.order_id AND pr.product_brand = o.brand_id) AS items_total,
                       (SELECT COALESCE(SUM(od.qty), 0) FROM orderdetails od JOIN products pr ON od.product_id = pr.product_id
                        WHERE od.order_id = o.order_id AND pr.product_brand = o.brand_id) AS item_count
                FROM orders o
                JOIN customer c ON o.customer_id = c.customer_id
                LEFT JOIN customer k ON o.courier_id = k.customer_id
                WHERE $where
                ORDER BY o.order_id DESC";
        return $this->fetchAll($sql, [$brandId]);
    }

    /**
     * Order count and meal sales for a kitchen since a date (or all time when $since is null)
     */
    public function getKitchenSales($brandId, $since = null)
    {
        $sql = "SELECT COUNT(DISTINCT o.order_id) AS orders,
                       COALESCE(SUM(od.qty * COALESCE(od.price, p.product_price)), 0) AS sales,
                       COALESCE(SUM(od.qty), 0) AS meals
                FROM orders o
                JOIN orderdetails od ON od.order_id = o.order_id
                JOIN products p ON od.product_id = p.product_id AND p.product_brand = o.brand_id
                WHERE o.brand_id = ?" . ($since ? " AND o.order_date >= ?" : "");
        return $this->fetchOne($sql, $since ? [$brandId, $since] : [$brandId]);
    }

    public function countKitchenOrdersByStatus($brandId)
    {
        $rows = $this->fetchAll("SELECT order_status, COUNT(*) AS total FROM orders WHERE brand_id = ? GROUP BY order_status", [$brandId]);
        return array_column($rows, 'total', 'order_status');
    }

    public function getKitchenBestSellers($brandId, $limit = 5)
    {
        $sql = "SELECT p.product_id, p.product_title, SUM(od.qty) AS meals,
                       SUM(od.qty * COALESCE(od.price, p.product_price)) AS sales
                FROM orderdetails od
                JOIN orders o ON od.order_id = o.order_id
                JOIN products p ON od.product_id = p.product_id AND p.product_brand = o.brand_id
                WHERE o.brand_id = ?
                GROUP BY p.product_id, p.product_title
                ORDER BY meals DESC, sales DESC
                LIMIT " . intval($limit);
        return $this->fetchAll($sql, [$brandId]);
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
                       (SELECT COALESCE(SUM(od.qty * COALESCE(od.price, p.product_price)), 0)
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
