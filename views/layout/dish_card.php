<?php
/**
 * One meal card, translated from the theme's cb-dish-card snippet.
 * Expects $dish (a meal row with brand_name and zone). Set $showKitchen = false to hide the kitchen chip.
 * Favourite hearts join the card with the favourites page.
 */
$showKitchen = $showKitchen ?? true;
$dishImage = !empty($dish['product_image']) && file_exists(__DIR__ . '/../../images/products/' . $dish['product_image'])
    ? $ROOT_DIR . '/images/products/' . htmlspecialchars($dish['product_image'])
    : null;
$dishInZoneB = ($dish['zone'] ?? '') === 'Zone B';
?>
<article class="cb-dish">
    <a href="<?php echo $ROOT_DIR; ?>/views/single_product.php?id=<?php echo (int)$dish['product_id']; ?>" class="cb-dish-img" aria-label="<?php echo htmlspecialchars($dish['product_title']); ?>">
        <?php if ($dishImage): ?>
            <img src="<?php echo $dishImage; ?>" alt="" loading="lazy">
        <?php else: ?>
            <span class="cb-dish-emoji" aria-hidden="true"><i class="bi bi-egg-fried"></i></span>
        <?php endif; ?>
    </a>
    <?php if ($showKitchen): ?>
        <a class="cb-vchip" href="<?php echo $ROOT_DIR; ?>/views/menu.php?brand=<?php echo (int)$dish['product_brand']; ?>">
            <?php echo htmlspecialchars($dish['brand_name']); ?>
            <i<?php echo $dishInZoneB ? ' class="b"' : ''; ?>><?php echo $dishInZoneB ? 'Off-campus' : 'On campus'; ?></i>
        </a>
    <?php endif; ?>
    <p class="cb-dish-name"><?php echo htmlspecialchars($dish['product_title']); ?></p>
    <div class="cb-dish-foot">
        <span class="cb-price">GH₵ <?php echo number_format($dish['product_price'], 2); ?></span>
        <form action="<?php echo $ROOT_DIR; ?>/actions/add_to_cart_action.php" method="POST">
            <input type="hidden" name="product_id" value="<?php echo (int)$dish['product_id']; ?>">
            <input type="hidden" name="qty" value="1">
            <button class="cb-add" type="submit" aria-label="Add <?php echo htmlspecialchars($dish['product_title']); ?> to your order">+</button>
        </form>
    </div>
</article>
