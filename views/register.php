<?php
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

if (is_logged_in()) {
    header("Location: " . $ROOT_DIR . "/index.php");
    exit;
}

// Values from a rejected submission, so nobody has to retype them
$form = $_SESSION['register_form'] ?? [];
unset($_SESSION['register_form']);

$formValue = function ($field) use ($form) {
    return htmlspecialchars($form[$field] ?? '');
};
$selectedHostel = $form['campus_hostel'] ?? '';
?>

<section class="cb-auth cb-auth-wide">
    <h2>Create your account</h2>
    <p class="cb-auth-lead">Order from campus and Berekuso kitchens and have it brought to your door.</p>

    <form id="registerForm" action="<?php echo $ROOT_DIR; ?>/actions/register_action.php" method="POST" novalidate>
        <?php echo csrf_field(); ?>
        <div class="cb-auth-grid">
            <div class="cb-field cb-field-full">
                <label for="customer_name">Full name</label>
                <input type="text" id="customer_name" name="customer_name" value="<?php echo $formValue('customer_name'); ?>" autocomplete="name" aria-describedby="name_error" required>
                <p class="cb-field-error" id="name_error"></p>
            </div>
            <div class="cb-field cb-field-full">
                <label for="customer_email">Email address</label>
                <input type="email" id="customer_email" name="customer_email" value="<?php echo $formValue('customer_email'); ?>" placeholder="name@ashesi.edu.gh" autocomplete="email" aria-describedby="email_error" required>
                <p class="cb-field-error" id="email_error"></p>
            </div>
            <div class="cb-field cb-field-full">
                <label for="customer_pass">Password</label>
                <input type="password" id="customer_pass" name="customer_pass" autocomplete="new-password" aria-describedby="pass_hint pass_error" required>
                <p class="cb-field-hint" id="pass_hint">At least 8 characters with an uppercase letter, a lowercase letter, a number and a symbol.</p>
                <p class="cb-field-error" id="pass_error"></p>
            </div>
            <div class="cb-field">
                <label for="customer_contact">Mobile money number</label>
                <input type="tel" id="customer_contact" name="customer_contact" value="<?php echo $formValue('customer_contact'); ?>" placeholder="0241234567" autocomplete="tel" aria-describedby="contact_error" required>
                <p class="cb-field-error" id="contact_error"></p>
            </div>
            <div class="cb-field">
                <label for="campus_hostel">Delivery spot</label>
                <select id="campus_hostel" name="campus_hostel">
                    <?php foreach (CustomerController::HOSTELS as $hostelValue => $hostelLabel): ?>
                        <option value="<?php echo htmlspecialchars($hostelValue); ?>" <?php echo $selectedHostel === $hostelValue ? 'selected' : ''; ?>><?php echo htmlspecialchars($hostelLabel); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="cb-checkout">Create account</button>
    </form>

    <p class="cb-auth-switch">Already have an account? <a href="<?php echo $ROOT_DIR; ?>/views/login.php">Sign in</a></p>
</section>

<script src="<?php echo $ROOT_DIR; ?>/js/validate.js?v=<?php echo filemtime(__DIR__ . '/../js/validate.js'); ?>"></script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
