<?php
require_once __DIR__ . "/../core/db_class.php";

class SupportClass extends Database
{
    public function createMessage($customerId, $orderId, $brandId, $topic, $message)
    {
        $sql = "INSERT INTO support_messages (customer_id, order_id, brand_id, topic, message) VALUES (?, ?, ?, ?, ?)";
        return $this->execute($sql, [$customerId, $orderId, $brandId, $topic, $message]);
    }

    public function getMessagesByCustomer($customerId)
    {
        $sql = "SELECT s.*, (SELECT COUNT(*) FROM support_replies r WHERE r.message_id = s.message_id) AS reply_count,
                       (SELECT r.author_role FROM support_replies r WHERE r.message_id = s.message_id ORDER BY r.reply_id DESC LIMIT 1) AS last_role,
                       COALESCE((SELECT MAX(r.created_at) FROM support_replies r WHERE r.message_id = s.message_id), s.created_at) AS last_activity,
                       o.invoice_no, b.brand_name, b.brand_phone
                FROM support_messages s
                LEFT JOIN orders o ON s.order_id = o.order_id
                LEFT JOIN brands b ON COALESCE(s.brand_id, o.brand_id) = b.brand_id
                WHERE s.customer_id = ?
                ORDER BY last_activity DESC, s.message_id DESC";
        return $this->fetchAll($sql, [$customerId]);
    }

    public function getMessagesByKitchen($brandId)
    {
        $sql = "SELECT s.*, (SELECT COUNT(*) FROM support_replies r WHERE r.message_id = s.message_id) AS reply_count,
                       (SELECT r.author_role FROM support_replies r WHERE r.message_id = s.message_id ORDER BY r.reply_id DESC LIMIT 1) AS last_role,
                       COALESCE((SELECT MAX(r.created_at) FROM support_replies r WHERE r.message_id = s.message_id), s.created_at) AS last_activity,
                       o.invoice_no, o.order_status, o.drop_location,
                       c.customer_name, c.customer_email, c.customer_contact
                FROM support_messages s
                JOIN customer c ON s.customer_id = c.customer_id
                LEFT JOIN orders o ON s.order_id = o.order_id
                WHERE s.brand_id = ? OR (s.order_id IS NOT NULL AND o.brand_id = ?)
                ORDER BY (s.status = 'Open') DESC, last_activity DESC, s.message_id DESC";
        return $this->fetchAll($sql, [$brandId, $brandId]);
    }

    // Open messages first, then newest
    public function getAllMessages()
    {
        $sql = "SELECT s.*, (SELECT COUNT(*) FROM support_replies r WHERE r.message_id = s.message_id) AS reply_count,
                       (SELECT r.author_role FROM support_replies r WHERE r.message_id = s.message_id ORDER BY r.reply_id DESC LIMIT 1) AS last_role,
                       COALESCE((SELECT MAX(r.created_at) FROM support_replies r WHERE r.message_id = s.message_id), s.created_at) AS last_activity,
                       o.invoice_no, c.customer_name, c.customer_email, c.customer_contact, b.brand_name
                FROM support_messages s
                JOIN customer c ON s.customer_id = c.customer_id
                LEFT JOIN orders o ON s.order_id = o.order_id
                LEFT JOIN brands b ON COALESCE(s.brand_id, o.brand_id) = b.brand_id
                ORDER BY (s.status = 'Open') DESC, last_activity DESC, s.message_id DESC";
        return $this->fetchAll($sql);
    }

    // One message with what's needed to decide who may see it and to email the customer
    public function getMessageById($id)
    {
        $sql = "SELECT s.*, o.brand_id AS order_brand_id, o.invoice_no, c.customer_name, c.customer_email, b.brand_name
                FROM support_messages s
                JOIN customer c ON s.customer_id = c.customer_id
                LEFT JOIN orders o ON s.order_id = o.order_id
                LEFT JOIN brands b ON COALESCE(s.brand_id, o.brand_id) = b.brand_id
                WHERE s.message_id = ?";
        return $this->fetchOne($sql, [$id]);
    }

    public function addReply($messageId, $authorId, $role, $body)
    {
        $sql = "INSERT INTO support_replies (message_id, author_id, author_role, body) VALUES (?, ?, ?, ?)";
        return $this->execute($sql, [$messageId, $authorId, $role, $body]);
    }

    // Replies for a set of messages, oldest first, grouped as [message_id => [reply, ...]]
    public function getRepliesFor(array $messageIds)
    {
        $ids = array_values(array_unique(array_map('intval', $messageIds)));
        if (!$ids) {
            return [];
        }
        $sql = "SELECT r.*, c.customer_name AS author_name
                FROM support_replies r
                LEFT JOIN customer c ON c.customer_id = r.author_id
                WHERE r.message_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")
                ORDER BY r.reply_id ASC";
        $grouped = [];
        foreach ($this->fetchAll($sql, $ids) as $reply) {
            $grouped[(int)$reply['message_id']][] = $reply;
        }
        return $grouped;
    }

    public function setStatus($id, $status)
    {
        return $this->execute("UPDATE support_messages SET status = ? WHERE message_id = ?", [$status, $id]);
    }
}
?>
