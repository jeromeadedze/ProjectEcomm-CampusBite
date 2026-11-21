<?php
/**
 * The replies under one support message, and the form to add one.
 * Needs: $msg (the message row), $threadReplies (its replies, oldest first),
 * $viewerRole ('customer', 'kitchen' or 'admin') and $ROOT_DIR.
 */
// What was typed in a reply that failed to send, put back in its form (read once per page)
if (!isset($replyDraft)) {
    $replyDraft = $_SESSION['reply_draft'] ?? [];
    unset($_SESSION['reply_draft']);
}
$draftHere = (int)($replyDraft['message_id'] ?? 0) === (int)$msg['message_id'];
$isStaff = $viewerRole !== 'customer';

$authorLabel = function ($reply) use ($msg, $viewerRole) {
    if ($reply['author_role'] === 'customer') {
        return $viewerRole === 'customer' ? 'You' : $msg['customer_name'];
    }
    if ($reply['author_role'] === 'kitchen') {
        return ($viewerRole === 'kitchen' ? 'You · ' : '') . ($msg['brand_name'] ?: 'Kitchen');
    }
    return 'CampusBite support' . ($viewerRole === 'admin' && !empty($reply['author_name']) ? ' · ' . $reply['author_name'] : '');
};
?>
<?php if (!empty($threadReplies)): ?>
    <ol class="cb-thread" aria-label="Replies">
        <?php foreach ($threadReplies as $reply): ?>
            <?php $fromStaff = $reply['author_role'] !== 'customer'; ?>
            <li class="cb-reply <?php echo $fromStaff ? 'is-staff' : 'is-customer'; ?>">
                <div class="cb-reply-head">
                    <b><?php echo htmlspecialchars($authorLabel($reply)); ?></b>
                    <small><?php echo date('M d · h:i A', strtotime($reply['created_at'])); ?></small>
                </div>
                <p><?php echo nl2br(htmlspecialchars($reply['body'])); ?></p>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>

<details class="cb-reply-form" <?php echo $draftHere ? 'open' : ''; ?>>
    <summary class="cb-btn cb-btn-quiet"><i class="bi bi-reply" aria-hidden="true"></i> Reply</summary>
    <form action="<?php echo $ROOT_DIR; ?>/actions/reply_message_action.php" method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="message_id" value="<?php echo (int)$msg['message_id']; ?>">
        <label class="cb-sr-only" for="reply-<?php echo (int)$msg['message_id']; ?>">Your reply</label>
        <textarea id="reply-<?php echo (int)$msg['message_id']; ?>" name="body" rows="3" minlength="2" maxlength="<?php echo SupportController::MAX_MESSAGE; ?>" required
                  placeholder="<?php echo $isStaff ? 'Write to ' . htmlspecialchars(explode(' ', trim($msg['customer_name']))[0]) . '. They get an email with your reply.' : 'Add more details or answer their question'; ?>"><?php echo $draftHere ? htmlspecialchars($replyDraft['body']) : ''; ?></textarea>
        <div class="cb-reply-actions">
            <?php if ($isStaff): ?>
                <label class="cb-check"><input type="checkbox" name="resolve" value="1"> Mark resolved after sending</label>
            <?php elseif ($msg['status'] === 'Resolved'): ?>
                <span class="cb-muted">Replying opens this message again.</span>
            <?php endif; ?>
            <button type="submit" class="cb-btn">Send reply</button>
        </div>
    </form>
</details>
