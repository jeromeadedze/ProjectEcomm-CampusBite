<?php
// Kitchen admin: the people who work the kitchen's shifts. Staff logins handle orders and messages and never see money.
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../controllers/CustomerController.php';

require_kitchen_admin();

$kitchenId = get_kitchen_id();
$kitchen = (new ProductController())->getBrandById($kitchenId);
$team = (new CustomerController())->getKitchenTeam($kitchenId);
$staff = array_values(array_filter($team, function ($p) { return $p['kitchen_role'] === 'staff'; }));
$admins = array_values(array_filter($team, function ($p) { return $p['kitchen_role'] !== 'staff'; }));

// The form stays folded away unless a save just failed (then it reopens with what was typed)
$formOpen = isset($_SESSION['staff_form']) || isset($_GET['new']);
$form = ($_SESSION['staff_form'] ?? []) + ['customer_name' => '', 'customer_email' => '', 'customer_contact' => '', 'personal_email' => ''];
unset($_SESSION['staff_form']);

$pageHeading = 'Staff';
require_once __DIR__ . '/../layout/header.php';
$initials = function ($name) {
    $parts = preg_split('/\s+/', trim($name));
    return strtoupper(mb_substr($parts[0] ?? '', 0, 1) . mb_substr(end($parts) ?: '', 0, 1));
};
?>

<div class="mf-bar">
    <p class="cb-sub mf-sub"><?php echo htmlspecialchars($kitchen['brand_name']); ?> · <?php echo count($staff); ?> staff login<?php echo count($staff) === 1 ? '' : 's'; ?></p>
    <button type="button" class="cb-btn" id="mf-open" aria-expanded="<?php echo $formOpen ? 'true' : 'false'; ?>" aria-controls="mf-card" <?php echo $formOpen ? 'hidden' : ''; ?>><i class="bi bi-person-plus" aria-hidden="true"></i> Add a staff member</button>
</div>

<?php require __DIR__ . '/../layout/new_login_card.php'; ?>

<section class="cb-card mf-card" id="mf-card" aria-labelledby="mf-title" <?php echo $formOpen ? '' : 'hidden'; ?>>
    <div class="mf-head">
        <h2 id="mf-title">Add a staff member</h2>
        <p class="mf-lead">They'll handle orders, customer messages and sold-out meals. They never see sales, prices you set or payouts.</p>
    </div>
    <form class="mf-form mf-form-single" action="<?php echo $ROOT_DIR; ?>/actions/kitchen_add_staff_action.php" method="POST" autocomplete="off">
        <?php echo csrf_field(); ?>
        <div class="mf-main">
            <fieldset class="mf-section">
                <legend>Who they are</legend>
                <div class="cb-field">
                    <label for="staff_name">Full name</label>
                    <input type="text" id="staff_name" name="customer_name" maxlength="100" value="<?php echo htmlspecialchars($form['customer_name']); ?>" placeholder="Kwame Boateng" required>
                </div>
                <div class="cb-field mf-wide">
                    <label for="staff_personal">Their own email</label>
                    <input type="email" id="staff_personal" name="personal_email" maxlength="100" value="<?php echo htmlspecialchars($form['personal_email']); ?>" placeholder="kwame.boateng@gmail.com" aria-describedby="staff_personal_hint" required>
                    <p class="cb-field-hint" id="staff_personal_hint">We'll email their sign-in details here.</p>
                </div>
                <div class="cb-field">
                    <label for="staff_phone">Phone</label>
                    <input type="tel" id="staff_phone" name="customer_contact" maxlength="15" inputmode="tel" value="<?php echo htmlspecialchars($form['customer_contact']); ?>" placeholder="0241234567" required>
                </div>
            </fieldset>

            <fieldset class="mf-section">
                <legend>How they sign in</legend>
                <div class="mf-wide lp" data-login-preview data-kitchen="<?php echo htmlspecialchars($kitchen['brand_name']); ?>">
                    <div class="lp-auto" data-auto>
                        <div><span>Email</span><code data-email>kitchen.firstname@campusbite.com</code></div>
                        <div><span>Starting password</span><code data-pass>Kitchen@Firstname + 2 digits</code></div>
                    </div>
                    <div class="cb-field" data-custom-email hidden>
                        <label for="staff_email">Sign-in email</label>
                        <input type="email" id="staff_email" name="customer_email" maxlength="100" value="<?php echo htmlspecialchars($form['customer_email']); ?>" placeholder="name@example.com">
                    </div>
                    <p class="cb-field-hint">Made for you when you save. A number is added if the email is already taken, and the full password is shown once afterwards. <button type="button" class="mf-suggest" data-email-toggle>Use a different email</button></p>
                </div>
            </fieldset>
        </div>

        <div class="mf-foot">
            <p class="mf-note"><i class="bi bi-shield-lock" aria-hidden="true"></i> They'll set their own password the first time they sign in.</p>
            <div class="mf-actions">
                <button type="button" class="cb-btn cb-btn-quiet" id="mf-cancel">Cancel</button>
                <button type="submit" class="cb-btn">Add staff member</button>
            </div>
        </div>
    </form>
