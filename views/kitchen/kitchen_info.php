<?php
// Kitchen portal: where the kitchen is and whether shoppers can order from it
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../controllers/OrderController.php';
require_once __DIR__ . '/../../core/paystack.php';

require_kitchen();

$controller = new ProductController();
$kitchen = $controller->getBrandById(get_kitchen_id());
$adminPaused = (int)($kitchen['admin_paused'] ?? 0) === 1;

// Payout account: where Paystack pays the kitchen's share of each order
$payoutLabel = ProductController::payoutLabel($kitchen);
$refunds = (new OrderController())->getKitchenRefundBalance((int)$kitchen['brand_id']);
$payout = $_SESSION['payout_form'] ?? [
    'payout_bank_code' => $kitchen['payout_bank_code'] ?? '',
    'payout_account_number' => $kitchen['payout_account_number'] ?? '',
    'payout_account_name' => $kitchen['payout_account_name'] ?? '',
];
unset($_SESSION['payout_form']);
$momoOptions = paystack_configured() ? paystack_payout_options('mobile_money') : [];
$bankOptions = paystack_configured() ? paystack_payout_options('bank') : [];
$commissionPct = round($kitchen['commission_rate'] * 100);

$pageHeading = $kitchen['brand_name'];
require_once __DIR__ . '/../layout/header.php';
?>

