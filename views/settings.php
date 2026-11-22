<?php
// Account settings for everyone: who you are, your details and your password.
// Each card shows what's saved; Edit / Change opens its form in place.
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/CustomerController.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_login();

$user = (new CustomerController())->getCustomerById(get_user_id());

// After a failed save, refill the form with what was typed and open it again
$detailsOpen = isset($_SESSION['settings_form']) || ($_GET['edit'] ?? '') === 'details';
// Still on the starting password made for them: the password form is the only thing they can do until it's changed
$mustChange = !empty($_SESSION['must_change_password']);
$passwordOpen = $mustChange || ($_GET['edit'] ?? '') === 'password';
$form = $_SESSION['settings_form'] ?? [
    'customer_name' => $user['customer_name'],
    'customer_contact' => $user['customer_contact'],
    'campus_hostel' => $user['campus_hostel'] ?? '',
    'customer_email' => $user['customer_email'],
];
$form['customer_email'] = $form['customer_email'] ?? $user['customer_email'];
unset($_SESSION['settings_form']);

// Who this account is, for the badge under the name
$kitchenName = is_kitchen() ? ((new ProductController())->getBrandById(get_kitchen_id())['brand_name'] ?? '') : '';
if (is_admin()) {
    $roleLabel = 'CampusBite admin';
} elseif (is_kitchen_admin()) {
    $roleLabel = 'Kitchen admin · ' . $kitchenName;
} elseif (is_kitchen_staff()) {
    $roleLabel = 'Kitchen staff · ' . $kitchenName;
} else {
    $roleLabel = 'Student';
}
$nameParts = preg_split('/\s+/', trim($user['customer_name']));
$initials = strtoupper(mb_substr($nameParts[0] ?? '', 0, 1) . (count($nameParts) > 1 ? mb_substr(end($nameParts), 0, 1) : ''));
$showHostel = !is_kitchen();
$hostelLabel = DeliveryController::isDropPoint($user['campus_hostel'] ?? null)
    ? $user['campus_hostel'] . ' (' . DeliveryController::AREAS[DeliveryController::DROP_POINTS[$user['campus_hostel']]] . ')' : '';
?>

<section class="st-profile">
    <span class="st-avatar" aria-hidden="true"><?php echo htmlspecialchars($initials); ?></span>
    <div class="st-who">
        <h2><?php echo htmlspecialchars($user['customer_name']); ?></h2>
        <p><?php echo htmlspecialchars($user['customer_email']); ?></p>
        <span class="st-role"><?php echo htmlspecialchars($roleLabel); ?></span>
    </div>
    <a class="cb-btn cb-btn-quiet st-signout" href="<?php echo $ROOT_DIR; ?>/logout.php"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Sign out</a>
</section>

<?php if ($mustChange): ?>
    <p class="ka-alert"><i class="bi bi-shield-lock" aria-hidden="true"></i> Welcome! Choose your own password to finish setting up your account. The one you were given only works this once.</p>
<?php endif; ?>

