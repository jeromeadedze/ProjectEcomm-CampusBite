<?php
// Help & support: send a message to a kitchen or the CampusBite team, or contact the kitchen directly
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
require_once __DIR__ . '/../core/core.php';
require_login();
// Customers write in here; kitchens and admins answer from their own inboxes
if (!can_order()) {
    header("Location: .." . (is_kitchen() ? '/views/kitchen/kitchen_messages.php' : '/views/admin/messages.php'));
    exit;
}

require_once __DIR__ . '/../controllers/SupportController.php';
require_once __DIR__ . '/../controllers/OrderController.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$supportCtrl = new SupportController();
$myOrders = (new OrderController())->getOrdersByCustomer(get_user_id());
// Active kitchens only (paused ones can't take messages)
$kitchens = (new ProductController())->getAllBrands();
$myMessages = $supportCtrl->getMessagesByCustomer(get_user_id());
$replies = $supportCtrl->getRepliesFor($myMessages);
$viewerRole = 'customer';

// A "Get help" link on an order page arrives with ?order_id=
$form = $_SESSION['contact_form'] ?? [
    'topic' => isset($_GET['order_id']) ? 'Order issue' : '',
    'order_id' => (int)($_GET['order_id'] ?? 0),
    'brand_id' => (int)($_GET['brand_id'] ?? 0),
    'message' => '',
];
unset($_SESSION['contact_form']);

$liveRefresh = true; // orders and messages change on their own: the page updates itself every 5 s
require_once __DIR__ . '/layout/header.php';
?>

<p class="cb-sub">Have a question about your food, delivery, or account? Reach the kitchen directly or send a message.</p>