<div class="cb-settings">
    <section class="cb-card">
        <h2>Kitchen details</h2>
        <p class="cb-card-lead">Shoppers see your location on every meal. Pause the kitchen to hide it while you're closed.</p>
        <form class="cb-admin-form" action="<?php echo $ROOT_DIR; ?>/actions/kitchen_update_info_action.php" method="POST">
            <?php echo csrf_field(); ?>
            <div class="cb-field cb-field-full">
                <label for="location_desc">Location</label>
                <input type="text" id="location_desc" name="location_desc" maxlength="255" value="<?php echo htmlspecialchars($kitchen['location_desc'] ?? ''); ?>" placeholder="Student Center Pavilion">
            </div>
            <div class="cb-field cb-field-full">
                <label for="brand_phone">Phone (orders & customer support)</label>
                <input type="tel" id="brand_phone" name="brand_phone" maxlength="20" value="<?php echo htmlspecialchars($kitchen['brand_phone'] ?? ''); ?>" placeholder="0241234567">
                <p class="cb-field-hint">Shoppers can call or WhatsApp this number when they have questions about their food or delivery.</p>
            </div>
            <div class="cb-field cb-field-full">
                <label for="is_active">Taking orders</label>
                <?php if ($adminPaused): ?>
                    <input type="hidden" name="is_active" value="0">
                    <select id="is_active" disabled aria-describedby="admin_paused_hint">
                        <option selected>Paused by CampusBite</option>
                    </select>
                    <p class="cb-field-hint" id="admin_paused_hint">CampusBite paused your kitchen, so shoppers can't see it. Contact the CampusBite admin to reopen.</p>
                <?php else: ?>
                    <select id="is_active" name="is_active">
                        <option value="1" <?php echo (int)$kitchen['is_active'] === 1 ? 'selected' : ''; ?>>Open: shoppers can order</option>
                        <option value="0" <?php echo (int)$kitchen['is_active'] === 0 ? 'selected' : ''; ?>>Paused: hidden from shoppers</option>
                    </select>
                <?php endif; ?>
            </div>
            <div class="cb-admin-actions"><button type="submit" class="cb-btn">Save details</button></div>
        </form>
    </section>

    <section class="cb-card" id="payout" aria-labelledby="payout-title">
        <h2 id="payout-title">Payout account</h2>
        <?php if ($payoutLabel): ?>
            <p class="cb-card-lead">Paystack pays your share of every order straight to <strong><?php echo htmlspecialchars($payoutLabel); ?></strong><?php if (!empty($kitchen['payout_account_name'])): ?> (<?php echo htmlspecialchars($kitchen['payout_account_name']); ?>)<?php endif; ?>: the meals minus CampusBite's <?php echo $commissionPct; ?>% commission, plus the delivery fee. Paystack settles it on its usual schedule, normally the next working day.</p>
            <p class="cb-field-hint">When an order is cancelled, Paystack refunds the customer from CampusBite's balance, so your share of that order is taken back from your next orders.<?php if ($refunds['owed'] > 0): ?> Still to take back: <strong>GH₵ <?php echo number_format($refunds['owed'], 2); ?></strong>.<?php endif; ?></p>
        <?php else: ?>
            <p class="cb-warn-note"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> Students can't pay for your meals until you add a payout account. Paystack sends your share of each order there.</p>
            <p class="cb-card-lead">Add the Mobile Money number or bank account your kitchen gets paid into. Your share is the meals minus CampusBite's <?php echo $commissionPct; ?>% commission, plus the delivery fee.</p>
        <?php endif; ?>

        <?php if (!$momoOptions && !$bankOptions): ?>
            <p class="cb-field-hint">Paystack can't be reached right now, so the account can't be changed. Try again in a few minutes.</p>
        <?php else: ?>
            <form class="cb-admin-form" action="<?php echo $ROOT_DIR; ?>/actions/kitchen_save_payout_action.php" method="POST" autocomplete="off">
                <?php echo csrf_field(); ?>
                <div class="cb-field cb-field-full">
                    <label for="payout_bank_code">Mobile Money network or bank</label>
                    <select id="payout_bank_code" name="payout_bank_code" required>
                        <option value="" disabled <?php echo $payout['payout_bank_code'] === '' ? 'selected' : ''; ?>>Choose one</option>
                        <?php if ($momoOptions): ?>
                            <optgroup label="Mobile Money">
                                <?php foreach ($momoOptions as $code => $name): ?>
                                    <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $payout['payout_bank_code'] === (string)$code ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                        <?php if ($bankOptions): ?>
                            <optgroup label="Banks">
                                <?php foreach ($bankOptions as $code => $name): ?>
                                    <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $payout['payout_bank_code'] === (string)$code ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="cb-field">
                    <label for="payout_account_number">Number</label>
                    <input type="text" id="payout_account_number" name="payout_account_number" inputmode="numeric" maxlength="20" value="<?php echo htmlspecialchars($payout['payout_account_number']); ?>" placeholder="0241234567" aria-describedby="payout_number_hint" required>
                    <p class="cb-field-hint" id="payout_number_hint">Mobile Money: 10 digits starting with 0. Bank: the account number, no spaces.</p>
                </div>
                <div class="cb-field">
                    <label for="payout_account_name">Name on the account</label>
                    <input type="text" id="payout_account_name" name="payout_account_name" maxlength="100" value="<?php echo htmlspecialchars($payout['payout_account_name']); ?>" placeholder="Ama Mensah" required>
                </div>
                <div class="cb-admin-actions"><button type="submit" class="cb-btn"><?php echo $payoutLabel ? 'Update payout account' : 'Save payout account'; ?></button></div>
            </form>
            <?php if ($payoutLabel): ?>
                <p class="cb-field-hint">A change applies to new orders. Orders already paid still go to the old account.</p>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <section class="cb-card">
        <h2>Set by CampusBite</h2>
        <p class="cb-card-lead">Contact the CampusBite admin to change these.</p>
        <dl class="cb-facts">
            <div><dt>Kitchen name</dt><dd><?php echo htmlspecialchars($kitchen['brand_name']); ?></dd></div>
            <div><dt>Delivery zone</dt><dd><?php echo htmlspecialchars(ProductController::ZONES[$kitchen['zone']] ?? $kitchen['zone']); ?></dd></div>
            <div><dt>Commission</dt><dd><?php echo round($kitchen['commission_rate'] * 100); ?>% of meal sales</dd></div>
        </dl>
    </section>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
