<?php
// Kitchen portal: where the kitchen is and whether shoppers can order from it
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';

require_kitchen();

$controller = new ProductController();
$kitchen = $controller->getBrandById(get_kitchen_id());

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
                <label for="is_active">Taking orders</label>
                <select id="is_active" name="is_active">
                    <option value="1" <?php echo (int)$kitchen['is_active'] === 1 ? 'selected' : ''; ?>>Open: shoppers can order</option>
                    <option value="0" <?php echo (int)$kitchen['is_active'] === 0 ? 'selected' : ''; ?>>Paused: hidden from shoppers</option>
                </select>
            </div>
            <div class="cb-admin-actions"><button type="submit" class="cb-btn">Save details</button></div>
        </form>
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
