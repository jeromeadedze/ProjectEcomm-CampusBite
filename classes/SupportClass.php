<?php
require_once __DIR__ . "/../core/db_class.php";

class SupportClass extends Database
{
    public function createMessage($customerId, $orderId, $topic, $message)
    {
        $sql = "INSERT INTO support_messages (customer_id, order_id, topic, message) VALUES (?, ?, ?, ?)";
        return $this->execute($sql, [$customerId, $orderId, $topic, $message]);
    }

    public function getMessagesByCustomer($customerId)
    {
        $sql = "SELECT s.*, o.invoice_no
                FROM support_messages s
                LEFT JOIN orders o ON s.order_id = o.order_id
                WHERE s.customer_id = ?
                ORDER BY s.message_id DESC";
        return $this->fetchAll($sql, [$customerId]);
    }

    // Open messages first, then newest
    public function getAllMessages()
    {
        $sql = "SELECT s.*, o.invoice_no, c.customer_name, c.customer_email, c.customer_contact
                FROM support_messages s
                JOIN customer c ON s.customer_id = c.customer_id
                LEFT JOIN orders o ON s.order_id = o.order_id
                ORDER BY (s.status = 'Open') DESC, s.message_id DESC";
        return $this->fetchAll($sql);
    }

    public function getMessageById($id)
    {
        return $this->fetchOne("SELECT * FROM support_messages WHERE message_id = ?", [$id]);
    }

    public function setStatus($id, $status)
    {
        return $this->execute("UPDATE support_messages SET status = ? WHERE message_id = ?", [$status, $id]);
    }
}
?>
