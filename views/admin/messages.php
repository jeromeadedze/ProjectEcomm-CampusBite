<?php
// Support inbox for admins: open messages first
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../controllers/SupportController.php';

require_admin();

$messages = (new SupportController())->getAllMessages();
$openCount = count(array_filter($messages, function ($msg) {
    return $msg['status'] === 'Open';
}));
?>

<?php require __DIR__ . '/../layout/admin_nav.php'; ?>

<p class="cb-sub"><?php echo $openCount; ?> open · <?php echo count($messages); ?> total</p>

<section class="cb-card">
    <?php if (empty($messages)): ?>
        <p class="cb-table-empty">No messages yet.</p>
    <?php else: ?>
        <ul class="cb-messages">
            <?php foreach ($messages as $msg): ?>
                <li class="<?php echo $msg['status'] === 'Resolved' ? 'is-resolved' : ''; ?>">
                    <div class="cb-messages-head">
                        <b>
                            <?php echo htmlspecialchars($msg['topic']); ?>
                            <?php if ($msg['order_id']): ?>
                                · <a class="cb-link" href="<?php echo $ROOT_DIR; ?>/views/order_tracking.php?order_id=<?php echo (int)$msg['order_id']; ?>">order #<?php echo htmlspecialchars($msg['invoice_no']); ?></a>
                            <?php endif; ?>
                        </b>
                        <form action="<?php echo $ROOT_DIR; ?>/actions/update_message_status_action.php" method="POST">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="message_id" value="<?php echo (int)$msg['message_id']; ?>">
                            <?php if ($msg['status'] === 'Open'): ?>
                                <input type="hidden" name="status" value="Resolved">
                                <button type="submit" class="cb-btn">Mark resolved</button>
                            <?php else: ?>
                                <input type="hidden" name="status" value="Open">
                                <button type="submit" class="cb-btn cb-btn-quiet">Reopen</button>
                            <?php endif; ?>
                        </form>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                    <small>
                        <?php echo htmlspecialchars($msg['customer_name']); ?> ·
                        <a class="cb-link" href="mailto:<?php echo htmlspecialchars($msg['customer_email']); ?>"><?php echo htmlspecialchars($msg['customer_email']); ?></a> ·
                        <?php echo htmlspecialchars($msg['customer_contact']); ?> ·
                        <?php echo date('M d, Y · h:i A', strtotime($msg['created_at'])); ?>
                    </small>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
