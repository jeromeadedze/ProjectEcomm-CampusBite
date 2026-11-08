<?php
// Help & support: send the CampusBite team a message, optionally about a specific order
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/SupportController.php';
require_once __DIR__ . '/../controllers/OrderController.php';

require_login();

$supportCtrl = new SupportController();
$myOrders = (new OrderController())->getOrdersByCustomer(get_user_id());
$myMessages = $supportCtrl->getMessagesByCustomer(get_user_id());

// A "Get help" link on an order page arrives with ?order_id=
$form = $_SESSION['contact_form'] ?? [
    'topic' => isset($_GET['order_id']) ? 'Order issue' : '',
    'order_id' => (int)($_GET['order_id'] ?? 0),
    'message' => '',
];
unset($_SESSION['contact_form']);
?>

<p class="cb-sub">Something wrong with an order, a payment or your account? Tell us and the team will follow up by phone or email.</p>

<div class="cb-settings">
    <section class="cb-card">
        <h2>Send a message</h2>
        <form class="cb-admin-form" action="<?php echo $ROOT_DIR; ?>/actions/send_message_action.php" method="POST">
            <?php echo csrf_field(); ?>
            <div class="cb-field">
                <label for="topic">What is it about?</label>
                <select id="topic" name="topic" required>
                    <option value="">Choose a topic</option>
                    <?php foreach (SupportController::TOPICS as $topicValue => $topicLabel): ?>
                        <option value="<?php echo htmlspecialchars($topicValue); ?>" <?php echo $form['topic'] === $topicValue ? 'selected' : ''; ?>><?php echo htmlspecialchars($topicLabel); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="cb-field">
                <label for="order_id">Order <span class="cb-muted">(optional)</span></label>
                <select id="order_id" name="order_id">
                    <option value="0">Not about a specific order</option>
                    <?php foreach ($myOrders as $ord): ?>
                        <option value="<?php echo (int)$ord['order_id']; ?>" <?php echo (int)$form['order_id'] === (int)$ord['order_id'] ? 'selected' : ''; ?>>
                            #<?php echo htmlspecialchars($ord['invoice_no']); ?> · <?php echo date('M d', strtotime($ord['order_date'])); ?> · <?php echo htmlspecialchars($ord['order_status']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="cb-field cb-field-full">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="6" minlength="10" maxlength="<?php echo SupportController::MAX_MESSAGE; ?>" placeholder="What happened, and what would you like us to do?" required><?php echo htmlspecialchars($form['message']); ?></textarea>
            </div>
            <div class="cb-admin-actions">
                <button type="submit" class="cb-btn">Send message</button>
            </div>
        </form>
    </section>

    <?php if (!empty($myMessages)): ?>
        <section class="cb-card">
            <h2>Your messages</h2>
            <ul class="cb-messages">
                <?php foreach ($myMessages as $msg): ?>
                    <li>
                        <div class="cb-messages-head">
                            <b><?php echo htmlspecialchars($msg['topic']); ?><?php echo $msg['invoice_no'] ? ' · order #' . htmlspecialchars($msg['invoice_no']) : ''; ?></b>
                            <span class="cb-dot-status<?php echo $msg['status'] === 'Resolved' ? ' off' : ''; ?>"><?php echo htmlspecialchars($msg['status']); ?></span>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                        <small><?php echo date('M d, Y · h:i A', strtotime($msg['created_at'])); ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
