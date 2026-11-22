<?php
// One meal, in the theme's layout: details, quantity and note, then more meals from the same kitchen
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$ROOT_DIR = defined('ROOT_DIR') ? ROOT_DIR : '..';

$prodCtrl = new ProductController();
$product = $prodCtrl->getProductById((int)($_GET['id'] ?? 0));

// Shoppers can't open sold-out meals or meals from paused kitchens; admins and the meal's own kitchen can still preview them
$isOwnKitchen = get_kitchen_id() === (int)($product['product_brand'] ?? 0);
if (!$product || (!$prodCtrl->isOrderable($product) && !is_admin() && !$isOwnKitchen)) {
    $_SESSION['error'] = "Sorry, that meal isn't available right now.";
    header("Location: " . $ROOT_DIR . "/views/menu.php");
    exit;
}

$pageHeading = $product['product_title'];
$hidePageTitle = true; // the meal's name is drawn large beside its photo instead
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
            <span class="cb-dish-emoji" aria-hidden="true"><i class="bi <?php echo meal_icon($product); ?>"></i></span>
        <?php endif; ?>
    </div>

    <div class="cb-meal-info">
        <a class="cb-vchip" href="<?php echo $ROOT_DIR; ?>/views/menu.php?brand=<?php echo (int)$product['product_brand']; ?>">
            <?php echo htmlspecialchars($product['brand_name']); ?>
            <i<?php echo $inZoneB ? ' class="b"' : ''; ?>><?php echo $inZoneB ? 'Off-campus' : 'On campus'; ?></i>
        </a>
        <h1 class="cb-meal-title"><?php echo htmlspecialchars($product['product_title']); ?></h1>
        <?php if (!empty($product['product_desc'])): ?>
            <p class="cb-meal-desc"><?php echo nl2br(htmlspecialchars($product['product_desc'])); ?></p>
        <?php endif; ?>

        <div class="cb-meal-tags">
            <span><i class="bi bi-clock" aria-hidden="true"></i> About <?php echo (int)($product['prep_time_minutes'] ?? 15); ?> min to prepare</span>
            <span><i class="bi bi-bicycle" aria-hidden="true"></i> <?php require_once __DIR__ . '/../controllers/DeliveryController.php'; echo htmlspecialchars(DeliveryController::feeTag($product)['text']); ?></span>
            <span><i class="bi bi-tag" aria-hidden="true"></i> <?php echo htmlspecialchars($product['cat_name']); ?></span>
            <?php foreach (ProductController::labelsOf($product) as $mealLabel): ?>
                <span><i class="bi <?php echo ProductController::LABEL_ICONS[$mealLabel]; ?>" aria-hidden="true"></i> <?php echo ProductController::LABELS[$mealLabel]; ?></span>
            <?php endforeach; ?>
            <?php if (!empty($product['location_desc'])): ?>
                <span><i class="bi bi-geo-alt" aria-hidden="true"></i> <?php echo htmlspecialchars($product['location_desc']); ?></span>
            <?php endif; ?>
        </div>

        <?php if (!can_order()): ?>
            <p class="cb-meal-price">GH₵ <?php echo number_format($product['product_price'], 2); ?></p>
            <p class="cb-empty cb-meal-note">
                <?php echo is_admin() ? 'Admin preview' : 'Kitchen preview'; ?><?php echo $prodCtrl->isOrderable($product) ? '' : ': shoppers can\'t see this meal because it is sold out or its kitchen is paused'; ?>.
                <?php if (is_admin()): ?>
                    <a class="cb-link" href="<?php echo $ROOT_DIR; ?>/views/admin/kitchen.php?id=<?php echo (int)$product['product_brand']; ?>">View kitchen</a>
                <?php elseif ($isOwnKitchen): ?>
                    <a class="cb-link" href="<?php echo $ROOT_DIR; ?>/views/kitchen/kitchen_menu.php?edit_id=<?php echo (int)$product['product_id']; ?>">Edit meal</a>
                <?php endif; ?>
            </p>
        <?php else: ?>
            <form class="cb-meal-form" action="<?php echo $ROOT_DIR; ?>/actions/add_to_cart_action.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="product_id" value="<?php echo (int)$product['product_id']; ?>">
                <div class="cb-field">
                    <label for="meal-notes">Special request <span class="cb-muted">(optional)</span></label>
                    <input type="text" id="meal-notes" name="notes" maxlength="255" placeholder="e.g. extra shito, less pepper, no onions">
                </div>
                <div class="cb-meal-buy">
                    <div class="cb-qty cb-qty-lg" data-stepper>
                        <button type="button" data-step="-1" aria-label="One less">−</button>
                        <input type="number" id="meal-qty" name="qty" value="1" min="1" max="<?php echo CartClass::MAX_QTY; ?>" inputmode="numeric" required aria-label="Quantity">
                        <button type="button" data-step="1" aria-label="One more">+</button>
                    </div>
                    <button type="submit" class="cb-cta" data-unit-price="<?php echo htmlspecialchars((string)(float)$product['product_price']); ?>">
                        <span class="cb-cta-price" data-cta-price>GH₵ <?php echo number_format($product['product_price'], 2); ?></span>
                        <span>Add to order</span>
                    </button>
                </div>
            </form>
            <?php if (is_logged_in()): ?>
                <form data-favorite action="<?php echo $ROOT_DIR; ?>/actions/toggle_favorite_action.php" method="POST" class="cb-meal-save">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="product_id" value="<?php echo (int)$product['product_id']; ?>">
                    <button type="submit" class="cb-btn cb-btn-quiet" aria-pressed="<?php echo $mealSaved ? 'true' : 'false'; ?>">
                        <i class="bi <?php echo $mealSaved ? 'bi-heart-fill' : 'bi-heart'; ?>" aria-hidden="true"></i>
                       <span data-save-label>
    <?php echo $mealSaved ? 'Saved to favourites' : 'Save to favourites'; ?>
</span>
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($moreFromKitchen)): ?>
    <div class="cb-row cb-row-label">
        <h2>More from <?php echo htmlspecialchars($product['brand_name']); ?></h2>
        <a class="cb-viewall" href="<?php echo $ROOT_DIR; ?>/views/menu.php?brand=<?php echo (int)$product['product_brand']; ?>">Full menu ›</a>
    </div>
    <div class="cb-grid cb-pairings">
        <?php $showKitchen = false; ?>
        <?php foreach ($moreFromKitchen as $dish): ?>
            <?php require __DIR__ . '/layout/dish_card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
