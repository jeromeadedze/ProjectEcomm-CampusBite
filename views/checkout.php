<?php
$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/CartController.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

require_login();

$cartCtrl = new CartController();
$custCtrl = new CustomerController();

$cartIp = get_ip();
$userId = get_user_id();
$cartItems = $cartCtrl->getCartItems($cartIp, $userId);

if (empty($cartItems)) {
    header("Location: " . $ROOT_DIR . "/views/cart.php");
    exit;
}

$user = $custCtrl->getCustomerById($userId);
$subtotal = $cartCtrl->getCartTotal($cartIp, $userId);
$deliveryFee = $cartCtrl->calculateDeliveryFee($cartItems, !empty($user['has_pass']));
$grandTotal = $subtotal + $deliveryFee;

// Determine predominant delivery zone
$zone = 'Zone A';
foreach ($cartItems as $item) {
    if (isset($item['zone']) && $item['zone'] === 'Zone B') {
        $zone = 'Zone B';
        break;
    }
}
?>

<div class="row g-4 my-3 justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h3 class="fw-bold mb-1"><i class="bi bi-shield-check text-warning me-2"></i>Campus Delivery & Checkout</h3>
            <p class="text-muted small mb-4">Complete your drop location details and select your Mobile Money or Card payment option.</p>

            <form action="<?php echo $ROOT_DIR; ?>/actions/checkout_process_action.php" method="POST">
                <input type="hidden" name="delivery_zone" value="<?php echo $zone; ?>">
                <input type="hidden" name="delivery_fee" value="<?php echo $deliveryFee; ?>">
                <input type="hidden" name="total_amt" value="<?php echo $grandTotal; ?>">

                <h5 class="fw-bold mb-3"><i class="bi bi-geo-alt-fill text-danger me-2"></i>1. Delivery Drop Point (Ashesi Campus)</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted">Customer Name</label>
                        <input type="text" class="form-control rounded-pill bg-light" value="<?php echo htmlspecialchars($user['customer_name'] ?? ''); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted">Contact Phone (MoMo / WhatsApp)</label>
                        <input type="tel" name="contact" class="form-control rounded-pill" value="<?php echo htmlspecialchars($user['customer_contact'] ?? ''); ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold text-muted">Campus Drop Point / Room / Building</label>
                        <select name="drop_location_preset" class="form-select rounded-pill mb-2" id="presetSelect" onchange="document.getElementById('customLocation').value = this.value;">
                            <option value="">Select popular Ashesi drop point...</option>
                            <option value="Walter Sisulu Hall (Hostel 1)">Walter Sisulu Hall (Hostel 1)</option>
                            <option value="Wangari Maathai Hall (Hostel 2)">Wangari Maathai Hall (Hostel 2)</option>
                            <option value="Charlotte Court (Off-Campus)">Charlotte Court (Off-Campus)</option>
                            <option value="Masere Hostel (Berekuso)">Masere Hostel (Berekuso)</option>
                            <option value="FabLab / Engineering Building Courtyard">FabLab / Engineering Building Courtyard</option>
                            <option value="Library Courtyard / Upper Quad">Library Courtyard / Upper Quad</option>
                            <option value="Faculty Office Lounge">Faculty Office Lounge</option>
                            <option value="Student Center Hive">Student Center Hive</option>
                        </select>
                        <input type="text" name="drop_location" id="customLocation" class="form-control rounded-pill" placeholder="Or enter exact room e.g. Sisulu Room 208, or Lab 2" value="<?php echo htmlspecialchars($user['campus_hostel'] ?? ''); ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold text-muted">Delivery Instructions for Student Courier</label>
                        <input type="text" name="notes" class="form-control rounded-pill" placeholder="e.g. Call when outside the courtyard / Leave with security desk">
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="fw-bold mb-3"><i class="bi bi-wallet2 text-primary me-2"></i>2. Payment Method</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="card p-3 border rounded-4 text-center cursor-pointer h-100" style="cursor: pointer;">
                            <input type="radio" name="payment_mode" value="Paystack - MTN MoMo" checked class="form-check-input mb-2 mx-auto">
                            <span class="fw-bold d-block"><i class="bi bi-phone-fill text-warning me-1"></i> MTN MoMo</span>
                            <small class="text-muted" style="font-size: 0.78rem;">Instant prompt on your phone</small>
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="card p-3 border rounded-4 text-center cursor-pointer h-100" style="cursor: pointer;">
                            <input type="radio" name="payment_mode" value="Paystack - Telecel Cash" class="form-check-input mb-2 mx-auto">
                            <span class="fw-bold d-block"><i class="bi bi-phone text-danger me-1"></i> Telecel Cash</span>
                            <small class="text-muted" style="font-size: 0.78rem;">USSD / Push token</small>
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="card p-3 border rounded-4 text-center cursor-pointer h-100" style="cursor: pointer;">
                            <input type="radio" name="payment_mode" value="Paystack - Bank Card" class="form-check-input mb-2 mx-auto">
                            <span class="fw-bold d-block"><i class="bi bi-credit-card-2-front text-primary me-1"></i> Visa / Mastercard</span>
                            <small class="text-muted" style="font-size: 0.78rem;">Online card payment</small>
                        </label>
                    </div>
                </div>

                <div class="bg-light p-3 rounded-4 mb-4">
                    <div class="d-flex justify-content-between mb-1 small">
                        <span>Items Subtotal:</span>
                        <span class="fw-semibold">GH₵ <?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 small">
                        <span><?php echo $zone; ?> Delivery:</span>
                        <span class="fw-semibold text-success">GH₵ <?php echo number_format($deliveryFee, 2); ?></span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between fw-bold fs-5">
                        <span>Total Payable:</span>
                        <span class="text-danger">GH₵ <?php echo number_format($grandTotal, 2); ?></span>
                    </div>
                </div>

                <button type="submit" class="cb-btn-primary btn btn-lg w-100 py-3">
                    <i class="bi bi-lock-fill me-2"></i> Pay GH₵ <?php echo number_format($grandTotal, 2); ?> & Place Order
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
