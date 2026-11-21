<?php
require_once __DIR__ . "/../core/db_class.php";

class OrderClass extends Database
{
    /**
     * Commission rate for one order line (needs aliases o = orders, p = products, b = the meal's kitchen in brands).
     * Uses the rate saved on the order when the meal is from the order's kitchen, otherwise the kitchen's current rate.
     */
    const LINE_RATE = "CASE WHEN p.product_brand = o.brand_id AND o.commission_rate IS NOT NULL THEN o.commission_rate ELSE b.commission_rate END";

    public function createOrder($customerId, $brandId, $invoiceNo, $deliveryZone, $deliveryFee, $dropLocation, $notes = '')
    {
        // The kitchen's commission rate is copied onto the order, so changing a rate later doesn't change past payouts
        $sql = "INSERT INTO orders (customer_id, brand_id, invoice_no, order_status, delivery_zone, delivery_fee, drop_location, special_instructions, commission_rate)
                VALUES (?, ?, ?, 'Placed', ?, ?, ?, ?, (SELECT commission_rate FROM brands WHERE brand_id = ?))";
        $success = $this->execute($sql, [$customerId, $brandId, $invoiceNo, $deliveryZone, $deliveryFee, $dropLocation, $notes, $brandId]);
        if ($success) {
            $orderId = $this->getLastInsertId();
            // Create initial tracking entry
            $this->addTrackingUpdate($orderId, 'Placed', 'Order placed and sent to the kitchen.');
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

    public function recordPayment($amt, $customerId, $orderId, $paymentMode, $ref, $verified = false)
    {
        $sql = "INSERT INTO payment (amt, customer_id, order_id, currency, payment_mode, transaction_reference, verified_at)
                VALUES (?, ?, ?, 'GHS', ?, ?, " . ($verified ? "NOW()" : "NULL") . ")";
        return $this->execute($sql, [$amt, $customerId, $orderId, $paymentMode, $ref]);
    }

    /**
     * The order a payment reference already paid for, or null. Stops one Paystack payment creating two orders.
     */
    public function getOrderIdByReference($ref)
    {
        $row = $this->fetchOne("SELECT order_id FROM payment WHERE transaction_reference = ?", [$ref]);
        return $row ? (int)$row['order_id'] : null;
    }

    public function getOrderById($orderId)
    {
        $sql = "SELECT o.*, c.customer_name, c.customer_email, c.customer_contact,
                       b.brand_name, b.brand_phone, COALESCE(o.commission_rate, b.commission_rate) AS commission_rate,
                       COALESCE(b.brand_phone, (SELECT ks.customer_contact FROM customer ks
                        WHERE ks.user_role = 4 AND ks.brand_id = o.brand_id ORDER BY ks.customer_id LIMIT 1)) AS kitchen_contact,
                       pay.pay_id, pay.amt, pay.payment_mode, pay.transaction_reference, pay.verified_at,
                       r.refund_id, r.amount AS refund_amount, r.status AS refund_status, r.paystack_refund_id, r.updated_at AS refund_updated_at
                FROM orders o
                JOIN customer c ON o.customer_id = c.customer_id
                LEFT JOIN brands b ON o.brand_id = b.brand_id
                LEFT JOIN payment pay ON pay.pay_id = (SELECT MIN(p2.pay_id) FROM payment p2 WHERE p2.order_id = o.order_id)
                LEFT JOIN refunds r ON r.order_id = o.order_id
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
                       b.brand_name, b.brand_phone,
                       COALESCE(b.brand_phone, (SELECT ks.customer_contact FROM customer ks
                        WHERE ks.user_role = 4 AND ks.brand_id = o.brand_id ORDER BY ks.customer_id LIMIT 1)) AS kitchen_contact
                FROM orders o
                LEFT JOIN brands b ON o.brand_id = b.brand_id
                WHERE o.customer_id = ?
                ORDER BY o.order_id DESC";
        return $this->fetchAll($sql, [$customerId]);
    }

    public function getAllOrders()
    {
        $sql = "SELECT o.*, c.customer_name, c.customer_contact, p.payment_mode, p.amt, b.brand_name,
                       (SELECT COALESCE(SUM(od.qty * COALESCE(od.price, pr.product_price)), 0)
                        FROM orderdetails od JOIN products pr ON od.product_id = pr.product_id
                        WHERE od.order_id = o.order_id) AS items_total
                FROM orders o
                JOIN customer c ON o.customer_id = c.customer_id
                LEFT JOIN brands b ON o.brand_id = b.brand_id
                LEFT JOIN payment p ON o.order_id = p.order_id
                ORDER BY o.order_id DESC";
        return $this->fetchAll($sql);
    }

    /**
     * WHERE clause and parameters for the admin order search. $f holds any of:
     * q (invoice number, customer name, email or phone), brand_id, from and to (Y-m-d, order date), status.
     * Status is applied only when $withStatus is true, so the status chips can count across the other filters.
     */
    private function orderSearchWhere($f, $withStatus = true)
    {
        $where = ['1 = 1'];
        $params = [];
        if (($f['q'] ?? '') !== '') {
            $term = '%' . $f['q'] . '%';
            $where[] = "(o.invoice_no LIKE ? OR c.customer_name LIKE ? OR c.customer_email LIKE ? OR c.customer_contact LIKE ?)";
            array_push($params, $term, $term, $term, $term);
        }
        if (!empty($f['brand_id'])) {
            $where[] = "o.brand_id = ?";
            $params[] = (int)$f['brand_id'];
        }
        if (!empty($f['customer_id'])) {
            $where[] = "o.customer_id = ?";
            $params[] = (int)$f['customer_id'];
        }
        if (!empty($f['from'])) {
            $where[] = "o.order_date >= ?";
            $params[] = $f['from'] . ' 00:00:00';
        }
        if (!empty($f['to'])) {
            $where[] = "o.order_date < ?";
            $params[] = date('Y-m-d 00:00:00', strtotime($f['to'] . ' +1 day'));
        }
        if ($withStatus && !empty($f['status'])) {
            $where[] = "o.order_status = ?";
            $params[] = $f['status'];
        }
        return [implode(' AND ', $where), $params];
    }

    // One customer's ordering history in numbers, for the admin's customer page
    public function getCustomerOrderStats($customerId)
    {
        $stats = $this->fetchOne("SELECT COUNT(*) AS orders,
                                         COALESCE(SUM(order_status = 'Delivered'), 0) AS delivered,
                                         COALESCE(SUM(order_status = 'Cancelled'), 0) AS cancelled,
                                         COALESCE(SUM(order_status NOT IN ('Delivered', 'Cancelled')), 0) AS in_progress,
                                         MIN(order_date) AS first_order, MAX(order_date) AS last_order
                                  FROM orders WHERE customer_id = ?", [$customerId]);
        $stats['spent'] = (float)$this->fetchOne("SELECT COALESCE(SUM(p.amt), 0) AS v FROM payment p JOIN orders o ON o.order_id = p.order_id
                                                  WHERE o.customer_id = ? AND o.order_status <> 'Cancelled'", [$customerId])['v'];
        $stats['refunded'] = (float)$this->fetchOne("SELECT COALESCE(SUM(r.amount), 0) AS v FROM refunds r JOIN orders o ON o.order_id = r.order_id
                                                     WHERE o.customer_id = ? AND r.status NOT IN ('failed', 'needs-attention', 'not_needed')", [$customerId])['v'];
        $top = $this->fetchOne("SELECT b.brand_id, b.brand_name, COUNT(*) AS n FROM orders o JOIN brands b ON b.brand_id = o.brand_id
                                WHERE o.customer_id = ? AND o.order_status <> 'Cancelled'
                                GROUP BY b.brand_id, b.brand_name ORDER BY n DESC, b.brand_name LIMIT 1", [$customerId]);
        $stats['top_kitchen'] = $top ?: null;
        return $stats;
    }

    // One page of matching orders, newest first, with the first payment recorded for each
    public function searchOrders($f, $limit, $offset)
    {
        [$where, $params] = $this->orderSearchWhere($f);
        $sql = "SELECT o.*, c.customer_name, c.customer_contact, b.brand_name, pay.payment_mode, pay.amt,
                       (SELECT COALESCE(SUM(od.qty * COALESCE(od.price, pr.product_price)), 0)
                        FROM orderdetails od JOIN products pr ON od.product_id = pr.product_id
                        WHERE od.order_id = o.order_id) AS items_total
                FROM orders o
                JOIN customer c ON o.customer_id = c.customer_id
                LEFT JOIN brands b ON o.brand_id = b.brand_id
                LEFT JOIN payment pay ON pay.pay_id = (SELECT MIN(p2.pay_id) FROM payment p2 WHERE p2.order_id = o.order_id)
                WHERE $where
                ORDER BY o.order_id DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        return $this->fetchAll($sql, $params);
    }

    // How many matching orders each status has (ignoring the status filter itself)
    public function countOrdersByStatusFor($f)
    {
        [$where, $params] = $this->orderSearchWhere($f, false);
        $sql = "SELECT o.order_status, COUNT(*) AS total
                FROM orders o JOIN customer c ON o.customer_id = c.customer_id
                WHERE $where
                GROUP BY o.order_status";
        return array_column($this->fetchAll($sql, $params), 'total', 'order_status');
    }

    public function updateOrderStatus($orderId, $status)
    {
        // A cancelled order stays cancelled
        $sql = "UPDATE orders SET order_status = ? WHERE order_id = ? AND order_status <> 'Cancelled'";
        $res = $this->executeCount($sql, [$status, $orderId]) > 0;
        if ($res) {
            $msg = "Order status updated to: " . $status;
            $this->addTrackingUpdate($orderId, $status, $msg);
        }
        return $res;
    }

    // ---------- Cancelling and refunds ----------

    /**
     * Marks an order Cancelled only if it is still in one of $fromStatuses, so two people (or a kitchen moving it on)
     * can't race each other. Returns true when this call cancelled it.
     */
    public function claimCancellation($orderId, $fromStatuses, $by, $reason)
    {
        $marks = implode(', ', array_fill(0, count($fromStatuses), '?'));
        $sql = "UPDATE orders SET order_status = 'Cancelled', cancelled_at = NOW(), cancelled_by = ?, cancel_reason = ?
                WHERE order_id = ? AND order_status IN ($marks)";
        return $this->executeCount($sql, array_merge([$by, $reason, $orderId], $fromStatuses)) === 1;
    }

    // Puts a cancelled order back when its refund couldn't be started
    public function undoCancellation($orderId, $previousStatus)
    {
        $sql = "UPDATE orders SET order_status = ?, cancelled_at = NULL, cancelled_by = NULL, cancel_reason = NULL
                WHERE order_id = ? AND order_status = 'Cancelled'";
        return $this->execute($sql, [$previousStatus, $orderId]);
    }

    public function recordRefund($orderId, $payId, $amount, $status, $paystackId = null)
    {
        $sql = "INSERT INTO refunds (order_id, pay_id, amount, status, paystack_refund_id) VALUES (?, ?, ?, ?, ?)";
        return $this->execute($sql, [$orderId, $payId, $amount, $status, $paystackId]);
    }

    public function updateRefundStatus($refundId, $status)
    {
        return $this->execute("UPDATE refunds SET status = ?, updated_at = NOW() WHERE refund_id = ?", [$status, $refundId]);
    }

    // ---------- Admin dashboard (whole platform) ----------

    /**
     * Orders, meal sales and CampusBite's commission since a date (all time when $since is null).
     * Commission uses the rate saved on the order, or the kitchen's current rate for orders from before rates were saved.
     */
    public function getPlatformSales($since = null)
    {
        $sql = "SELECT COUNT(DISTINCT o.order_id) AS orders,
                       COALESCE(SUM(od.qty * COALESCE(od.price, p.product_price)), 0) AS sales,
                       COALESCE(SUM(od.qty * COALESCE(od.price, p.product_price) * " . self::LINE_RATE . "), 0) AS commission
                FROM orders o
                JOIN orderdetails od ON od.order_id = o.order_id
                JOIN products p ON od.product_id = p.product_id
                JOIN brands b ON p.product_brand = b.brand_id
                WHERE o.order_status <> 'Cancelled'" . ($since ? " AND o.order_date >= ?" : "");
        return $this->fetchOne($sql, $since ? [$since] : []);
    }

    public function getDeliveryFees($since = null)
    {
        $row = $this->fetchOne("SELECT COALESCE(SUM(delivery_fee), 0) AS fees FROM orders WHERE order_status <> 'Cancelled'" . ($since ? " AND order_date >= ?" : ""), $since ? [$since] : []);
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
                       (SELECT COUNT(*) FROM orders o WHERE o.brand_id = b.brand_id AND o.order_date >= ? AND o.order_status <> 'Cancelled') AS orders,
                       (SELECT COUNT(*) FROM orders o WHERE o.brand_id = b.brand_id AND o.order_status NOT IN ('Delivered', 'Cancelled')) AS active,
                       (SELECT COALESCE(SUM(od.qty * COALESCE(od.price, p.product_price)), 0)
                        FROM orderdetails od JOIN orders o ON od.order_id = o.order_id JOIN products p ON od.product_id = p.product_id
                        WHERE p.product_brand = b.brand_id AND o.order_date >= ? AND o.order_status <> 'Cancelled') AS sales,
                       (SELECT COALESCE(SUM(od.qty * COALESCE(od.price, p.product_price) * " . self::LINE_RATE . "), 0)
                        FROM orderdetails od JOIN orders o ON od.order_id = o.order_id JOIN products p ON od.product_id = p.product_id
                        WHERE p.product_brand = b.brand_id AND o.order_date >= ? AND o.order_status <> 'Cancelled') AS commission
                FROM brands b
                ORDER BY sales DESC, b.brand_name ASC";
        return $this->fetchAll($sql, [$since, $since, $since]);
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

    // ---------- Money report (admin) ----------

    /**
     * Meal sales and commission per kitchen for orders placed between two datetimes, split into
     * delivered orders (the kitchen is owed this) and orders still in progress (not owed yet).
     * Meals are counted under the kitchen that cooked them.
     */
    public function getMealMoneyByKitchen($from, $to)
    {
        $sql = "SELECT p.product_brand AS brand_id,
                       o.order_status = 'Delivered' AS delivered,
                       COALESCE(SUM(od.qty * COALESCE(od.price, p.product_price)), 0) AS sales,
                       COALESCE(SUM(od.qty * COALESCE(od.price, p.product_price) * " . self::LINE_RATE . "), 0) AS commission
                FROM orders o
                JOIN orderdetails od ON od.order_id = o.order_id
                JOIN products p ON od.product_id = p.product_id
                JOIN brands b ON p.product_brand = b.brand_id
                WHERE o.order_date >= ? AND o.order_date < ? AND o.order_status <> 'Cancelled'
                GROUP BY p.product_brand, delivered";
        return $this->fetchAll($sql, [$from, $to]);
    }

    /**
     * Order counts and delivery fees per kitchen for orders placed between two datetimes,
     * split into delivered and in progress. The delivery fee belongs to the kitchen that delivers the order.
     */
    public function getOrderMoneyByKitchen($from, $to)
    {
        $sql = "SELECT o.brand_id, o.order_status = 'Delivered' AS delivered,
                       COUNT(*) AS orders, COALESCE(SUM(o.delivery_fee), 0) AS delivery
                FROM orders o
                WHERE o.order_date >= ? AND o.order_date < ? AND o.order_status <> 'Cancelled'
                GROUP BY o.brand_id, delivered";
        return $this->fetchAll($sql, [$from, $to]);
    }

    /**
     * Every order placed between two datetimes with what it should have cost (meals + delivery)
     * and what was recorded as paid, for checking payments.
     */
    public function getPaymentCheck($from, $to)
    {
        $sql = "SELECT o.order_id, o.invoice_no, o.order_date, o.order_status, b.brand_name, c.customer_name,
                       (SELECT COALESCE(SUM(od.qty * COALESCE(od.price, pr.product_price)), 0)
                        FROM orderdetails od JOIN products pr ON od.product_id = pr.product_id
                        WHERE od.order_id = o.order_id) + o.delivery_fee AS expected,
                       (SELECT SUM(pay.amt) FROM payment pay WHERE pay.order_id = o.order_id) AS paid,
                       (SELECT COUNT(*) FROM payment pay WHERE pay.order_id = o.order_id) AS payments,
                       (SELECT GROUP_CONCAT(pay.transaction_reference SEPARATOR ', ') FROM payment pay WHERE pay.order_id = o.order_id) AS refs,
                       (SELECT COUNT(*) FROM payment pay WHERE pay.order_id = o.order_id AND pay.verified_at IS NOT NULL) AS verified,
                       r.amount AS refunded, r.status AS refund_status
                FROM orders o
                LEFT JOIN refunds r ON r.order_id = o.order_id
                JOIN customer c ON o.customer_id = c.customer_id
                LEFT JOIN brands b ON o.brand_id = b.brand_id
                WHERE o.order_date >= ? AND o.order_date < ?
                ORDER BY o.order_date DESC";
        return $this->fetchAll($sql, [$from, $to]);
    }

    // ---------- Kitchen portal ----------

    /**
     * A kitchen's orders, newest first. $scope: 'active' (not delivered yet), 'done' (delivered) or 'all'.
     */
    public function getKitchenOrders($brandId, $scope = 'active')
    {
        $where = "o.brand_id = ?";
        if ($scope === 'active') {
            $where .= " AND o.order_status NOT IN ('Delivered', 'Cancelled')";
        } elseif ($scope === 'done') {
            $where .= " AND o.order_status IN ('Delivered', 'Cancelled')";
        }
        // Totals count only this kitchen's meals: orders from before the one-kitchen rule can mix kitchens
        $sql = "SELECT o.*, c.customer_name, c.customer_contact,
                       (SELECT COALESCE(SUM(od.qty * COALESCE(od.price, pr.product_price)), 0)
                        FROM orderdetails od JOIN products pr ON od.product_id = pr.product_id
                        WHERE od.order_id = o.order_id AND pr.product_brand = o.brand_id) AS items_total,
                       (SELECT COALESCE(SUM(od.qty), 0) FROM orderdetails od JOIN products pr ON od.product_id = pr.product_id
                        WHERE od.order_id = o.order_id AND pr.product_brand = o.brand_id) AS item_count
                FROM orders o
                JOIN customer c ON o.customer_id = c.customer_id
                WHERE $where
                ORDER BY o.order_id DESC";
        return $this->fetchAll($sql, [$brandId]);
    }

    /**
     * Order count, meal sales, commission and delivery fees for a kitchen since a date (or all time when $since is null)
     */
    public function getKitchenSales($brandId, $since = null)
    {
        $params = $since ? [$brandId, $since] : [$brandId];
        $sql = "SELECT COUNT(DISTINCT o.order_id) AS orders,
                       COALESCE(SUM(od.qty * COALESCE(od.price, p.product_price)), 0) AS sales,
                       COALESCE(SUM(od.qty * COALESCE(od.price, p.product_price) * " . self::LINE_RATE . "), 0) AS commission,
                       COALESCE(SUM(od.qty), 0) AS meals
                FROM orders o
                JOIN orderdetails od ON od.order_id = o.order_id
                JOIN products p ON od.product_id = p.product_id AND p.product_brand = o.brand_id
                JOIN brands b ON p.product_brand = b.brand_id
                WHERE o.brand_id = ? AND o.order_status <> 'Cancelled'" . ($since ? " AND o.order_date >= ?" : "");
        $row = $this->fetchOne($sql, $params);
        $fees = $this->fetchOne("SELECT COALESCE(SUM(delivery_fee), 0) AS fees FROM orders WHERE brand_id = ? AND order_status <> 'Cancelled'" . ($since ? " AND order_date >= ?" : ""), $params);
        $row['delivery'] = (float)$fees['fees'];
        return $row;
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
                WHERE o.brand_id = ? AND o.order_status <> 'Cancelled'
                GROUP BY p.product_id, p.product_title
                ORDER BY meals DESC, sales DESC
                LIMIT " . intval($limit);
        return $this->fetchAll($sql, [$brandId]);
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
