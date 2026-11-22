<?php
// Kitchen admin: the kitchen's details, its payout account and what CampusBite sets.
// Each card shows what's saved; Edit opens its form in place.
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../controllers/OrderController.php';
require_once __DIR__ . '/../../core/paystack.php';

require_kitchen_admin();

$controller = new ProductController();
$kitchen = $controller->getBrandById(get_kitchen_id());
$adminPaused = (int)($kitchen['admin_paused'] ?? 0) === 1;

// Payout account: where Paystack pays the kitchen's share of each order
$payoutLabel = ProductController::payoutLabel($kitchen);
$refunds = (new OrderController())->getKitchenRefundBalance((int)$kitchen['brand_id']);
$payoutFailed = isset($_SESSION['payout_form']);
$payout = $_SESSION['payout_form'] ?? [
    'payout_bank_code' => $kitchen['payout_bank_code'] ?? '',
    'payout_account_number' => $kitchen['payout_account_number'] ?? '',
    'payout_account_name' => $kitchen['payout_account_name'] ?? '',
];
unset($_SESSION['payout_form']);
$momoOptions = paystack_configured() ? paystack_payout_options('mobile_money') : [];
$bankOptions = paystack_configured() ? paystack_payout_options('bank') : [];
$commissionPct = round($kitchen['commission_rate'] * 100);

// The payout form starts open when there's no account yet (students can't pay without one) or a save just failed
$payoutOpen = !$payoutLabel || $payoutFailed;
$detailsOpen = isset($_GET['edit']) && $_GET['edit'] === 'details';

$pageHeading = 'Kitchen info';
require_once __DIR__ . '/../layout/header.php';
$orDash = function ($v) { return trim((string)$v) !== '' ? htmlspecialchars($v) : '<span class="ki-missing">Not set</span>'; };
?>

<p class="cb-sub ka-sub"><?php echo htmlspecialchars($kitchen['brand_name']); ?> ·
    <span class="cb-dot-status <?php echo $kitchen['is_active'] ? '' : 'off'; ?>"><?php echo $kitchen['is_active'] ? 'Open to students' : ($adminPaused ? 'Paused by CampusBite' : 'Paused'); ?></span>
</p>

