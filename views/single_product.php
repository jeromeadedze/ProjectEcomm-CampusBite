<?php
// One meal, in the theme's layout: details, quantity and note, then more meals from the same kitchen
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';

$prodCtrl = new ProductController();
$product = $prodCtrl->getProductById((int)($_GET['id'] ?? 0));

// Shoppers can't open sold-out meals or meals from paused kitchens; admins can still preview them
if (!$product || (!$prodCtrl->isOrderable($product) && !is_admin())) {
    $_SESSION['error'] = "Sorry, that meal isn't available right now.";
    header("Location: " . $ROOT_DIR . "/views/menu.php");
    exit;
}

$pageHeading = $product['product_title'];
require_once __DIR__ . '/layout/header.php';

$mealImage = !empty($product['product_image']) && file_exists(__DIR__ . '/../images/products/' . $product['product_image'])
    ? $ROOT_DIR . '/images/products/' . htmlspecialchars($product['product_image'])
    : null;
$inZoneB = $product['zone'] === 'Zone B';
$mealSaved = in_array((int)$product['product_id'], $favoriteIds, true);

$moreFromKitchen = array_slice(array_filter($prodCtrl->getProductsByBrand($product['product_brand']), function ($other) use ($product) {
    return (int)$other['product_id'] !== (int)$product['product_id'];
}), 0, 4);
?>

<div class="cb-meal">
    <div class="cb-meal-media">
        <?php if ($mealImage): ?>
            <img src="<?php echo $mealImage; ?>" alt="<?php echo htmlspecialchars($product['product_title']); ?>">
        <?php else: ?>
            <span class="cb-dish-emoji" aria-hidden="true"><i class="bi bi-egg-fried"></i></span>
        <?php endif; ?>
    </div>

    <div class="cb-meal-info">
        <a class="cb-vchip" href="<?php echo $ROOT_DIR; ?>/views/menu.php?brand=<?php echo (int)$product['product_brand']; ?>">
            <?php echo htmlspecialchars($product['brand_name']); ?>
            <i<?php echo $inZoneB ? ' class="b"' : ''; ?>><?php echo $inZoneB ? 'Off-campus' : 'On campus'; ?></i>
        </a>
        <p class="cb-meal-price">GH₵ <?php echo number_format($product['product_price'], 2); ?></p>

        <div class="cb-meal-tags">
            <span><i class="bi bi-clock" aria-hidden="true"></i> About <?php echo (int)($product['prep_time_minutes'] ?? 15); ?> min to prepare</span>
            <span><i class="bi bi-bicycle" aria-hidden="true"></i> GH₵ <?php echo number_format($prodCtrl->deliveryFeeForZone($product['zone']), 2); ?> delivery</span>
            <span><i class="bi bi-tag" aria-hidden="true"></i> <?php echo htmlspecialchars($product['cat_name']); ?></span>
            <?php if (!empty($product['location_desc'])): ?>
                <span><i class="bi bi-geo-alt" aria-hidden="true"></i> <?php echo htmlspecialchars($product['location_desc']); ?></span>
            <?php endif; ?>
        </div>

        <?php if (!empty($product['product_desc'])): ?>
            <p class="cb-meal-desc"><?php echo nl2br(htmlspecialchars($product['product_desc'])); ?></p>
        <?php endif; ?>

        <?php if (is_admin()): ?>
            <p class="cb-empty cb-meal-note">
                Admin preview<?php echo $prodCtrl->isOrderable($product) ? '' : ': shoppers can\'t see this meal because it is sold out or its kitchen is paused'; ?>.
                <a class="cb-link" href="<?php echo $ROOT_DIR; ?>/views/admin/product.php?edit_id=<?php echo (int)$product['product_id']; ?>">Edit meal</a>
            </p>
        <?php else: ?>
            <form class="cb-meal-form" action="<?php echo $ROOT_DIR; ?>/actions/add_to_cart_action.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="product_id" value="<?php echo (int)$product['product_id']; ?>">
                <div class="cb-field">
                    <label for="meal-qty">Quantity</label>
                    <div class="cb-stepper" data-stepper>
                        <button type="button" data-step="-1" aria-label="One less">−</button>
                        <input type="number" id="meal-qty" name="qty" value="1" min="1" max="<?php echo CartClass::MAX_QTY; ?>" inputmode="numeric" required>
                        <button type="button" data-step="1" aria-label="One more">+</button>
                    </div>
                </div>
                <div class="cb-field">
                    <label for="meal-notes">Special request <span class="cb-muted">(optional)</span></label>
                    <input type="text" id="meal-notes" name="notes" maxlength="255" placeholder="e.g. extra shito, less pepper, no onions">
                </div>
                <button type="submit" class="cb-checkout">Add to order</button>
            </form>
            <?php if (is_logged_in()): ?>
                <form action="<?php echo $ROOT_DIR; ?>/actions/toggle_favorite_action.php" method="POST" class="cb-meal-save">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="product_id" value="<?php echo (int)$product['product_id']; ?>">
                    <button type="submit" class="cb-btn cb-btn-quiet" aria-pressed="<?php echo $mealSaved ? 'true' : 'false'; ?>">
                        <i class="bi <?php echo $mealSaved ? 'bi-heart-fill' : 'bi-heart'; ?>" aria-hidden="true"></i>
                        <?php echo $mealSaved ? 'Saved to favourites' : 'Save to favourites'; ?>
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($moreFromKitchen)): ?>
    <div class="cb-row">
        <h2>More from <?php echo htmlspecialchars($product['brand_name']); ?></h2>
        <a class="cb-viewall" href="<?php echo $ROOT_DIR; ?>/views/menu.php?brand=<?php echo (int)$product['product_brand']; ?>">Full menu ›</a>
    </div>
    <div class="cb-grid">
        <?php $showKitchen = false; ?>
        <?php foreach ($moreFromKitchen as $dish): ?>
            <?php require __DIR__ . '/layout/dish_card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