<div class="ki-grid">
    <section class="cb-card mf-card" aria-labelledby="details-title" data-edit-card>
        <div class="ki-head">
            <div>
                <h2 id="details-title">Your details</h2>
                <p class="mf-lead"><?php echo is_kitchen() ? 'CampusBite uses your phone number to reach you about the kitchen.' : (is_admin() ? 'How kitchens and students see you when you reply to them.' : 'Kitchens use your phone number and drop point to find you when they deliver.'); ?></p>
            </div>
            <button type="button" class="cb-btn cb-btn-quiet ka-btn-sm" data-edit-open <?php echo $detailsOpen ? 'hidden' : ''; ?>><i class="bi bi-pencil" aria-hidden="true"></i> Edit</button>
        </div>

        <dl class="ka-facts" data-edit-view <?php echo $detailsOpen ? 'hidden' : ''; ?>>
            <div><dt>Full name</dt><dd><?php echo htmlspecialchars($user['customer_name']); ?></dd></div>
            <div><dt>Phone</dt><dd><?php echo htmlspecialchars($user['customer_contact']); ?></dd></div>
            <?php if ($showHostel): ?>
                <div><dt>Default drop point</dt><dd><?php echo $hostelLabel !== '' ? htmlspecialchars($hostelLabel) : '<span class="ki-missing">Not set</span>'; ?></dd></div>
            <?php endif; ?>
            <div><dt>Email</dt><dd><?php echo htmlspecialchars($user['customer_email']); ?></dd></div>
        </dl>

        <form class="mf-form mf-form-single" action="<?php echo $ROOT_DIR; ?>/actions/update_profile_action.php" method="POST" data-edit-form <?php echo $detailsOpen ? '' : 'hidden'; ?>>
            <?php echo csrf_field(); ?>
            <div class="mf-main">
                <div class="mf-section">
                    <div class="cb-field mf-wide">
                        <label for="customer_name">Full name</label>
                        <input type="text" id="customer_name" name="customer_name" maxlength="100" autocomplete="name" value="<?php echo htmlspecialchars($form['customer_name']); ?>" required>
                    </div>
                    <div class="cb-field <?php echo $showHostel ? '' : 'mf-wide'; ?>">
                        <label for="customer_contact">Phone <?php echo $showHostel ? '(MoMo / WhatsApp)' : ''; ?></label>
                        <input type="tel" id="customer_contact" name="customer_contact" maxlength="15" autocomplete="tel" inputmode="tel" value="<?php echo htmlspecialchars($form['customer_contact']); ?>" required>
                    </div>
                    <?php if ($showHostel): ?>
                        <div class="cb-field">
                            <label for="campus_hostel">Default drop point</label>
                            <select id="campus_hostel" name="campus_hostel" required>
                                <option value="">Choose your hall or hostel</option>
                                <?php foreach (DeliveryController::grouped() as $area => $points): ?>
                                    <optgroup label="<?php echo htmlspecialchars(DeliveryController::AREAS[$area]); ?>">
                                        <?php foreach ($points as $point): ?>
                                            <option value="<?php echo htmlspecialchars($point); ?>" <?php echo $form['campus_hostel'] === $point ? 'selected' : ''; ?>><?php echo htmlspecialchars($point); ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <p class="cb-field-hint">Used to show your delivery fee and filled in at checkout.</p>
                        </div>
                    <?php endif; ?>
                    <div class="cb-field mf-wide">
                        <label for="customer_email">Sign-in email</label>
                        <input type="email" id="customer_email" name="customer_email" maxlength="100" autocomplete="email" value="<?php echo htmlspecialchars($form['customer_email']); ?>" data-current="<?php echo htmlspecialchars($user['customer_email']); ?>" required>
                        <?php if (is_kitchen()): ?><p class="cb-field-hint">Use your own email so new-order alerts and password resets reach you.</p><?php endif; ?>
                    </div>
                    <div class="cb-field mf-wide" id="email-pass-field" <?php echo strcasecmp($form['customer_email'], $user['customer_email']) === 0 ? 'hidden' : ''; ?>>
                        <label for="email_pass">Your current password</label>
                        <input type="password" id="email_pass" name="email_pass" autocomplete="current-password" aria-describedby="email_pass_hint">
                        <p class="cb-field-hint" id="email_pass_hint">Needed to change your sign-in email.</p>
                    </div>
                </div>
            </div>
            <div class="mf-foot">
                <p class="mf-note"><i class="bi bi-envelope" aria-hidden="true"></i> You'll sign in with your new email straight away.</p>
                <div class="mf-actions">
                    <button type="button" class="cb-btn cb-btn-quiet" data-edit-cancel>Cancel</button>
                    <button type="submit" class="cb-btn">Save details</button>
                </div>
            </div>
        </form>
    </section>

    <section class="cb-card mf-card" aria-labelledby="password-title" data-edit-card>
        <div class="ki-head">
            <div>
                <h2 id="password-title">Password</h2>
                <p class="mf-lead">Use one you don't use anywhere else.</p>
            </div>
            <button type="button" class="cb-btn cb-btn-quiet ka-btn-sm" data-edit-open <?php echo $passwordOpen ? 'hidden' : ''; ?>><i class="bi bi-key" aria-hidden="true"></i> Change password</button>
        </div>

        <div data-edit-view <?php echo $passwordOpen ? 'hidden' : ''; ?>>
            <div class="ki-account">
                <span class="ki-account-icon" aria-hidden="true"><i class="bi bi-shield-lock"></i></span>
                <span><b>••••••••••</b><small>Changing it signs out any other device using your account</small></span>
            </div>
        </div>

        <form class="mf-form mf-form-single" action="<?php echo $ROOT_DIR; ?>/actions/change_password_action.php" method="POST" data-edit-form <?php echo $passwordOpen ? '' : 'hidden'; ?>>
            <?php echo csrf_field(); ?>
            <div class="mf-main">
                <div class="mf-section">
                    <div class="cb-field mf-wide">
                        <label for="current_pass"><?php echo $mustChange ? 'Password you were given' : 'Current password'; ?></label>
                        <div class="mf-affix mf-affix-end">
                            <input type="password" id="current_pass" name="current_pass" autocomplete="current-password" required>
                            <button type="button" class="mf-reveal" data-reveal="current_pass" aria-pressed="false" aria-label="Show password"><i class="bi bi-eye" aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <div class="cb-field">
                        <label for="new_pass">New password</label>
                        <div class="mf-affix mf-affix-end">
                            <input type="password" id="new_pass" name="new_pass" minlength="8" autocomplete="new-password" aria-describedby="new_pass_hint" required>
                            <button type="button" class="mf-reveal" data-reveal="new_pass" aria-pressed="false" aria-label="Show password"><i class="bi bi-eye" aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <div class="cb-field">
                        <label for="confirm_pass">Repeat new password</label>
                        <div class="mf-affix mf-affix-end">
                            <input type="password" id="confirm_pass" name="confirm_pass" minlength="8" autocomplete="new-password" required>
                            <button type="button" class="mf-reveal" data-reveal="confirm_pass" aria-pressed="false" aria-label="Show password"><i class="bi bi-eye" aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <p class="cb-field-hint mf-wide" id="new_pass_hint">At least 8 characters, with an uppercase letter, a lowercase letter, a number and a symbol.</p>
                </div>
            </div>
            <div class="mf-foot">
                <span></span>
                <div class="mf-actions">
                    <?php if (!$mustChange): ?><button type="button" class="cb-btn cb-btn-quiet" data-edit-cancel>Cancel</button><?php endif; ?>
                    <button type="submit" class="cb-btn"><?php echo $mustChange ? 'Set my password' : 'Change password'; ?></button>
                </div>
            </div>
        </form>
    </section>