<div class="ki-grid">
    <div class="ki-col">
        <section class="cb-card mf-card" aria-labelledby="details-title" data-edit-card>
            <div class="ki-head">
                <div>
                    <h2 id="details-title">Kitchen details</h2>
                    <p class="mf-lead">Students see your location on every meal and can call this number about their order.</p>
                </div>
                <button type="button" class="cb-btn cb-btn-quiet ka-btn-sm" data-edit-open <?php echo $detailsOpen ? 'hidden' : ''; ?>><i class="bi bi-pencil" aria-hidden="true"></i> Edit</button>
            </div>

            <dl class="ka-facts" data-edit-view <?php echo $detailsOpen ? 'hidden' : ''; ?>>
                <div><dt>Location</dt><dd><?php echo $orDash($kitchen['location_desc'] ?? ''); ?></dd></div>
                <div><dt>Phone</dt><dd><?php echo $orDash($kitchen['brand_phone'] ?? ''); ?></dd></div>
                <div><dt>Taking orders</dt><dd><?php echo $adminPaused ? 'Paused by CampusBite' : ($kitchen['is_active'] ? 'Yes, open' : 'No, paused'); ?></dd></div>
            </dl>

            <form class="mf-form mf-form-single" action="<?php echo $ROOT_DIR; ?>/actions/kitchen_update_info_action.php" method="POST" data-edit-form <?php echo $detailsOpen ? '' : 'hidden'; ?>>
                <?php echo csrf_field(); ?>
                <div class="mf-main">
                    <div class="mf-section">
                        <div class="cb-field mf-wide">
                            <label for="location_desc">Location</label>
                            <input type="text" id="location_desc" name="location_desc" maxlength="255" value="<?php echo htmlspecialchars($kitchen['location_desc'] ?? ''); ?>" placeholder="Student Center Pavilion">
                        </div>
                        <div class="cb-field mf-wide">
                            <label for="brand_phone">Phone for orders and questions</label>
                            <input type="tel" id="brand_phone" name="brand_phone" maxlength="20" inputmode="tel" value="<?php echo htmlspecialchars($kitchen['brand_phone'] ?? ''); ?>" placeholder="0241234567" aria-describedby="phone_hint">
                            <p class="cb-field-hint" id="phone_hint">Students can call or WhatsApp this number.</p>
                        </div>
                    </div>
                </div>
                <div class="mf-foot">
                    <?php if ($adminPaused): ?>
                        <input type="hidden" name="is_active" value="0">
                        <p class="mf-note"><i class="bi bi-pause-circle" aria-hidden="true"></i> CampusBite paused your kitchen. Contact the CampusBite admin to reopen.</p>
                    <?php else: ?>
                        <label class="mf-avail">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" <?php echo (int)$kitchen['is_active'] === 1 ? 'checked' : ''; ?>>
                            <span class="mf-switch" aria-hidden="true"><span></span></span>
                            <span><b>Taking orders</b><small>Switch off to hide your kitchen while you're closed</small></span>
                        </label>
                    <?php endif; ?>
                    <div class="mf-actions">
                        <button type="button" class="cb-btn cb-btn-quiet" data-edit-cancel>Cancel</button>
                        <button type="submit" class="cb-btn">Save details</button>
                    </div>
                </div>
            </form>
        </section>

        <section class="cb-card mf-card" aria-labelledby="set-title">
            <div class="ki-head">
                <div>
                    <h2 id="set-title">Set by CampusBite</h2>
                    <p class="mf-lead">Contact the CampusBite admin to change these.</p>
                </div>
            </div>
            <dl class="ka-facts">
                <div><dt>Kitchen name</dt><dd><?php echo htmlspecialchars($kitchen['brand_name']); ?></dd></div>
                <div><dt>Delivery zone</dt><dd><?php echo htmlspecialchars(ProductController::ZONES[$kitchen['zone']] ?? $kitchen['zone']); ?></dd></div>
                <div><dt>Commission</dt><dd><?php echo $commissionPct; ?>% of meal sales</dd></div>
            </dl>
        </section>
    </div>

    <section class="cb-card mf-card" id="payout" aria-labelledby="payout-title" data-edit-card>
        <div class="ki-head">
            <div>
                <h2 id="payout-title">Payout account</h2>
                <p class="mf-lead">Paystack pays your share of every order here, usually the next working day.</p>
            </div>
            <?php if ($payoutLabel && ($momoOptions || $bankOptions)): ?>
                <button type="button" class="cb-btn cb-btn-quiet ka-btn-sm" data-edit-open <?php echo $payoutOpen ? 'hidden' : ''; ?>><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Change account</button>
            <?php endif; ?>
        </div>

        <?php if ($payoutLabel): ?>
            <div data-edit-view <?php echo $payoutOpen ? 'hidden' : ''; ?>>
                <div class="ki-account">
                    <span class="ki-account-icon" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
                    <span><b><?php echo htmlspecialchars($payoutLabel); ?></b><?php if (!empty($kitchen['payout_account_name'])): ?><small><?php echo htmlspecialchars($kitchen['payout_account_name']); ?></small><?php endif; ?></span>
                    <span class="cb-status cb-status-delivered">Active</span>
                </div>
                <dl class="ka-facts">
                    <div><dt>Each order you get</dt><dd>Meals − <?php echo $commissionPct; ?>% + delivery fee</dd></div>
                    <div><dt>Refunds still to take back</dt><dd class="<?php echo $refunds['owed'] > 0 ? 'ka-bad' : ''; ?>">GH₵ <?php echo number_format(max(0, $refunds['owed']), 2); ?></dd></div>
                </dl>
                <p class="ka-fine">If an order is cancelled, the customer is refunded in full and your share of it comes out of your next orders.</p>
            </div>
        <?php else: ?>
            <p class="ka-alert ki-alert"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> Students can't pay for your meals until you add a payout account.</p>
        <?php endif; ?>

        <?php if (!$momoOptions && !$bankOptions): ?>
            <p class="ka-empty">Paystack can't be reached right now, so the account can't be <?php echo $payoutLabel ? 'changed' : 'added'; ?>. Check that the Paystack keys are set up, or try again in a few minutes.</p>
        <?php else: ?>
            <form class="mf-form mf-form-single" action="<?php echo $ROOT_DIR; ?>/actions/kitchen_save_payout_action.php" method="POST" autocomplete="off" data-edit-form <?php echo $payoutOpen ? '' : 'hidden'; ?>>
                <?php echo csrf_field(); ?>
                <div class="mf-main">
                    <div class="mf-section">
                        <div class="cb-field mf-wide">
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
                            <p class="cb-field-hint" id="payout_number_hint">MoMo: 10 digits starting with 0. Bank: account number, no spaces.</p>
                        </div>
                        <div class="cb-field">
                            <label for="payout_account_name">Name on the account</label>
                            <input type="text" id="payout_account_name" name="payout_account_name" maxlength="100" value="<?php echo htmlspecialchars($payout['payout_account_name']); ?>" placeholder="Ama Mensah" required>
                        </div>
                    </div>
                </div>
                <div class="mf-foot">
                    <p class="mf-note"><i class="bi bi-info-circle" aria-hidden="true"></i> <?php echo $payoutLabel ? 'A change applies to new orders only.' : 'Paystack checks the account before saving it.'; ?></p>
                    <div class="mf-actions">
                        <?php if ($payoutLabel): ?><button type="button" class="cb-btn cb-btn-quiet" data-edit-cancel>Cancel</button><?php endif; ?>
                        <button type="submit" class="cb-btn"><?php echo $payoutLabel ? 'Save new account' : 'Save payout account'; ?></button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </section>
</div>

<script>
// Each card: Edit shows its form in place of the summary; Cancel puts the summary back
document.querySelectorAll('[data-edit-card]').forEach(function (card) {
    var openBtn = card.querySelector('[data-edit-open]'), view = card.querySelector('[data-edit-view]'),
        form = card.querySelector('[data-edit-form]'), cancel = card.querySelector('[data-edit-cancel]');
    if (openBtn && form) {
        openBtn.addEventListener('click', function () {
            form.hidden = false; if (view) view.hidden = true; openBtn.hidden = true;
            var first = form.querySelector('input:not([type=hidden]), select'); if (first) first.focus();
        });
    }
    if (cancel && form) {
        cancel.addEventListener('click', function () {
            form.reset(); form.hidden = true; if (view) view.hidden = false; if (openBtn) { openBtn.hidden = false; openBtn.focus(); }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
