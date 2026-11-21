<?php
// Kitchen portal: messages from customers about their orders or meals
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../controllers/SupportController.php';

require_kitchen();

$kitchenId = get_kitchen_id();
$kitchen = (new ProductController())->getBrandById($kitchenId);
$supportCtrl = new SupportController();
$messages = $supportCtrl->getMessagesByKitchen($kitchenId);
$replies = $supportCtrl->getRepliesFor($messages);
$viewerRole = 'kitchen';

$openCount = count(array_filter($messages, function ($msg) {
    return $msg['status'] === 'Open';
}));
$needReply = count(array_filter($messages, function ($msg) {
    return SupportController::waitingOn($msg) === 'staff';
}));

$pageHeading = $kitchen['brand_name'] . ' messages';
require_once __DIR__ . '/../layout/header.php';
?>

<p class="cb-sub"><?php echo $needReply; ?> need a reply · <?php echo $openCount; ?> open · <?php echo count($messages); ?> total</p>

<section class="cb-card">
    <?php if (empty($messages)): ?>
        <p class="cb-table-empty">No messages yet. Messages from shoppers about their orders or your meals will appear here.</p>
    <?php else: ?>
        <ul class="cb-messages">
            <?php foreach ($messages as $msg): ?>
                <?php $msg['brand_name'] = $kitchen['brand_name']; $waiting = SupportController::waitingOn($msg); ?>
                <li id="message-<?php echo (int)$msg['message_id']; ?>" class="<?php echo $msg['status'] === 'Resolved' ? 'is-resolved' : ''; ?>">
                    <div class="cb-messages-head">
                        <b>
                            <?php echo htmlspecialchars($msg['topic']); ?>
                            <?php if (!empty($msg['order_id'])): ?>
                                · <a class="cb-link" href="<?php echo $ROOT_DIR; ?>/views/order_tracking.php?order_id=<?php echo (int)$msg['order_id']; ?>">order #<?php echo htmlspecialchars($msg['invoice_no']); ?></a>
                                <?php if (!empty($msg['drop_location'])): ?>
                                    <span class="cb-muted">(to <?php echo htmlspecialchars($msg['drop_location']); ?>)</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if ($waiting): ?><span class="cb-wait-chip <?php echo $waiting === 'staff' ? 'is-you' : ''; ?>"><?php echo $waiting === 'staff' ? 'Needs a reply' : 'Waiting for customer'; ?></span><?php endif; ?>
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
                    <?php $threadReplies = $replies[(int)$msg['message_id']] ?? []; include __DIR__ . '/../layout/support_thread.php'; ?>

                    <div class="cb-messages-foot">
                        <small>
                            <?php echo htmlspecialchars($msg['customer_name']); ?> ·
                            <a class="cb-link" href="mailto:<?php echo htmlspecialchars($msg['customer_email']); ?>"><?php echo htmlspecialchars($msg['customer_email']); ?></a> ·
                            <?php echo htmlspecialchars($msg['customer_contact']); ?> ·
                            <?php echo date('M d, Y · h:i A', strtotime($msg['created_at'])); ?>
                        </small>
                        <div class="cb-messages-actions">
                            <a href="<?php echo phone_tel_url($msg['customer_contact']); ?>" class="cb-btn cb-btn-quiet">
                                <i class="bi bi-telephone" aria-hidden="true"></i> Call customer
                            </a>
                            <a href="<?php echo phone_wa_url($msg['customer_contact'], 'Hi ' . $msg['customer_name'] . ', this is ' . $kitchen['brand_name'] . ' regarding your message' . (!empty($msg['invoice_no']) ? ' on order #' . $msg['invoice_no'] : '')); ?>" target="_blank" rel="noopener" class="cb-btn cb-btn-quiet">
                                <i class="bi bi-whatsapp" aria-hidden="true"></i> WhatsApp
                            </a>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