</section>

<section class="cb-card" aria-labelledby="team-title">
        <h2 id="team-title">Your team</h2>
        <p class="cb-card-lead">Staff see the shift dashboard, orders, customer messages and which meals are sold out. They never see sales, prices you set or payouts.</p>
        <ul class="ka-people">
            <?php foreach ($admins as $p): ?>
                <li>
                    <span class="ka-avatar is-admin" aria-hidden="true"><?php echo htmlspecialchars($initials($p['customer_name'])); ?></span>
                    <span class="ka-person"><b><?php echo htmlspecialchars($p['customer_name']); ?><?php echo (int)$p['customer_id'] === (int)get_user_id() ? ' (you)' : ''; ?></b><small><?php echo htmlspecialchars($p['customer_email']); ?></small></span>
                    <span class="cb-status cb-status-delivered">Kitchen admin</span>
                </li>
            <?php endforeach; ?>
            <?php foreach ($staff as $p): ?>
                <li>
                    <span class="ka-avatar" aria-hidden="true"><?php echo htmlspecialchars($initials($p['customer_name'])); ?></span>
                    <span class="ka-person"><b><?php echo htmlspecialchars($p['customer_name']); ?></b><small><?php echo htmlspecialchars($p['customer_email']); ?> · <?php echo htmlspecialchars($p['customer_contact']); ?></small></span>
                    <form action="<?php echo $ROOT_DIR; ?>/actions/kitchen_remove_staff_action.php" method="POST" data-confirm="Remove <?php echo htmlspecialchars($p['customer_name']); ?>'s access to the kitchen? They can still sign in to order food.">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="customer_id" value="<?php echo (int)$p['customer_id']; ?>">
                        <button type="submit" class="cb-btn cb-btn-quiet ka-btn-sm" aria-label="Remove <?php echo htmlspecialchars($p['customer_name']); ?>">Remove</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if (!$staff): ?>
            <p class="cb-table-empty">No staff yet. Add the people who'll take orders during your shifts.</p>
        <?php endif; ?>
    </section>

<script>
(function () {
    var card = document.getElementById('mf-card'), openBtn = document.getElementById('mf-open');
    openBtn.addEventListener('click', function () {
        card.hidden = false; openBtn.hidden = true; openBtn.setAttribute('aria-expanded', 'true');
        document.getElementById('staff_name').focus();
    });
    document.getElementById('mf-cancel').addEventListener('click', function () {
        card.querySelector('form').reset();
        card.hidden = true; openBtn.hidden = false; openBtn.setAttribute('aria-expanded', 'false'); openBtn.focus();
    });

})();
</script>

<script src="<?php echo $ROOT_DIR; ?>/js/login-preview.js?v=<?php echo filemtime(__DIR__ . '/../../js/login-preview.js'); ?>"></script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