<div class="cb-settings">
    <section class="cb-card">
        <h2>Send a message</h2>
        <form class="cb-admin-form" action="<?php echo $ROOT_DIR; ?>/actions/send_message_action.php" method="POST">
            <?php echo csrf_field(); ?>
            <?php echo form_message_html(take_form_message('contact')); ?>
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
                    <option value="0" data-brand-id="0" data-brand-name="" data-brand-phone="" data-invoice="">Not about a specific order</option>
                    <?php foreach ($myOrders as $ord): ?>
                        <option value="<?php echo (int)$ord['order_id']; ?>"
                                data-brand-id="<?php echo (int)($ord['brand_id'] ?? 0); ?>"
                                data-brand-name="<?php echo htmlspecialchars($ord['brand_name'] ?? 'Kitchen'); ?>"
                                data-brand-phone="<?php echo htmlspecialchars($ord['kitchen_contact'] ?? ''); ?>"
                                data-invoice="<?php echo htmlspecialchars($ord['invoice_no']); ?>"
                                <?php echo (int)$form['order_id'] === (int)$ord['order_id'] ? 'selected' : ''; ?>>
                            #<?php echo htmlspecialchars($ord['invoice_no']); ?> · <?php echo htmlspecialchars($ord['brand_name'] ?? 'Kitchen'); ?> · <?php echo date('M d', strtotime($ord['order_date'])); ?> · <?php echo htmlspecialchars($ord['order_status']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="cb-field" id="wrap_brand_id">
                <label for="brand_id">Kitchen <span class="cb-muted">(optional)</span></label>
                <select id="brand_id" name="brand_id">
                    <option value="0" data-phone="">General / CampusBite Support</option>
                    <?php foreach ($kitchens as $k): ?>
                        <option value="<?php echo (int)$k['brand_id']; ?>" data-phone="<?php echo htmlspecialchars($k['brand_phone'] ?? ''); ?>" <?php echo (int)$form['brand_id'] === (int)$k['brand_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($k['brand_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="cb-field cb-field-full" id="direct_contact_wrap" style="display:none;">
                <div class="cb-direct-contact">
                    <div class="cb-direct-contact-head">
                        <i class="bi bi-shop" aria-hidden="true"></i>
                        <div>
                            <b id="contact_kitchen_name" style="display:block;font-size:14px;font-weight:600">Kitchen Support</b>
                            <span style="font-size:12.5px;color:var(--muted)">Need a fast answer about your food or delivery? Reach the kitchen:</span>
                        </div>
                    </div>
                    <div class="cb-direct-contact-actions">
                        <a id="btn_call_kitchen" href="#" class="cb-btn cb-btn-quiet">
                            <i class="bi bi-telephone" aria-hidden="true"></i> <span id="lbl_call_kitchen">Call</span>
                        </a>
                        <a id="btn_wa_kitchen" href="#" target="_blank" rel="noopener" class="cb-btn cb-btn-quiet">
                            <i class="bi bi-whatsapp" aria-hidden="true"></i> WhatsApp
                        </a>
                    </div>
                </div>
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
                    <?php $msg['customer_name'] = $_SESSION['customer_name'] ?? ''; ?>
                    <li id="message-<?php echo (int)$msg['message_id']; ?>">
                        <div class="cb-messages-head">
                            <b>
                                <?php echo htmlspecialchars($msg['topic']); ?>
                                <?php if (!empty($msg['brand_name'])): ?>
                                    · <span class="cb-muted">to</span> <?php echo htmlspecialchars($msg['brand_name']); ?>
                                <?php endif; ?>
                                <?php echo !empty($msg['invoice_no']) ? ' · order #' . htmlspecialchars($msg['invoice_no']) : ''; ?>
                                <?php if (SupportController::waitingOn($msg) === 'customer'): ?><span class="cb-wait-chip is-you">New reply</span><?php endif; ?>
                            </b>
                            <span class="cb-dot-status<?php echo $msg['status'] === 'Resolved' ? ' off' : ''; ?>"><?php echo htmlspecialchars($msg['status']); ?></span>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                        <small><?php echo date('M d, Y · h:i A', strtotime($msg['created_at'])); ?></small>
                        <?php $threadReplies = $replies[(int)$msg['message_id']] ?? []; include __DIR__ . '/layout/support_thread.php'; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const orderSelect = document.getElementById('order_id');
    const brandSelect = document.getElementById('brand_id');
    const brandWrap = document.getElementById('wrap_brand_id');
    const directBox = document.getElementById('direct_contact_wrap');
    const nameEl = document.getElementById('contact_kitchen_name');
    const callBtn = document.getElementById('btn_call_kitchen');
    const callLbl = document.getElementById('lbl_call_kitchen');
    const waBtn = document.getElementById('btn_wa_kitchen');

    function updateContact() {
        const orderVal = orderSelect ? orderSelect.value : '0';
        let brandName = '';
        let phone = '';
        let invoice = '';

        if (orderVal !== '0') {
            if (brandWrap) brandWrap.style.display = 'none';
            const opt = orderSelect.options[orderSelect.selectedIndex];
            brandName = opt.dataset.brandName || '';
            phone = opt.dataset.brandPhone || '';
            invoice = opt.dataset.invoice || '';
        } else {
            if (brandWrap) brandWrap.style.display = '';
            if (brandSelect && brandSelect.value !== '0') {
                const opt = brandSelect.options[brandSelect.selectedIndex];
                brandName = opt.text ? opt.text.trim() : '';
                phone = opt.dataset.phone || '';
            }
        }

        if (phone && brandName) {
            nameEl.textContent = brandName;
            callBtn.href = 'tel:' + phone.replace(/[^0-9+]/g, '');
            callLbl.textContent = 'Call ' + phone;
            let cleanWa = phone.replace(/[^0-9]/g, '');
            if (cleanWa.startsWith('0') && cleanWa.length === 10) {
                cleanWa = '233' + cleanWa.slice(1);
            }
            const waMsg = invoice
                ? 'Hi ' + brandName + ', I am checking on my order #' + invoice + '.'
                : 'Hi ' + brandName + ', I am contacting you from CampusBite.';
            waBtn.href = 'https://wa.me/' + cleanWa + '?text=' + encodeURIComponent(waMsg);
            directBox.style.display = 'block';
        } else {
            directBox.style.display = 'none';
        }
    }

    if (orderSelect) orderSelect.addEventListener('change', updateContact);
    if (brandSelect) brandSelect.addEventListener('change', updateContact);
    updateContact();
});
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
