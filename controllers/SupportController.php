<?php
require_once __DIR__ . "/../classes/SupportClass.php";
require_once __DIR__ . "/../classes/OrderClass.php";

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
            }
        }

        if ($error === null && !$this->supportModel->createMessage($customerId, $values['order_id'] ?: null, $values['topic'], $values['message'])) {
            $error = "Your message couldn't be sent. Try again.";
        }

        return ['values' => $values, 'error' => $error];
    }

    public function getMessagesByCustomer($customerId)
    {
        return $this->supportModel->getMessagesByCustomer($customerId);
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
}
?>
