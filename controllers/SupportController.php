<?php
require_once __DIR__ . "/../classes/SupportClass.php";
require_once __DIR__ . "/../classes/OrderClass.php";
require_once __DIR__ . "/../core/mailer.php";

class SupportController
{
    const TOPICS = [
        'Order issue' => 'Problem with an order',
        'Payment' => 'Payment or refund',
        'Account' => 'My account',
        'Feedback' => 'Feedback or suggestion',
        'Other' => 'Something else',
    ];
    const STATUSES = ['Open', 'Resolved'];
    const MAX_MESSAGE = 2000;

    private $supportModel;

    public function __construct()
    {
        $this->supportModel = new SupportClass();
    }

    /**
     * Checks and saves a message. Returns ['values' => form values, 'error' => message or null].
     */
    public function submitMessage($customerId, $input)
    {
        $values = [
            'topic' => trim($input['topic'] ?? ''),
            'order_id' => (int)($input['order_id'] ?? 0),
            'brand_id' => (int)($input['brand_id'] ?? 0),
            'message' => trim($input['message'] ?? ''),
        ];

        $error = null;
        if (!array_key_exists($values['topic'], self::TOPICS)) {
            $error = "Choose what your message is about.";
        } elseif (mb_strlen($values['message']) < 10) {
            $error = "Tell us a little more: at least 10 characters.";
        } elseif (mb_strlen($values['message']) > self::MAX_MESSAGE) {
            $error = "Keep your message to " . self::MAX_MESSAGE . " characters or fewer.";
        } elseif ($values['order_id'] > 0) {
            // Only the customer's own orders can be attached
            $order = (new OrderClass())->getOrderById($values['order_id']);
            if (!$order || (int)$order['customer_id'] !== (int)$customerId) {
                $error = "Choose one of your orders from the list.";
            } else {
                // When tied to an order, automatically route to the order's kitchen
                $values['brand_id'] = (int)($order['brand_id'] ?? 0);
            }
        }

        if ($error === null && !$this->supportModel->createMessage($customerId, $values['order_id'] ?: null, $values['brand_id'] ?: null, $values['topic'], $values['message'])) {
            $error = "Your message couldn't be sent. Try again.";
        }

        return ['values' => $values, 'error' => $error];
    }

    public function getMessagesByCustomer($customerId)
    {
        return $this->supportModel->getMessagesByCustomer($customerId);
    }

    public function getMessagesByKitchen($brandId)
    {
        return $this->supportModel->getMessagesByKitchen($brandId);
    }

    public function getAllMessages()
    {
        return $this->supportModel->getAllMessages();
    }

    public function getMessageById($id)
    {
        return $this->supportModel->getMessageById($id);
    }

    public function setStatus($id, $status)
    {
        return in_array($status, self::STATUSES, true) ? $this->supportModel->setStatus($id, $status) : false;
    }

    /**
     * How the signed-in person takes part in a conversation: 'admin' (any message), 'kitchen' (messages sent to
     * its kitchen or about its orders), 'customer' (their own messages), or null if they can't see it.
     */
    public function roleFor($msg)
    {
        if (!$msg) {
            return null;
        }
        if (is_admin()) {
            return 'admin';
        }
        // Kitchen staff answer customer messages; the kitchen admin doesn't handle them
        $kitchenId = is_kitchen_staff() ? get_kitchen_id() : null;
        if (is_kitchen_admin()) {
            return null;
        }
        if ($kitchenId) {
            $handles = (int)$msg['brand_id'] === $kitchenId || (int)($msg['order_brand_id'] ?? 0) === $kitchenId;
            return $handles ? 'kitchen' : null;
        }
        return (int)$msg['customer_id'] === (int)get_user_id() ? 'customer' : null;
    }

    // Who an open conversation is waiting on: 'staff' (no answer yet, or the customer wrote last) or 'customer'
    public static function waitingOn($msg)
    {
        if ($msg['status'] !== 'Open') {
            return null;
        }
        return in_array($msg['last_role'] ?? null, ['kitchen', 'admin'], true) ? 'customer' : 'staff';
    }

