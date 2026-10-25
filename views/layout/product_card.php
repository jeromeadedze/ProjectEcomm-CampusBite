<?php
/**
 * Product Card Component
 * Expects $p array with product fields
 */
?>
<div class="col-sm-6 col-lg-4 col-xl-3">
    <div class="cb-product-card">
        <div class="cb-product-img-wrap">
            <?php 
                $imgUrl = !empty($p['product_image']) && file_exists(__DIR__ . '/../../images/products/' . $p['product_image'])
                    ? $ROOT_DIR . '/images/products/' . htmlspecialchars($p['product_image'])
                    : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=600&q=80';
            ?>
            <img src="<?php echo $imgUrl; ?>" alt="<?php echo htmlspecialchars($p['product_title']); ?>" class="cb-product-img" loading="lazy">
            <span class="cb-product-badge <?php echo ($p['zone'] === 'Zone B') ? 'bg-warning text-dark' : 'bg-success text-white'; ?>">
                <i class="bi bi-geo-alt-fill me-1"></i><?php echo htmlspecialchars($p['zone'] ?? 'Zone A'); ?>
            </span>
        </div>
        <div class="cb-product-body">
            <div class="cb-product-vendor">
                <i class="bi bi-shop"></i> <?php echo htmlspecialchars($p['brand_name'] ?? 'Campus Kitchen'); ?>
            </div>
            <a href="<?php echo $ROOT_DIR; ?>/views/single_product.php?id=<?php echo $p['product_id']; ?>" class="cb-product-title">
                <?php echo htmlspecialchars($p['product_title']); ?>
            </a>
            <p class="cb-product-desc">
                <?php echo htmlspecialchars($p['product_desc'] ?? ''); ?>
            </p>
            <div class="cb-product-footer">
                <div class="cb-product-price">
                    GH₵ <?php echo number_format($p['product_price'], 2); ?>
                </div>
                <form action="<?php echo $ROOT_DIR; ?>/actions/add_to_cart_action.php" method="POST" class="m-0">
                    <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
                    <input type="hidden" name="qty" value="1">
                    <button type="submit" class="cb-btn-add" title="Add to Order">
                        <i class="bi bi-plus-lg"></i> Add
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
