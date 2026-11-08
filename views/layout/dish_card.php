<?php
/**
 * One meal card, translated from the theme's cb-dish-card snippet.
 * Expects $dish (a meal row with brand_name and zone). Set $showKitchen = false to hide the kitchen chip.
 * The heart saves or unsaves the meal; header.php loads $favoriteIds for the signed-in shopper.
 */
$showKitchen = $showKitchen ?? true;
$dishImage = !empty($dish['product_image']) && file_exists(__DIR__ . '/../../images/products/' . $dish['product_image'])
    ? $ROOT_DIR . '/images/products/' . htmlspecialchars($dish['product_image'])
    : null;
$dishSaved = in_array((int)$dish['product_id'], $favoriteIds ?? [], true);
$heartIcon = '<svg viewBox="0 0 24 24" fill="' . ($dishSaved ? 'currentColor' : 'none') . '" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/></svg>';
?>
<article class="cb-dish">
    <?php if (is_logged_in() && !is_admin()): ?>
        <form action="<?php echo $ROOT_DIR; ?>/actions/toggle_favorite_action.php" method="POST" class="cb-heart-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="product_id" value="<?php echo (int)$dish['product_id']; ?>">
            <button type="submit" class="cb-heart<?php echo $dishSaved ? ' is-on' : ''; ?>" aria-pressed="<?php echo $dishSaved ? 'true' : 'false'; ?>" aria-label="<?php echo ($dishSaved ? 'Remove ' : 'Save ') . htmlspecialchars($dish['product_title']) . ($dishSaved ? ' from' : ' to'); ?> favourites"><?php echo $heartIcon; ?></button>
        </form>
    <?php elseif (!is_logged_in()): ?>
        <a class="cb-heart" href="<?php echo $ROOT_DIR; ?>/views/login.php" aria-label="Sign in to save <?php echo htmlspecialchars($dish['product_title']); ?>"><?php echo $heartIcon; ?></a>
    <?php endif; ?>
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
        </a>
    <?php endif; ?>
    <p class="cb-dish-name"><?php echo htmlspecialchars($dish['product_title']); ?></p>
    <div class="cb-dish-foot">
        <span class="cb-price">GH₵ <?php echo number_format($dish['product_price'], 2); ?></span>
        <?php if (!is_admin()): ?>
        <form action="<?php echo $ROOT_DIR; ?>/actions/add_to_cart_action.php" method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="product_id" value="<?php echo (int)$dish['product_id']; ?>">
            <input type="hidden" name="qty" value="1">
            <button class="cb-add" type="submit" aria-label="Add <?php echo htmlspecialchars($dish['product_title']); ?> to your order">+</button>
        </form>
        <?php endif; ?>
    </div>
</article>