    // Replies for a list of messages: [message_id => [reply, ...]]
    public function getRepliesFor(array $messages)
    {
        return $this->supportModel->getRepliesFor(array_column($messages, 'message_id'));
    }

    /**
     * A customer replied: tell whoever answers the conversation, so it isn't missed. The kitchen's staff
     * for a message to a kitchen or about its order; CampusBite's admins otherwise. A failed email never stops the reply.
     */
    private function emailStaffAboutReply($msg, $body)
    {
        require_once __DIR__ . '/../classes/CustomerClass.php';
        $customers = new CustomerClass();
        $kitchenId = (int)($msg['brand_id'] ?: ($msg['order_brand_id'] ?? 0));
        if ($kitchenId) {
            $people = $customers->getKitchenAlertLogins($kitchenId);
            $link = app_url('views/kitchen/kitchen_messages.php#message-' . (int)$msg['message_id']);
        } else {
            $people = $customers->getAdminLogins();
            $link = app_url('views/admin/messages.php#message-' . (int)$msg['message_id']);
        }
        $about = $msg['topic'] . (!empty($msg['invoice_no']) ? ' (order #' . $msg['invoice_no'] . ')' : '');
        foreach ($people as $person) {
            $first = explode(' ', trim($person['customer_name']))[0];
            $lines = ["Hi $first,", "{$msg['customer_name']} replied to their message about $about:", $body];
            send_email($person['customer_email'], $person['customer_name'], "{$msg['customer_name']} replied to their message",
                email_html('New reply from ' . $msg['customer_name'], $lines, 'View and reply', $link, 'Reply on CampusBite, not to this email.'),
                email_text('New reply from ' . $msg['customer_name'], $lines, 'View and reply', $link, 'Reply on CampusBite, not to this email.'));
        }
    }

    /**
     * Adds a reply. A customer's reply reopens a resolved conversation; staff can resolve it as they reply.
     * When a kitchen or admin replies, the customer gets an email. Returns an error message, or null.
     */
    public function reply($messageId, $body, $resolve = false)
    {
        $msg = $this->supportModel->getMessageById($messageId);
        $role = $this->roleFor($msg);
        $body = trim((string)$body);
        if (!$role) {
            return "You can't reply to that message.";
        }
        if (mb_strlen($body) < 2) {
            return 'Write a reply first.';
        }
        if (mb_strlen($body) > self::MAX_MESSAGE) {
            return 'Keep your reply to ' . self::MAX_MESSAGE . ' characters or fewer.';
        }
        if (!$this->supportModel->addReply($messageId, get_user_id(), $role, $body)) {
            return "Your reply couldn't be sent. Try again.";
        }

        if ($role === 'customer') {
            $this->supportModel->setStatus($messageId, 'Open');
            $this->emailStaffAboutReply($msg, $body);
            return null;
        }
        $this->supportModel->setStatus($messageId, $resolve ? 'Resolved' : 'Open');

        $from = $role === 'kitchen' ? ($msg['brand_name'] ?: 'The kitchen') : 'CampusBite support';
        $first = explode(' ', trim($msg['customer_name']))[0];
        $about = $msg['topic'] . (!empty($msg['invoice_no']) ? ' (order #' . $msg['invoice_no'] . ')' : '');
        $lines = ["Hi $first,", "$from replied to your message about $about:", $body];
        $link = app_url('views/contact.php#message-' . (int)$messageId);
        $foot = $resolve ? 'They marked your message resolved. Reply on CampusBite if you still need help, and it opens again.' : 'Reply on CampusBite, not to this email.';
        send_email($msg['customer_email'], $msg['customer_name'], "$from replied to your message",
            email_html("$from replied", $lines, 'View and reply', $link, $foot),
            email_text("$from replied", $lines, 'View and reply', $link, $foot));
        return null;
    }
}
?>
