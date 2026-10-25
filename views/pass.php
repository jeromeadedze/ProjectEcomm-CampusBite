<?php
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

$custCtrl = new CustomerController();
$user = is_logged_in() ? $custCtrl->getCustomerById(get_user_id()) : null;
?>

<div class="row justify-content-center my-4">
    <div class="col-lg-8">
        <div class="cb-pass-card">
            <span class="cb-pass-badge"><i class="bi bi-stars"></i> UNLIMITED ZONE A DELIVERIES</span>
            <h1 class="fw-bold mb-2">CampusBite Pass</h1>
            <p class="fs-5 opacity-90 mb-4">
                Pay just <strong>GH₵ 35.00 / month</strong> and enjoy zero delivery fees on all orders from Hallmark, Akonnor, and Munchees.
            </p>

            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <div class="p-3 bg-white bg-opacity-10 rounded-3">
                        <h4 class="fw-bold mb-0">GH₵ 0.00</h4>
                        <small class="opacity-75">Zone A Delivery Fee</small>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="p-3 bg-white bg-opacity-10 rounded-3">
                        <h4 class="fw-bold mb-0">Priority</h4>
                        <small class="opacity-75">Courier Dispatch</small>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="p-3 bg-white bg-opacity-10 rounded-3">
                        <h4 class="fw-bold mb-0">Exam Perks</h4>
                        <small class="opacity-75">Late Night Delivery Slots</small>
                    </div>
                </div>
            </div>

            <?php if ($user && !empty($user['has_pass'])): ?>
                <div class="alert alert-success bg-white text-success border-0 rounded-4 p-3 fw-bold mb-0">
                    <i class="bi bi-check-circle-fill me-2"></i> You already have an active CampusBite Pass! Valid through <?php echo htmlspecialchars($user['pass_expiry'] ?? '30 days'); ?>.
                </div>
            <?php else: ?>
                <a href="<?php echo is_logged_in() ? $ROOT_DIR . '/actions/subscribe_pass_action.php' : $ROOT_DIR . '/views/login.php'; ?>" class="btn btn-warning btn-lg rounded-pill px-4 fw-bold text-dark shadow">
                    <i class="bi bi-lightning-charge-fill me-1"></i> Subscribe Now for GH₵ 35.00
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