</div>

<script>
// Each card: the button shows its form in place of the summary; Cancel puts the summary back
document.querySelectorAll('[data-edit-card]').forEach(function (card) {
    var openBtn = card.querySelector('[data-edit-open]'), view = card.querySelector('[data-edit-view]'),
        form = card.querySelector('[data-edit-form]'), cancel = card.querySelector('[data-edit-cancel]');
    openBtn.addEventListener('click', function () {
        form.hidden = false; view.hidden = true; openBtn.hidden = true;
        form.querySelector('input:not([type=hidden])').focus();
    });
    if (cancel) cancel.addEventListener('click', function () {
        form.reset(); form.hidden = true; view.hidden = false; openBtn.hidden = false; openBtn.focus();
    });
});
// Changing the sign-in email asks for the current password
(function () {
    var email = document.getElementById('customer_email'), field = document.getElementById('email-pass-field');
    if (!email) return;
    email.addEventListener('input', function () {
        var changed = email.value.trim().toLowerCase() !== email.dataset.current.toLowerCase();
        field.hidden = !changed;
        document.getElementById('email_pass').required = changed;
    });
})();
// Show / hide a password field
document.querySelectorAll('[data-reveal]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = document.getElementById(btn.dataset.reveal), on = input.type === 'password';
        input.type = on ? 'text' : 'password';
        btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        btn.setAttribute('aria-label', on ? 'Hide password' : 'Show password');
        btn.innerHTML = '<i class="bi ' + (on ? 'bi-eye-slash' : 'bi-eye') + '" aria-hidden="true"></i>';
    });
});
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
