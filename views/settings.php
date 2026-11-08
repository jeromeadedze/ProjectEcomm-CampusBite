<?php
// Account settings: name, phone and default drop point, plus a password change
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

require_login();

$user = (new CustomerController())->getCustomerById(get_user_id());

// After a failed save, refill the form with what was typed
$form = $_SESSION['settings_form'] ?? [
    'customer_name' => $user['customer_name'],
    'customer_contact' => $user['customer_contact'],
    'campus_hostel' => $user['campus_hostel'] ?? '',
];
unset($_SESSION['settings_form']);
?>

<p class="cb-sub">Signed in as <?php echo htmlspecialchars($user['customer_email']); ?></p>

<div class="cb-settings">
    <section class="cb-card">
        <h2>Your details</h2>
        <p class="cb-card-lead">Couriers use your phone number and drop point to find you.</p>
        <form class="cb-admin-form" action="<?php echo $ROOT_DIR; ?>/actions/update_profile_action.php" method="POST">
            <?php echo csrf_field(); ?>
            <div class="cb-field cb-field-full">
                <label for="customer_name">Full name</label>
                <input type="text" id="customer_name" name="customer_name" maxlength="100" autocomplete="name" value="<?php echo htmlspecialchars($form['customer_name']); ?>" required>
            </div>
            <div class="cb-field">
                <label for="customer_contact">Phone (MoMo / WhatsApp)</label>
                <input type="tel" id="customer_contact" name="customer_contact" maxlength="15" autocomplete="tel" value="<?php echo htmlspecialchars($form['customer_contact']); ?>" required>
            </div>
            <div class="cb-field">
                <label for="campus_hostel">Default drop point</label>
                <select id="campus_hostel" name="campus_hostel" required>
                    <option value="">Choose a drop point</option>
                    <?php foreach (CustomerController::HOSTELS as $hostelValue => $hostelLabel): ?>
                        <option value="<?php echo htmlspecialchars($hostelValue); ?>" <?php echo $form['campus_hostel'] === $hostelValue ? 'selected' : ''; ?>><?php echo htmlspecialchars($hostelLabel); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="cb-admin-actions">
                <button type="submit" class="cb-btn">Save details</button>
            </div>
        </form>
    </section>

    <section class="cb-card">
        <h2>Change password</h2>
        <p class="cb-card-lead">At least 8 characters, with an uppercase letter, a lowercase letter, a number and a symbol.</p>
        <form class="cb-admin-form" action="<?php echo $ROOT_DIR; ?>/actions/change_password_action.php" method="POST">
            <?php echo csrf_field(); ?>
            <div class="cb-field cb-field-full">
                <label for="current_pass">Current password</label>
                <input type="password" id="current_pass" name="current_pass" autocomplete="current-password" required>
            </div>
            <div class="cb-field">
                <label for="new_pass">New password</label>
                <input type="password" id="new_pass" name="new_pass" minlength="8" autocomplete="new-password" required>
            </div>
            <div class="cb-field">
                <label for="confirm_pass">Repeat new password</label>
                <input type="password" id="confirm_pass" name="confirm_pass" minlength="8" autocomplete="new-password" required>
            </div>
            <div class="cb-admin-actions">
                <button type="submit" class="cb-btn">Change password</button>
            </div>
        </form>
    </section>

    <section class="cb-card cb-settings-signout">
        <div>
            <h2>Sign out</h2>
            <p class="cb-card-lead">Your bill stays saved to your account for next time.</p>
        </div>
        <a class="cb-btn cb-btn-quiet" href="<?php echo $ROOT_DIR; ?>/logout.php">Sign out</a>
    </section>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
